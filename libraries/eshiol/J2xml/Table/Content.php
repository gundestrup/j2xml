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

        // $this->aliases['featured'] = 'SELECT IFNULL(f.ordering,0) FROM
        // #__content_frontpage f RIGHT JOIN #__content a ON f.content_id = a.id
        // WHERE a.id = ' . (int)$this->id;
        $this->aliases['featured'] = (string) $this->getDatabase()->getQuery()->clear()
            ->select('COALESCE(' . $this->getDatabase()->quoteName('f.ordering') . ', 0)')
            ->from($this->getDatabase()->quoteName('#__content_frontpage', 'f'))
            ->join('RIGHT',
                $this->getDatabase()->quoteName('#__content', 'a') . ' ON ' . $this->getDatabase()->quoteName('f.content_id') . ' = ' . $this->getDatabase()->quoteName('a.id'))
            ->where($this->getDatabase()->quoteName('a.id') . ' = ' . (int) $this->id);

        $this->aliases['featured_up'] = (string) $this->getDatabase()->getQuery()->clear()
            ->select($this->getDatabase()->quoteName('f.featured_up'))
            ->from($this->getDatabase()->quoteName('#__content_frontpage', 'f'))
            ->join('RIGHT',
                $this->getDatabase()->quoteName('#__content', 'a') . ' ON ' . $this->getDatabase()->quoteName('f.content_id') . ' = ' . $this->getDatabase()->quoteName('a.id'))
            ->where($this->getDatabase()->quoteName('a.id') . ' = ' . (int) $this->id);

        $this->aliases['featured_down'] = (string) $this->getDatabase()->getQuery()->clear()
            ->select($this->getDatabase()->quoteName('f.featured_down'))
            ->from($this->getDatabase()->quoteName('#__content_frontpage', 'f'))
            ->join('RIGHT',
                $this->getDatabase()->quoteName('#__content', 'a') . ' ON ' . $this->getDatabase()->quoteName('f.content_id') . ' = ' . $this->getDatabase()->quoteName('a.id'))
            ->where($this->getDatabase()->quoteName('a.id') . ' = ' . (int) $this->id);

        // $this->aliases['rating_sum'] = 'SELECT IFNULL(rating_sum,0) FROM
        // #__content_rating f RIGHT JOIN #__content a ON f.content_id = a.id
        // WHERE a.id = ' . (int)$this->id;
        $this->aliases['rating_sum'] = (string) $this->getDatabase()->getQuery()->clear()
            ->select('COALESCE(' . $this->getDatabase()->quoteName('rating_sum') . ', 0)')
            ->from($this->getDatabase()->quoteName('#__content_rating', 'f'))
            ->join('RIGHT',
                $this->getDatabase()->quoteName('#__content', 'a') . ' ON ' . $this->getDatabase()->quoteName('f.content_id') . ' = ' . $this->getDatabase()->quoteName('a.id'))
            ->where($this->getDatabase()->quoteName('a.id') . ' = ' . (int) $this->id);

        // $this->aliases['rating_count'] = 'SELECT IFNULL(rating_count,0) FROM
        // #__content_rating f RIGHT JOIN #__content a ON f.content_id = a.id
        // WHERE a.id = ' . (int)$this->id;
        $this->aliases['rating_count'] = (string) $this->getDatabase()->getQuery()->clear()
            ->select('COALESCE(' . $this->getDatabase()->quoteName('rating_count') . ', 0)')
            ->from($this->getDatabase()->quoteName('#__content_rating', 'f'))
            ->join('RIGHT',
                $this->getDatabase()->quoteName('#__content', 'a') . ' ON ' . $this->getDatabase()->quoteName('f.content_id') . ' = ' . $this->getDatabase()->quoteName('a.id'))
            ->where($this->getDatabase()->quoteName('a.id') . ' = ' . (int) $this->id);

        $slug = $this->alias ? ($this->id . ':' . $this->alias) : $this->id;

        // We need to make sure we are always using the site router, even if the language plugin is executed in admin app.
        $router = CMSApplication::getRouter('site');
        $url = $router->build(RouteHelper::getArticleRoute($slug, $this->catid, $this->language));

        $canonical = str_replace(\Joomla\CMS\Uri\Uri::base(true) . '/', \Joomla\CMS\Uri\Uri::root(), $url);
        // $this->aliases['canonical'] = 'SELECT \'' . $canonical . '\' FROM
        // DUAL';
        $serverType = $this->getDatabase()->getServerType();
        if ($serverType === 'sqlserver')
        {
            $this->aliases['canonical'] = (string) $this->getDatabase()->getQuery()->clear()
                ->select($this->getDatabase()->quote($canonical))
                ->from($this->getDatabase()->quoteName('DUAL'));
        }
        else
        {
            $this->aliases['canonical'] = (string) $this->getDatabase()->getQuery()->clear()->select($this->getDatabase()->quote($canonical));
        }

        // $this->aliases['tag']='SELECT t.path FROM #__tags t,
        // #__contentitem_tag_map m WHERE type_alias = "com_content.article"
        // AND
        // t.id = m.tag_id AND m.content_item_id = '. (int)$this->id;
        $this->aliases['tag'] = (string) $this->getDatabase()->getQuery()->clear()
            ->select($this->getDatabase()->quoteName('t.path'))
            ->from($this->getDatabase()->quoteName('#__tags', 't'))
            ->from($this->getDatabase()->quoteName('#__contentitem_tag_map', 'm'))
            ->where($this->getDatabase()->quoteName('type_alias') . ' = ' . $this->getDatabase()->quote('com_content.article'))
            ->where($this->getDatabase()->quoteName('t.id') . ' = ' . $this->getDatabase()->quoteName('m.tag_id'))
            ->where($this->getDatabase()->quoteName('m.content_item_id') . ' = ' . $this->getDatabase()->quote((string) $this->id));

        $this->buildFieldAliases();

        $query = $this->getDatabase()->getQuery()->clear();
        $this->aliases['association'] = (string) $query
            ->select($query->concatenate([$this->getDatabase()->quoteName('cc.path'), $this->getDatabase()->quoteName('c.alias')], '/'))
            ->from($this->getDatabase()->quoteName('#__associations', 'asso1'))
            ->join('INNER', $this->getDatabase()->quoteName('#__associations', 'asso2') . ' ON ' . $this->getDatabase()->quoteName('asso1.key') . ' = ' . $this->getDatabase()->quoteName('asso2.key'))
            ->join('INNER', $this->getDatabase()->quoteName('#__content', 'c') . ' ON ' . $this->getDatabase()->quoteName('asso2.id') . ' = ' . $this->getDatabase()->quoteName('c.id'))
            ->join('INNER', $this->getDatabase()->quoteName('#__categories', 'cc') . ' ON ' . $this->getDatabase()->quoteName('c.catid') . ' = ' . $this->getDatabase()->quoteName('cc.id'))
            ->where([
                $this->getDatabase()->quoteName('asso1.id') . ' = ' . (int) $this->id,
                $this->getDatabase()->quoteName('asso1.context') . ' = ' . $this->getDatabase()->quote('com_content.item'),
                $this->getDatabase()->quoteName('asso2.id') . ' <> ' . (int) $this->id]);

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
        if (($keepFrontpage == 0) || ($data['featured'] == 0))
        {
            $query = "DELETE FROM #__content_frontpage WHERE content_id = " . $itemId;
        }
        else
        {
            // Use query builder for cross-database compatibility (MySQL + PostgreSQL)
            $query = $db->getQuery()->clear()
                ->insert($db->quoteName('#__content_frontpage'))
                ->columns([$db->quoteName('content_id'), $db->quoteName('ordering')])
                ->values($itemId . ',' . $data['ordering']);
            if (!is_null($data['featured_up']))
            {
                $query->columns($db->quoteName('featured_up'))
                    ->values($db->quote($data['featured_up']));
            }
            if (!is_null($data['featured_down']))
            {
                $query->columns($db->quoteName('featured_down'))
                    ->values($db->quote($data['featured_down']));
            }
        }
        $db->setQuery($query)->execute();
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

        if (empty($data['associations']))
        {
            $data['associations'] = [];
        }

        self::resolveAssociations($data, $db);
    }

    /**
     * Resolve the exported article associations to local article ids keyed
     * by language.
     *
     * @param array $data
     *          the article data being imported
     * @param \Joomla\Database\DatabaseInterface $db
     *          the database connector
     *
     * @return void
     */
    private static function resolveAssociations (&$data, $db)
    {
        if (isset($data['associationlist']))
        {
            $associations = $data['associationlist']['association'] ?? [];
            unset($data['associationlist']);
        }
        elseif (isset($data['association']))
        {
            $associations = [$data['association']];
            unset($data['association']);
        }
        else
        {
            return;
        }

        foreach ($associations as $association)
        {
            $id = self::getArticleId($association);
            if (!$id)
            {
                continue;
            }

            $tag = $db->setQuery($db->getQuery()->clear()
                ->select($db->quoteName('language'))
                ->from($db->quoteName('#__content'))
                ->where($db->quoteName('id') . ' = ' . $id))
                ->loadResult();
            if ($tag !== '*')
            {
                $data['associations'][$tag] = $id;
            }
        }
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

        if ($xml->xpath("//j2xml/content/id[text() = '" . $id . "']"))
        {
            return;
        }

        $db = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $item = new Content($db);
        if (!$item->load($id))
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

        if ($item->access > 6)
        {
            Viewlevel::export($item->access, $xml, $options);
        }

        if (isset($options['categories']) && $options['categories'] && ($item->catid > 0))
        {
            Category::export($item->catid, $xml, $options);
        }

        if (isset($options['tags']) && $options['tags'])
        {
            $htags = new \Joomla\CMS\Helper\TagsHelper();
            $itemtags = $htags->getItemTags('com_content.article', $id);
            foreach ($itemtags as $itemtag)
            {
                Tag::export($itemtag->tag_id, $xml, $options);
            }
        }

        if (isset($options['fields']) && $options['fields'])
        {
            self::exportFields($id, $xml, $options, $db);
        }

        $doc = dom_import_simplexml($xml)->ownerDocument;
        $fragment = $doc->createDocumentFragment();

        $fragment->appendXML($item->toXML());
        $doc->documentElement->appendChild($fragment);

        if (isset($options['users']) && $options['users'])
        {
            if ($item->created_by)
            {
                User::export($item->created_by, $xml, $options);
            }
            if ($item->modified_by)
            {
                User::export($item->modified_by, $xml, $options);
            }
        }

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

        $imgs = json_decode($item->images);
        if ($imgs)
        {
            if (isset($imgs->image_fulltext))
            {
                Image::export($imgs->image_fulltext, $xml, $options);
            }

            if (isset($imgs->image_intro))
            {
                Image::export($imgs->image_intro, $xml, $options);
            }
        }

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

        $query = $db->getQuery()->clear()
            ->update($db->quoteName('#__content'))
            ->set($db->quoteName('id') . ' = ' . $newid)
            ->where($db->quoteName('id') . ' = ' . $id);
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
        $db->setQuery($query)->execute();

        // Asset
        $query = $db->getQuery()->clear()
            ->update($db->quoteName('#__assets'))
            ->set($db->quoteName('name') . ' = ' . $db->quote($context . '.' . $newid))
            ->where($db->quoteName('name') . ' = ' . $db->quote($context . '.' . $id));
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
        $db->setQuery($query)->execute();

        // Workflow
        $query = $db->getQuery()->clear()
            ->update($db->quoteName('#__workflow_associations'))
            ->set($db->quoteName('item_id') . ' = ' . $newid)
            ->where($db->quoteName('item_id') . ' = ' . $id)
            ->where($db->quoteName('extension') . ' = ' . $db->quote($context));
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
        $db->setQuery($query)->execute();

        // Field
        $query = $db->getQuery()->clear()
            ->update($db->quoteName('#__fields_values'))
            ->set($db->quoteName('item_id') . ' = ' . $newid)
            ->where($db->quoteName('item_id') . ' = ' . $id);
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
        $db->setQuery($query)->execute();

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

        $query = $db->getQuery()->clear()
            ->update($db->quoteName('#__menu'))
            ->set($db->quoteName('link') . ' = ' . $db->quote('index.php?option=' . $option . '&view=' . $view . '&id=' . $newid))
            ->where($db->quoteName('link') . ' = ' . $db->quote('index.php?option=' . $option . '&view=' . $view . '&id=' . $id))
            ->where($db->quoteName('component_id') . ' = ' . $componentId);
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
        $db->setQuery($query)->execute();

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
                $query = $db->getQuery()->clear()
                    ->update($db->quoteName('#__associations'))
                    ->set($db->quoteName('id') . ' = ' . $newid)
                    ->where($db->quoteName('id') . ' = ' . $id)
                    ->where($db->quoteName('key') . ' = ' . $db->quote($key))
                    ->where($db->quoteName('context') . ' = ' . $db->quote($contextLanguage));
                \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
                $db->setQuery($query)->execute();

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

                $query = $db->getQuery()->clear()
                    ->update($db->quoteName('#__associations'))
                    ->set($db->quoteName('key') . ' = ' . $db->quote($newkey))
                    ->where($db->quoteName('key') . ' = ' . $db->quote($key))
                    ->where($db->quoteName('context') . ' = ' . $db->quote($contextLanguage));
                \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
                $db->setQuery($query)->execute();
            }
        }

        // Tags
        $query = $db->getQuery()->clear()
            ->update($db->quoteName('#__contentitem_tag_map'))
            ->set($db->quoteName('content_item_id') . ' = ' . $newid)
            ->where($db->quoteName('content_item_id') . ' = ' . $id)
            ->where($db->quoteName('type_alias') . ' = ' . $db->quote($context));
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
        $db->setQuery($query)->execute();

        // Frontpage
        $query = $db->getQuery()->clear()
            ->update($db->quoteName('#__content_frontpage'))
            ->set($db->quoteName('content_id') . ' = ' . $newid)
            ->where($db->quoteName('content_id') . ' = ' . $id);
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
        $db->setQuery($query)->execute();

        // Rating
        $query = $db->getQuery()->clear()
            ->update($db->quoteName('#__content_rating'))
            ->set($db->quoteName('content_id') . ' = ' . $newid)
            ->where($db->quoteName('content_id') . ' = ' . $id);
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
        $db->setQuery($query)->execute();
    }
}
