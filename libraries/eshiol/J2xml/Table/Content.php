<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @version     __DEPLOY_VERSION__
 * @since       1.5.1
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2026 Helios Ciancio. All Rights Reserved
 * @copyright   Copyright (C) 2026 Svend Gundestrup. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */
namespace eshiol\J2xml\Table;

use eshiol\J2xml\Table\Category;
use eshiol\J2xml\Table\Field;
use eshiol\J2xml\Table\Image;
use eshiol\J2xml\Table\Table;
use eshiol\J2xml\Table\Tag;
use eshiol\J2xml\Table\User;
use eshiol\J2xml\Table\Viewlevel;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\SiteRouter;
use Joomla\CMS\Version;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Utilities\ArrayHelper;

/**
 *
 * Content Table
 *
 */
class Content extends Table
{

    /**
     * Constructor
     *
     * @param \Joomla\Database\DatabaseDriver $db
     *          A database connector object
     *
     * @since 1.5.1
     */
    public function __construct (\Joomla\Database\DatabaseDriver $db)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

        parent::__construct('#__content', 'id', $db);

    /**
     * $version = new \Joomla\CMS\Version();
     * if ($version->isCompatible('3.4'))
     * {
     * // Set the alias since the column is called state
     * $this->setColumnAlias('published', 'state');
     * }
     */
    }

    /**
     * Add an alias backed by a content-related table joined to #__content.
     *
     * @param string $alias
     *          the XML alias name
     * @param string $table
     *          the related database table
     * @param string $select
     *          the select expression
     *
     * @return void
     */
    private function addContentJoinAlias(string $alias, string $table, string $select): void
    {
        $db = $this->getDatabase();
        $this->aliases[$alias] = (string) $db->getQuery()->clear()
            ->select($select)
            ->from($db->quoteName($table, 'f'))
            ->join('RIGHT', $db->quoteName('#__content', 'a') . ' ON ' . $db->quoteName('f.content_id') . ' = ' . $db->quoteName('a.id'))
            ->where($db->quoteName('a.id') . ' = ' . (int) $this->id);
    }

    /**
     * Export item list to xml
     *
     * @access public
     */
    function toXML ($mapKeysToText = false)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

        $this->excluded = array_merge($this->excluded, [
                'sectionid',
                'mask',
                'title_alias',
                'ordering'
        ]);

        $db = $this->getDatabase();
        $this->addContentJoinAlias('featured', '#__content_frontpage', 'COALESCE(' . $db->quoteName('f.ordering') . ', 0)');
        $this->addContentJoinAlias('featured_up', '#__content_frontpage', $db->quoteName('f.featured_up'));
        $this->addContentJoinAlias('featured_down', '#__content_frontpage', $db->quoteName('f.featured_down'));
        $this->addContentJoinAlias('rating_sum', '#__content_rating', 'COALESCE(' . $db->quoteName('rating_sum') . ', 0)');
        $this->addContentJoinAlias('rating_count', '#__content_rating', 'COALESCE(' . $db->quoteName('rating_count') . ', 0)');

        $slug = $this->alias ? ($this->id . ':' . $this->alias) : $this->id;

        // We need to make sure we are always using the site router, even if the language plugin is executed in admin app.
        $router = CMSApplication::getRouter('site');
        $url = $router->build(RouteHelper::getArticleRoute($slug, $this->catid, $this->language));

        $canonical = str_replace(\Joomla\CMS\Uri\Uri::base(true) . '/', \Joomla\CMS\Uri\Uri::root(), $url);
        // $this->aliases['canonical'] = 'SELECT \'' . $canonical . '\' FROM
        // DUAL';
        if ($db->getServerType() === 'sqlserver')
        {
            $this->aliases['canonical'] = (string) $db->getQuery()->clear()
                ->select($db->quote($canonical))
                ->from($db->quoteName('DUAL'));
        }
        else
        {
            $this->aliases['canonical'] = (string) $db->getQuery()->clear()->select($db->quote($canonical));
        }

        $this->buildTagAlias('com_content.article');
        $this->buildFieldAliases();
        $this->buildAssociationAlias('#__content', 'com_content.item');

        return parent::toXML($mapKeysToText);
    }

    /**
     * Import data
     *
     * @param \SimpleXMLElement $xml
     *          xml
     * @param \JRegistry $params
     *          @option int 'content' 0: No (default); 1: Yes, if not exists;
     *          2: Yes, overwrite if exists
     *          @option int 'com_content_category_default'
     *          @option int 'content_category_forceto'
     *          @option string 'context'
     *
     * @throws
     * @return void
     * @access public
     *
     * @since 18.8.301
     */
    public static function import ($xml, &$params, $db = null, $userId = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

        $import_content = $params->get('content', 0);
        if ($import_content == 0)
        {
            return;
        }

        //$params->def('content_category_default', self::getCategoryId('uncategorised', 'com_content'));
        $force_to = $params->get('content_category_forceto');
        $context = $params->get('context', 'com_content.article');
        $db = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $nullDate = $db->getNullDate();
        $userid = $userId ?? \Joomla\CMS\Factory::getApplication()->getIdentity()->id;
        \Joomla\CMS\Plugin\PluginHelper::importPlugin('content');

        $params->set('extension', 'com_content');
        $import_categories = $params->get('categories');
        if ($import_categories)
        {
            Category::import($xml, $params);
        }

        $keep_id        = $params->get('keep_id', 0);
        $keep_frontpage = $params->get('keep_data', 0);
        $keep_rating    = $params->get('keep_data', 0);
        $keep_data      = $params->get('keep_data', 0);

        $contentFormPath = JPATH_ADMINISTRATOR . '/components/com_content';
        \Joomla\CMS\Form\Form::addFormPath($contentFormPath . '/forms');
        \Joomla\CMS\Form\Form::addFormPath($contentFormPath . '/models/forms');
        \Joomla\CMS\Form\Form::addFieldPath($contentFormPath . '/models/fields');
        \Joomla\CMS\Form\Form::addFormPath($contentFormPath . '/model/form');
        \Joomla\CMS\Form\Form::addFieldPath($contentFormPath . '/model/field');

        $mvcFactory = Factory::getApplication()->bootComponent('com_content')->getMVCFactory();

        foreach ($xml->xpath("//j2xml/content[not(name = '')]") as $record)
        {
            self::prepareData($record, $data, $params);

            $id = $data['id'];
            if ($force_to)
            {
                $data['catid'] = $force_to;
            }

            $content = $db->setQuery(
                $query = $db->getQuery()->clear()
                    ->select(
                        [
                            $db->quoteName('id'),
                            $db->quoteName('title'),
                            'GREATEST(' . $db->quoteName('created') . ',' . $db->quoteName('modified') . ') ' . $db->quoteName('modified')
                        ])
                    ->from($db->quoteName('#__content'))
                    ->where($db->quoteName('catid') . ' = ' . $db->quote($data['catid']))
                    ->where($db->quoteName('alias') . ' = ' . $db->quote($data['alias'])))
                ->loadObject();

            $table = $mvcFactory->createModel('Article', 'Administrator', ['ignore_request' => true]);

            if ((($import_content == 1) && $content) || (($import_content == 3) && $content && $content->modified >= $data['modified']))
            {
                if ($id == $content->id)
                {
                    \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_EXISTS', $data['title'], $id), \Joomla\CMS\Log\Log::NOTICE, 'lib_j2xml'));
                }
                elseif ($keep_id)
                {
                    \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_NOT_IMPORTED', $data['title'], $id, \Joomla\CMS\Language\Text::_('JLIB_DATABASE_ERROR_ARTICLE_UNIQUE_ALIAS')), \Joomla\CMS\Log\Log::ERROR, 'lib_j2xml'));
                }
                else
                {
                    \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_EXISTS', $data['title'], $id . '->' . $content->id), \Joomla\CMS\Log\Log::NOTICE, 'lib_j2xml'));
                }
                continue;
            }
            elseif (($import_content >= 2) && $content && $keep_id && ($id != $content->id))
            {
                \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_NOT_IMPORTED', $data['title'], $id, \Joomla\CMS\Language\Text::_('JLIB_DATABASE_ERROR_ARTICLE_UNIQUE_ALIAS')), \Joomla\CMS\Log\Log::ERROR, 'lib_j2xml'));
                continue;
            }
            else
            {
                if (!$content)
                { // new article
                    $isNew = true;
                    $data['id'] = null;
                }
                else
                { // article already exists
                    $isNew = false;
                    $data['id'] = $content->id;
                }

                if ($table->save($data))
                {
                    // fix hits
                    $table->save($data);

                    $item = $table->getItem();

                    self::restoreModifiedFields($db, $data, $item->id, $keep_data);
                    self::syncFrontpage($db, $data, $item->id, $keep_frontpage);
                    self::syncRating($db, $data, $item->id, $keep_rating);

                    if (($keep_id == 1) && ($id > 1) && !self::applySourceId($item, $id))
                    {
                        continue;
                    }

                    if ($id != $item->id)
                    {
                        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_IMPORTED', $item->title, $id, $item->id), \Joomla\CMS\Log\Log::INFO,    'lib_j2xml'));
                    }
                    else
                    {
                        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_UPDATED', $item->title, $id), \Joomla\CMS\Log\Log::INFO, 'lib_j2xml'));
                    }
                }
                else
                {
                    \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_NOT_IMPORTED', $data['title'], $id, $table->getError()), \Joomla\CMS\Log\Log::ERROR, 'lib_j2xml'));
                }
            }
        }
    }

    /**
     * Restore the exported modified/modified_by values after a save when
     * keep_data is enabled (Joomla's save overwrites them).
     *
     * @param \Joomla\Database\DatabaseInterface $db
     *          the database connector
     * @param array $data
     *          the imported article data
     * @param int $itemId
     *          the saved article id
     * @param int $keepData
     *          the keep_data option
     *
     * @return void
     */
    private static function restoreModifiedFields ($db, $data, $itemId, $keepData)
    {
        if ($keepData != 1)
        {
            return;
        }

        $sets = [];
        if (isset($data['modified']))
        {
            $sets[] = $db->quoteName('modified') . ' = ' . $db->quote($data['modified']);
        }
        if (isset($data['modified_by']))
        {
            $sets[] = $db->quoteName('modified_by') . ' = ' . $data['modified_by'];
        }
        if (!count($sets))
        {
            return;
        }

        $query = $db->getQuery()->clear()
            ->update($db->quoteName('#__content'))
            ->where($db->quoteName('id') . ' = ' . $itemId);
        foreach ($sets as $set)
        {
            $query->set($set);
        }
        $db->setQuery($query)->execute();
    }

    /**
     * Synchronise the frontpage (featured) assignment of an imported article.
     *
     * @param \Joomla\Database\DatabaseInterface $db
     *          the database connector
     * @param array $data
     *          the imported article data
     * @param int $itemId
     *          the saved article id
     * @param int $keepFrontpage
     *          the keep_frontpage option
     *
     * @return void
     */
    private static function syncFrontpage ($db, $data, $itemId, $keepFrontpage)
    {
        // Always clear any existing frontpage row first; re-inserting without
        // deleting produces a duplicate key error when the article is already
        // featured.
        $db->setQuery(
            $db->getQuery()->clear()
                ->delete($db->quoteName('#__content_frontpage'))
                ->where($db->quoteName('content_id') . ' = ' . (int) $itemId)
        )->execute();

        if (($keepFrontpage == 0) || ((int) ($data['featured'] ?? 0) === 0))
        {
            return;
        }

        // Use query builder for cross-database compatibility (MySQL + PostgreSQL)
        $columns = [$db->quoteName('content_id'), $db->quoteName('ordering')];
        $values  = [(int) $itemId, (int) ($data['ordering'] ?? 0)];
        if (isset($data['featured_up']))
        {
            $columns[] = $db->quoteName('featured_up');
            $values[]  = $db->quote($data['featured_up']);
        }
        if (isset($data['featured_down']))
        {
            $columns[] = $db->quoteName('featured_down');
            $values[]  = $db->quote($data['featured_down']);
        }

        $db->setQuery(
            $db->getQuery()->clear()
                ->insert($db->quoteName('#__content_frontpage'))
                ->columns($columns)
                ->values(implode(',', $values))
        )->execute();
    }

    /**
     * Synchronise the rating of an imported article.
     *
     * @param \Joomla\Database\DatabaseInterface $db
     *          the database connector
     * @param array $data
     *          the imported article data
     * @param int $itemId
     *          the saved article id
     * @param int $keepRating
     *          the keep_rating option
     *
     * @return void
     */
    private static function syncRating ($db, $data, $itemId, $keepRating)
    {
        if (($keepRating == 0) || (!isset($data['rating_count'])) || ($data['rating_count'] == 0))
        {
            $query = $db->getQuery()->clear()
                ->delete($db->quoteName('#__content_rating'))
                ->where($db->quoteName('content_id') . ' = ' . $itemId);
            $db->setQuery($query)->execute();
            return;
        }

        $rating = new \stdClass();
        $rating->content_id = $itemId;
        $rating->rating_count = $data['rating_count'];
        $rating->rating_sum = $data['rating_sum'];
        $rating->lastip = $_SERVER['REMOTE_ADDR'];
        try
        {
            $db->insertObject('#__content_rating', $rating);
        }
        catch (\Exception $ex)
        {
            $db->updateObject('#__content_rating', $rating, 'content_id');
        }
    }

    /**
     * Restore the exported article id on the saved row when keep_id is
     * enabled.
     *
     * @param \Joomla\CMS\Table\Table $item
     *          the saved article
     * @param int $id
     *          the exported article id
     *
     * @return boolean true on success, false when the id is already taken
     */
    private static function applySourceId ($item, $id)
    {
        try
        {
            self::changeId($item->id, $id);

            $item->id = $id;

            return true;
        }
        catch (\Exception $ex)
        {
            \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ARTICLE_ID_PRESENT', $item->title, $id, $item->id), \Joomla\CMS\Log\Log::WARNING, 'lib_j2xml'));

            return false;
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see Table::prepareData()
     *
     * @since 18.8.301
     */
    public static function prepareData ($record, &$data, $params, $userId = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

        $db      = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        $params->set('extension', 'com_content');
        parent::prepareData($record, $data, $params);

        if (empty($data['id']))
        {
            $data['id'] = 0;
        }

        if (empty($data['alias']) || (trim($data['alias']) == ''))
        {
            $data['alias'] = htmlspecialchars_decode($data['title'], ENT_QUOTES);   
        }
        $data['alias'] = \Joomla\CMS\Filter\OutputFilter::stringURLSafe($data['alias']);
        if (trim(str_replace('-', '', $data['alias'])) == '') {
            $data['alias'] = (new \Joomla\CMS\Date\Date("now"))->format('Y-m-d-H-i-s');
        }

        // Apply default values for missing fields.
        foreach (['fulltext' => '', 'metakey' => '', 'metadesc' => '', 'language' => '*', 'introtext' => ''] as $field => $default)
        {
            if (!isset($data[$field]))
            {
                $data[$field] = $default;
            }
        }
        if (!isset($data['created_by']))
        {
            $data['created_by'] = $userId ?? \Joomla\CMS\Factory::getApplication()->getIdentity()->id;
        }

        // if (!$version->isCompatible('3.4') && isset($data['published']))
        if (isset($data['published']))
        {
            // Set the column since its name is changed from published to state
            $data['state'] = $data['published'];
            unset($data['published']);
        }

        $data['featured'] = (int) (($data['featured'] ?? 0) > 0);
        if ($params->get('keep_frontpage') == 0)
        {
            $data['ordering'] = 0;
        }
        elseif (!isset($data['ordering']))
        {
            $data['ordering'] = $data['featured'];
        }

        if (!isset($data['catid']))
        {
            $data['catid'] = $params->get('com_content_category_default');
        }

        self::resolveAssociationData($data, static function ($association) {
            return self::getArticleId($association);
        }, '#__content', $db);
    }

    /**
     * Export data
     *
     * @param int $id
     *          the id of the item to be exported
     * @param \SimpleXMLElement $xml
     *          xml
     * @param array $options
     *
     * @throws
     * @return void
     * @access public
     *
     * @since 18.8.310
     */
    public static function export ($id, &$xml, $options, $db = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

        $item = static::loadExportItem('content', $id, $xml, $db);
        if (!$item)
        {
            return;
        }

        $params = new \Joomla\Registry\Registry($options);
        \Joomla\CMS\Plugin\PluginHelper::importPlugin('j2xml');

        \Joomla\CMS\Factory::getApplication()->getDispatcher()->dispatch('onJ2xmlBeforeExportContent',
            new \Joomla\Event\Event('onJ2xmlBeforeExportContent', [
                'lib_j2xml.article',
                &$item,
                $params
            ]));

        self::exportItemViewlevel($item->access, $xml, $options);
        self::exportItemCategory($item->catid, $xml, $options);
        self::exportItemTags('com_content.article', $id, $xml, $options);

        if (isset($options['fields']) && $options['fields'])
        {
            self::exportFields($id, $xml, $options, $db);
        }

        self::appendItemXml($item, $xml);
        self::exportItemUsers($item, $xml, $options);

        if (isset($options['images']) && $options['images'])
        {
            self::exportImages($item, $id, $xml, $options, $db);
        }

        return $xml;
    }

    /**
     * Export the images referenced by the article text, the images field
     * and the media/imagelist/editor custom fields.
     *
     * @param Content $item
     *          the article being exported
     * @param int $id
     *          the article id
     * @param \SimpleXMLElement $xml
     *          the export document
     * @param array $options
     *          the export options
     * @param \Joomla\Database\DatabaseInterface $db
     *          the database connector
     *
     * @return void
     */
    private static function exportImages ($item, $id, &$xml, $options, $db)
    {
        self::exportImagesFromText($item->introtext . $item->fulltext, $xml, $options);
        self::exportImagesFromJson($item->images, $xml, $options, ['image_fulltext', 'image_intro']);

        foreach($db->setQuery($db->getQuery()->clear()
            ->select($db->quoteName('v.value'))
            ->from($db->quoteName('#__fields_values', 'v'))
            ->from($db->quoteName('#__fields', 'f'))
            ->where($db->quoteName('f.id') . ' = ' . $db->quoteName('v.field_id'))
            ->where($db->quoteName('v.item_id') . ' = ' . $db->quote((string) $id))
            ->where($db->quoteName('f.type') . ' = ' . $db->quote('media')))
            ->loadColumn() as $_image)
        {
            Image::export($_image, $xml, $options);
        }

        foreach($db->setQuery($db->getQuery()->clear()
            ->select($db->quoteName('f.fieldparams'))
            ->select($db->quoteName('v.value'))
            ->from($db->quoteName('#__fields_values', 'v'))
            ->from($db->quoteName('#__fields', 'f'))
            ->where($db->quoteName('f.id') . ' = ' . $db->quoteName('v.field_id'))
            ->where($db->quoteName('v.item_id') . ' = ' . $db->quote((string) $id))
            ->where($db->quoteName('f.type') . ' = ' . $db->quote('imagelist')))
            ->loadObjectList() as $field)
        {
            $params = json_decode($field->fieldparams);
            $_image = ComponentHelper::getParams('com_media')->get('image_path', 'images') . '/' . (isset($params->directory) ? $params->directory . '/' : '') . $field->value;
            Image::export($_image, $xml, $options);
        }

        foreach($db->setQuery($db->getQuery()->clear()
            ->select($db->quoteName('v.value'))
            ->from($db->quoteName('#__fields_values', 'v'))
            ->from($db->quoteName('#__fields', 'f'))
            ->where($db->quoteName('f.id') . ' = ' . $db->quoteName('v.field_id'))
            ->where($db->quoteName('v.item_id') . ' = ' . $db->quote((string) $id))
            ->where($db->quoteName('f.type') . ' = ' . $db->quote('editor')))
            ->loadColumn() as $text)
        {
            \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($text, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
            self::exportImagesFromText($text, $xml, $options);
        }
    }

    /**
     * Export the images referenced inside a text value.
     *
     * @param string $text
     *          the text to scan for image references
     * @param \SimpleXMLElement $xml
     *          the export document
     * @param array $options
     *          the export options
     *
     * @return void
     */
    /**
     *
     * {@inheritdoc}
     * @see Table::getCategoryId()
     */
    public static function getCategoryId ($category, $extension = 'com_content', $defaultCategoryId = 0, $db = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

        return parent::getCategoryId($category, $extension, $defaultCategoryId);
    }

    /**
     * Change the content ID
     *
     * @since 23.2.378
     */
    public static function changeId($id, $newid, $db = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__ . '(' . $id . ', ' . $newid . ')', \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

        if ($id == $newid)
        {
            return;
        }

        $db      = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $context = 'com_content.article';

        // Check id
        $query = $db->getQuery()->clear()
            ->select($db->quoteName('title'))
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('id') . ' = ' . $id);
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
        $title = $db->setQuery($query)->loadResult();
        if (!$title)
        {
            throw new \Exception(\Joomla\CMS\Language\Text::sprintf('com_j2xml_ARTICLE_NOT_FOUND', $id));
        }

        // Check new id
        $query = $db->getQuery()->clear()
            ->select($db->quoteName('title'))
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('id') . ' = ' . $newid);
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
        if ($db->setQuery($query)->loadObject())
        {
            throw new \Exception(\Joomla\CMS\Language\Text::sprintf('com_j2xml_ARTICLE_UNIQUE_ID', $newid));
        }

        // Content
        $query = $db->getQuery()->clear()
            ->select('MAX(' . $db->quoteName('id') . ')')
            ->from($db->quoteName('#__content'));
        $maxid = (int) $db->setQuery($query)->loadResult();
        if ($newid > $maxid)
        {
            self::resetAutoIncrement($db, $newid + 1, '#__content', '#__content_id_seq');
        }

        self::updateColumn($db, '#__content', 'id', $newid, $id);

        // Asset
        self::updateColumn($db, '#__assets', 'name', $db->quote($context . '.' . $newid), $db->quote($context . '.' . $id));

        // Workflow
        self::updateColumn($db, '#__workflow_associations', 'item_id', $newid, $id, [$db->quoteName('extension') . ' = ' . $db->quote($context)]);

        // Field
        self::updateColumn($db, '#__fields_values', 'item_id', $newid, $id);

        // History
        $query = $db->getQuery()->clear()
            ->update($db->quoteName('#__history'))
            ->set($db->quoteName('item_id') . ' = ' . $db->quote($context . '.' . $newid))
            ->set($db->quoteName('version_data') . ' = REPLACE(' . $db->quoteName('version_data') . ', "{\"id\":' . $newid . ',", "{\"id\":' . $id . ',")')
            ->set($db->quoteName('sha1_hash') . ' = SHA1(REPLACE(' . $db->quoteName('version_data') . ', "{\"id\":' . $newid . ',", "{\"id\":' . $id . ',"))')
            ->where($db->quoteName('item_id') . ' = ' . $db->quote($context . '.' . $id));
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
        $db->setQuery($query)->execute();

        // Menu
        $contextPart = explode('.', $context);
        $option      = $contextPart[0];
        $view        = $contextPart[1];
        $query = $db->getQuery()->clear()
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
            ->where($db->quoteName('element') . ' = ' . $db->quote($option));
        $componentId = $db->setQuery($query)->loadResult();

        self::updateColumn(
            $db,
            '#__menu',
            'link',
            $db->quote('index.php?option=' . $option . '&view=' . $view . '&id=' . $newid),
            $db->quote('index.php?option=' . $option . '&view=' . $view . '&id=' . $id),
            [$db->quoteName('component_id') . ' = ' . $componentId]);

        // Language association
        if (\Joomla\CMS\Language\Associations::isEnabled())
        {
            $contextLanguage = 'com_content.item';

            $query = $db->getQuery()->clear()
                ->select($db->quoteName('key'))
                ->from($db->quoteName('#__associations'))
                ->where($db->quoteName('id') . ' = ' . (int) $id)
                ->where($db->quoteName('context') . ' = ' . $db->quote($contextLanguage));
            \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

            $key = $db->setQuery($query)->loadResult();
            \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($key, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

            if ($key)
            {
                // update id
                self::updateColumn($db, '#__associations', 'id', $newid, $id, [
                    $db->quoteName('key') . ' = ' . $db->quote($key),
                    $db->quoteName('context') . ' = ' . $db->quote($contextLanguage)
                ]);

                // update key
                $query = $db->getQuery()->clear()
                    ->select($db->quoteName('c.id'))
                    ->select($db->quoteName('c.language'))
                    ->from($db->quoteName('#__associations', 'a'))
                    ->join('INNER', $db->quoteName('#__content', 'c') . ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.id') . ')')
                    ->where($db->quoteName('key') . ' = ' . $db->quote($key))
                    ->where($db->quoteName('context') . ' = ' . $db->quote($contextLanguage));
                \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
                $rows = $db->setQuery($query)->loadObjectList();
                $associations = [];
                foreach ($rows as $row)
                {
                    $associations[$row->language] = (int) $row->id;
                }
                \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(json_encode($associations), \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
                $newkey   = md5(json_encode($associations)); // nosemgrep: weak-crypto — non-cryptographic lookup key for #__associations, matches Joomla core // NOSONAR

                self::updateColumn($db, '#__associations', 'key', $db->quote($newkey), $db->quote($key), [
                    $db->quoteName('context') . ' = ' . $db->quote($contextLanguage)
                ]);
            }
        }

        // Tags
        self::updateColumn($db, '#__contentitem_tag_map', 'content_item_id', $newid, $id, [
            $db->quoteName('type_alias') . ' = ' . $db->quote($context)
        ]);

        // Frontpage
        self::updateColumn($db, '#__content_frontpage', 'content_id', $newid, $id);

        // Rating
        self::updateColumn($db, '#__content_rating', 'content_id', $newid, $id);
    }
}
