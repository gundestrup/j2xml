<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @version     __DEPLOY_VERSION__
 * @since       14.8.240
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
use eshiol\J2xml\Table\Image;
use eshiol\J2xml\Table\Table;
use eshiol\J2xml\Table\User;
use Joomla\Component\Users\Administrator\Table\NoteTable;

/**
 *
 * Usernote Table
 *
 */
class Usernote extends \eshiol\J2xml\Table\Table
{

    /**
     * Constructor
     *
     * @param \Joomla\Database\DatabaseDriver $db
     *          A database connector object
     *
     * @since 15.3.248
     */
    public function __construct (\Joomla\Database\DatabaseDriver $db)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        parent::__construct('#__user_notes', 'id', $db);
    }

    /**
     *
     * {@inheritdoc}
     * @see Table::export()
     */
    public static function export ($id, &$xml, $options, $db = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $item = static::loadExportItem('usernote', $id, $xml, $db);
        if (!$item)
        {
            return;
        }

        self::appendItemXml($item, $xml);
        self::exportItemUsers($item, $xml, $options, ['created_user_id', 'modified_user_id']);

        if (isset($options['images']) && $options['images'])
        {
            self::exportImagesFromText(html_entity_decode($item->body), $xml, $options);
        }

        self::exportItemCategory($item->catid, $xml, $options);
    }

    /**
     *
     * {@inheritdoc}
     * @see Table::import()
     */
    public static function import ($xml, &$params, $db = null, $userId = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $import_usernotes = $params->get('usernotes', 0);
        if ($import_usernotes == 0)
        {
            return;
        }

        $params->set('extension', 'com_users');
        $import_categories = $params->get('categories');
        if ($import_categories)
        {
            Category::import($xml, $params);
        }
/*
        $users = json_decode($params->get('imported_users', '[]'), true);
        foreach ($users as $user_id => $overwrite)
        {
            $username = \Joomla\CMS\Factory::getContainer()->get(\Joomla\CMS\User\UserFactoryInterface::class)->loadUserById($user_id)->username;
            $path = "//j2xml/usernote[user_id='{$username}']";
*/
            $path = "//j2xml/usernote[user_id!='']";
            foreach ($xml->xpath($path) as $record)
            {
                self::prepareData($record, $data, $params);

                unset($data['id']);

                $db = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $table = new NoteTable($db);

//              if (!$overwrite)
//              {
                    $table->load(
                        [
                            'user_id' => $data['user_id'],
                            'catid' => $data['catid'],
                            'subject' => $data['subject']
                        ]);
//              }

                $table->bind($data);
                self::storeImportedTable($table, 'LIB_J2XML_MSG_USERNOTE_IMPORTED', 'LIB_J2XML_MSG_USERNOTE_NOT_IMPORTED', $data['subject']);
            }
/*
        }
*/
    }

    /**
     *
     * {@inheritdoc}
     * @see Table::prepareData()
     */
    public static function prepareData ($record, &$data, $params, $userId = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $params->set('extension', 'com_users');
        parent::prepareData($record, $data, $params);

        if (isset($data['user_id']))
        {
            $data['user_id'] = self::getUserId($data['user_id']);
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see Table::toXML()
     */
    function toXML ($mapKeysToText = false)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $this->aliases['user_id'] = (string) $this->getDatabase()->getQuery()->clear()
            ->select($this->getDatabase()->quoteName('username'))
            ->from($this->getDatabase()->quoteName('#__users'))
            ->where($this->getDatabase()->quoteName('id') . ' = ' . (int) $this->user_id);

        return parent::toXML($mapKeysToText);
    }
}
