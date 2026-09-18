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

use eshiol\J2xml\Table\Image;
use eshiol\J2xml\Table\Table;
use eshiol\J2xml\Table\Tag;
use eshiol\J2xml\Table\User;
use eshiol\J2xml\Table\Viewlevel;

/**
 *
 * Category Table
 *
 */
class Category extends Table
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
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        parent::__construct('#__categories', 'id', $db);
    }

    /**
     * Export item list to xml
     *
     * @access public
     */
    function toXML ($mapKeysToText = false)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $this->buildTagAlias($this->extension . '.category');

        $query = $this->getDatabase()->getQuery()->clear();
        $this->aliases['association'] = (string) $query
            ->select('CASE WHEN ' .  $this->getDatabase()->quoteName('cc1.level') . ' = 1'
                . ' THEN ' . $this->getDatabase()->quoteName('cc1.alias')
                . ' ELSE ' . $query->concatenate([$this->getDatabase()->quoteName('cc2.path'), $this->getDatabase()->quoteName('cc1.alias')], '/')
                . ' END')
            ->from($this->getDatabase()->quoteName('#__associations', 'asso1'))
            ->join('INNER', $this->getDatabase()->quoteName('#__associations', 'asso2') . ' ON ' . $this->getDatabase()->quoteName('asso1.key') . ' = ' . $this->getDatabase()->quoteName('asso2.key'))
            ->join('INNER', $this->getDatabase()->quoteName('#__categories', 'cc1') . ' ON ' . $this->getDatabase()->quoteName('asso2.id') . ' = ' . $this->getDatabase()->quoteName('cc1.id'))
            ->join('INNER', $this->getDatabase()->quoteName('#__categories', 'cc2') . ' ON ' . $this->getDatabase()->quoteName('cc1.parent_id') . ' = ' . $this->getDatabase()->quoteName('cc2.id'))
            ->where([
                $this->getDatabase()->quoteName('asso1.id') . ' = ' . (int) $this->id,
                $this->getDatabase()->quoteName('asso1.context') . ' = ' . $this->getDatabase()->quote('com_categories.item'),
                $this->getDatabase()->quoteName('asso2.id') . ' <> ' . (int) $this->id]);

        return parent::toXML($mapKeysToText);
    }

    /**
     * Import data
     *
     * @param \SimpleXMLElement $xml
     *          xml
     * @param \JRegistry $params
     *          @option int 'fields' 0: No | 1: Yes, if not exists | 2: Yes,
     *          overwrite if exists
     *          @option string 'context'
     *
     * @throws
     * @return void
     * @access public
     *
     * @since 18.8.310
     */
    public static function import ($xml, &$params, $db = null, $userId = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $import_categories = $params->get('categories', 0);
        if ($import_categories == 0)
            return;

        $extension = $params->get('extension');
        if (!$extension)
            return;

        \Joomla\CMS\Factory::getApplication()->getLanguage()->load('com_users', JPATH_ADMINISTRATOR);
        $db = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        $keep_id = $params->get('keep_id', 0);
        if ($keep_id)
        {
            $autoincrement = 0;
            $maxid = $db->setQuery($db->getQuery()->clear()
                ->select('MAX(' . $db->quoteName('id') . ')')
                ->from($db->quoteName('#__categories')))
                ->loadResult();
        }

        foreach ($xml->xpath("//j2xml/category[not(title = '') and (extension = '{$extension}')]") as $record)
        {
            self::prepareData($record, $data, $params);

            $alias = $data['alias']; // =
                                     // JApplication::stringURLSafe($data['alias']);
            $id = $data['id'];
            $path = $data['path'];

            $i = strrpos($path, '/');
            if ($i === false)
            {
                $data['parent_id'] = 1;
            }
            else
            {
                $data['parent_id'] = self::getCategoryId(substr($path, 0, $i), $data['extension']);
            }

            if ($data['parent_id'] === false)
            {
                \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_CATEGORY_NOT_IMPORTED', $data['title'], \Joomla\CMS\Language\Text::_('JLIB_DATABASE_ERROR_INVALID_PARENT_ID')), \Joomla\CMS\Log\Log::ERROR, 'lib_j2xml'));
            }
            else
            {
                $category = self::findCategory($db, $extension, $path, $keep_id ? $id : null);

                $table = new \Joomla\CMS\Table\Category($db);
                if (!$category || ($import_categories == 2))
                {
                    // $table = \Joomla\CMS\Table\Table::getInstance('category');

                    if (!$category && ($keep_id == 1))
                    {
                        $category = self::findCategory($db, $extension, $path);
                    }

                    if (!$category) // new category
                    {
                        $data['id'] = null;
                        /*
                         * if ($keep_access > 0) $data['access'] = $keep_access;
                         * if ($keep_state < 2) $data['published'] =
                         * $keep_state;
                         * if (!$keep_attribs) $data['params'] =
                         * '{"category_layout":"","image":""}';
                         */
                        $table->setLocation($data['parent_id'], 'last-child');
                    }
                    else // category already exists
                    {
                        $data['id'] = $category->id;
                        $table->load($data['id']);
                    }

                    $table->bind($data);

                    if (isset($data['tags']))
                    {
                        $table->newTags = Tag::convertPathsToIds($data['tags']);
                    }

                    if ($table->store())
                    {
                        if (!$category && ($keep_id == 1) && ($id > 1))
                        {
                            self::restoreCategoryId($db, $table, $data['extension'], $id, $autoincrement);
                        }
                        // Rebuild the tree path.
                        $table->rebuildPath();

                        self::setAssociations($table->id, $table->language, $data['associations'], 'com_categories.item');

                        if ($keep_id && ($id > 0) && ($id != $table->id))
                        {
                            \Joomla\CMS\Log\Log::add(
                                    new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_CATEGORY_ID_PRESENT', $table->title, $id, $table->id), \Joomla\CMS\Log\Log::WARNING,
                                            'lib_j2xml'));
                        }
                        elseif (empty($data['original_id']))
                        {
                            \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_CATEGORY_IMPORTED', $table->title), \Joomla\CMS\Log\Log::INFO, 'lib_j2xml'));
                        }
                        else
                        {
                            \Joomla\CMS\Log\Log::add(
                                    new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_CATEGORY_ID_PRESENT', $table->title, $data['original_id'], $table->id), \Joomla\CMS\Log\Log::WARNING,
                                            'lib_j2xml'));
                        }
                    }
                    else
                    {
                        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_CATEGORY_NOT_IMPORTED', $data['title'], $table->getError()), \Joomla\CMS\Log\Log::ERROR, 'lib_j2xml'));
                    }
                    $table = null;
                }
            }
            if ($keep_id && ($autoincrement > $maxid))
            {
                self::resetAutoIncrement($db, $autoincrement, '#__categories', '#__categories_id_seq');
                $maxid = $autoincrement;
            }
        }
    }

    /**
     * Find a category by extension and path, optionally restricted to a
     * specific id (keep_id imports).
     *
     * @param \Joomla\Database\DatabaseInterface $db
     *          the database connector
     * @param string $extension
     *          the category extension
     * @param string $path
     *          the category path
     * @param int|null $id
     *          restrict to this id when set
     *
     * @return mixed the category row if found, otherwise null/false
     */
    private static function findCategory ($db, $extension, $path, $id = null)
    {
        $query = $db->getQuery()->clear()
            ->select([
                $db->quoteName('id'),
                $db->quoteName('title'),
                $db->quoteName('path')
        ])
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote($extension))
            ->where($db->quoteName('path') . ' = ' . $db->quote($path));
        if (!is_null($id))
        {
            $query->where($db->quoteName('id') . ' = ' . $id);
        }
        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Restore the exported category id on the saved row when keep_id is
     * enabled, and rename the matching asset.
     *
     * @param \Joomla\Database\DatabaseInterface $db
     *          the database connector
     * @param \Joomla\CMS\Table\Category $table
     *          the saved category
     * @param string $extension
     *          the category extension
     * @param int $id
     *          the exported category id
     * @param int $autoincrement
     *          the highest category id assigned so far
     *
     * @return void
     */
    private static function restoreCategoryId ($db, $table, $extension, $id, &$autoincrement)
    {
        try
        {
            $query = $db->getQuery()->clear()
                ->update($db->quoteName('#__categories'))
                ->set($db->quoteName('id') . ' = ' . $id)
                ->where($db->quoteName('id') . ' = ' . $table->id);
            $db->setQuery($query)->execute();
            $table->id = $id;

            $query = $db->getQuery()->clear()
                ->update($db->quoteName('#__assets'))
                ->set($db->quoteName('name') . ' = ' . $db->quote($extension . '.category.' . $id))
                ->where($db->quoteName('id') . ' = ' . $table->asset_id);
            $db->setQuery($query)->execute();

            if ($id >= $autoincrement)
            {
                $autoincrement = $id + 1;
            }
        }
        catch (\Exception $ex)
        {
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
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $item = static::loadExportItem('category', $id, $xml, $db);
        if (!$item)
        {
            return;
        }

        $allowed_extensions = [
                'com_content'
        ];
        if (in_array($item->extension, $allowed_extensions))
        {
            if (isset($options['content']) && $options['content'])
            {
                self::exportCategoryContent($db, $item, $id, $xml, $options);
            }
        }
        $options['content'] = 0;

        if ($item->parent_id > 1)
        {
            Category::export($item->parent_id, $xml, $options);
        }

        self::appendItemXml($item, $xml);
        self::exportItemUsers($item, $xml, $options, ['created_user_id', 'modified_user_id']);
        self::exportItemViewlevel($item->access, $xml, $options);

        if (isset($options['images']) && $options['images'])
        {
            self::exportCategoryImages($item, $xml, $options);
        }

        self::exportItemTags($item->extension . '.category', $id, $xml, $options);
    }

    /**
     * Export the content items belonging to a category (when the extension
     * supports it).
     *
     * @param \Joomla\Database\DatabaseInterface $db
     *          the database connector
     * @param \Joomla\CMS\Table\Category $item
     *          the category being exported
     * @param int $id
     *          the category id
     * @param \SimpleXMLElement $xml
     *          xml
     * @param array $options
     *          export options
     *
     * @return void
     */
    private static function exportCategoryContent ($db, $item, $id, &$xml, &$options)
    {
        $table = '#__' . substr($item->extension, 4);
        $extension = '\\eshiol\\J2xml\\Table\\' . ucfirst(substr($item->extension, 4));
        $query = $db->getQuery()->clear()
            ->select('id')
            ->from($table)
            ->where('catid = ' . $id);
        $db->setQuery($query);
        $ids_content = $db->loadColumn();
        $options['categories'] = 0;
        foreach ($ids_content as $id_content)
        {
            $extension::export($id_content, $xml, $options);
        }
    }

    /**
     * Export the images referenced by a category description and params.
     *
     * @param \Joomla\CMS\Table\Category $item
     *          the category being exported
     * @param \SimpleXMLElement $xml
     *          xml
     * @param array $options
     *          export options
     *
     * @return void
     */
    private static function exportCategoryImages ($item, &$xml, $options)
    {
        self::exportImagesFromText(html_entity_decode($item->description), $xml, $options);
        self::exportImagesFromJson($item->params, $xml, $options, ['image']);
    }

    /**
     *
     * {@inheritdoc}
     * @see Table::prepareData()
     *
     * @since 20.5.349
     */
    public static function prepareData ($record, &$data, $params, $userId = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        self::prepareAssociatedData($record, $data, $params, 'com_categories', static function ($association) use (&$data) {
            return self::getCategoryId($association, $data['extension']);
        }, '#__categories');
    }
}
