<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @version     __DEPLOY_VERSION__
 * @since       15.9.261
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
use eshiol\J2xml\Table\Tag;
use eshiol\J2xml\Table\User;
use Joomla\Component\Contact\Administrator\Table\ContactTable;

/**
 *
 * Contact Table
 *
 */
class Contact extends Table
{

    /**
     * Constructor
     *
     * @param \Joomla\Database\DatabaseDriver $db
     *          A database connector object
     *
     * @since 15.9.261
     */
    public function __construct (\Joomla\Database\DatabaseDriver $db)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        parent::__construct('#__contact_details', 'id', $db);

        $this->type_alias = 'com_contact.contact';
    }

    /**
     * Export item list to xml
     *
     * @access public
     * @since 15.9.261
     */
    function toXML ($mapKeysToText = false)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        // $this->aliases['user_id']='SELECT username FROM #__users WHERE id =
        // '.(int)$this->user_id;
        $this->aliases['user_id'] = (string) $this->getDatabase()->getQuery()->clear()
            ->select($this->getDatabase()->quoteName('username'))
            ->from($this->getDatabase()->quoteName('#__users'))
            ->where($this->getDatabase()->quoteName('id') . ' = ' . (int) $this->user_id);

        $this->buildTagAlias($this->type_alias);
        $this->buildAssociationAlias('#__contact_details', 'com_contact.item');

        return parent::toXML($mapKeysToText);
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

        $item = static::loadExportItem('contact', $id, $xml, $db);
        if (!$item)
        {
            return;
        }

        self::appendItemXml($item, $xml);
        self::exportItemUsers($item, $xml, $options);

        if (isset($options['images']) && $options['images'])
        {
            if (isset($item->image))
            {
                Image::export($item->image, $xml, $options);
            }
        }

        self::exportItemTags('com_contact.contact', $id, $xml, $options);
        self::exportItemCategory($item->catid, $xml, $options);

        // associated contacts
        $query = $item->buildAssociationQuery('#__contact_details', 'com_contact.item', $db->quoteName('c.id'));
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

        $ids_contact = $db->setQuery($query)->loadColumn();
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(print_r($ids_contact, true), \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
        foreach ($ids_contact as $id_contact)
        {
            Contact::export($id_contact, $xml, $options);
        }
    }

    /**
     * Import data
     *
     * @param \SimpleXMLElement $xml
     *          xml
     * @param \JRegistry $params
     *          @option int 'users' 0: No | 1: Yes, if not exists | 2: Yes,
     *          overwrite if exists
     *          @option string 'context'
     *
     * @throws
     * @return void
     * @access public
     *
     * @since 19.2.322
     */
    public static function import ($xml, &$params, $db = null, $userId = null)
    {
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

        $import_contacts = $params->get('contacts', 0);
        if ($import_contacts == 0)
        {
            return;
        }

        $db     = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $keepId = $params->get('keep_user_id', '0');

        $import_categories = $params->get('categories', 0);
        if ($import_categories)
        {
        $params->set('extension', 'com_contact');
        $params->def('com_contact_category_default', self::getCategoryId('uncategorised', 'com_contact'));
            Category::import($xml, $params);
        }

        foreach ($xml->xpath("//j2xml/contact[not(alias = '')]") as $record)
        {
            self::prepareData($record, $data, $params);
            \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(print_r($data, true), \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

            $contactId = $data['id'] ?? 0;
            unset($data['id']);

            $query = $db->getQuery()->clear()
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__contact_details'));
            if ($keepId)
            {
                $query->where($db->quoteName('id') . ' = ' . $db->quote($contactId));
            }
            else
            {
                $query->where($db->quoteName('alias') . ' = ' . $db->quote($data['alias']))
                    ->where($db->quoteName('catid') . ' = ' . $db->quote($data['catid']));
            }

            $data['id'] = $db->setQuery($query)->loadResult();

            if (!$data['id'] || ($import_contacts == 2))
            {
                $contactTableFile = JPATH_ADMINISTRATOR . '/components/com_contact/src/Table/ContactTable.php';
                require_once $contactTableFile;
                $table = new ContactTable($db);

                if ($data['id'])
                {
                    $table->load($data['id']);
                }
                else
                {
                    unset($data['id']);
                }
                if (!isset($data['params']))
                {
                    $data['params'] = '';
                }

                if (self::bindAndStore($table, $data, 'LIB_J2XML_MSG_CONTACT_NOT_IMPORTED', 'name'))
                {
                    self::setAssociations($table->id, $table->language, $data['associations'], 'com_contact.item');

                    \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_CONTACT_IMPORTED', $table->name), \Joomla\CMS\Log\Log::INFO, 'lib_j2xml'));
                }

                $table = null;
            }
        }
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

        self::prepareAssociatedData($record, $data, $params, 'com_contact', static function ($association) {
            return self::getContactId($association);
        }, '#__contact_details');

        // if user doesn't exist remove the link
        if (isset($data['user_id']))
        {
            $data['user_id'] = self::getUserId($data['user_id'], -1);
            if ($data['user_id'] == -1)
            {
                unset($data['user_id']);
            }
        }

        if (!isset($data['catid']))
        {
            $data['catid'] = $params->get('com_contact_category_default');
        }
        if (!isset($data['metadesc']))
        {
            $data['metadesc'] = '';
        }
        if (!isset($data['metadata']))
        {
            $data['metadata'] = '<![CDATA[{"robots":"","rights":""}]]>';
        }
    }
}
