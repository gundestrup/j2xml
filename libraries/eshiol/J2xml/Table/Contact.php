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
 * @copyright   Copyright (C) 2010 - 2023 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */
namespace eshiol\J2xml\Table;
defined('JPATH_PLATFORM') or define('JPATH_PLATFORM', JPATH_LIBRARIES);

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
	 *			A database connector object
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

		// $this->_aliases['user_id']='SELECT username FROM #__users WHERE id =
		// '.(int)$this->user_id;
		$this->_aliases['user_id'] = (string) $this->_db->getQuery(true)
			->select($this->_db->quoteName('username'))
			->from($this->_db->quoteName('#__users'))
			->where($this->_db->quoteName('id') . ' = ' . (int) $this->user_id);

		// $this->_aliases['tag']='SELECT t.path FROM #__tags t,
		// #__contentitem_tag_map m WHERE type_alias = "com_contact.contact"
		// AND t.id = m.tag_id AND m.content_item_id = '. (int)$this->id;
		$this->_aliases['tag'] = (string) $this->_db->getQuery(true)
			->select($this->_db->quoteName('t.path'))
			->from($this->_db->quoteName('#__tags', 't'))
			->from($this->_db->quoteName('#__contentitem_tag_map', 'm'))
			->where($this->_db->quoteName('type_alias') . ' = ' . $this->_db->quote($this->type_alias))
			->where($this->_db->quoteName('t.id') . ' = ' . $this->_db->quoteName('m.tag_id'))
			->where($this->_db->quoteName('m.content_item_id') . ' = ' . $this->_db->quote((string) $this->id));

		$query = $this->_db->getQuery(true);
		$this->_aliases['association'] = (string) $query
			->select($query->concatenate(array($this->_db->quoteName('cc.path'), $this->_db->quoteName('c.alias')), '/'))
			->from($this->_db->quoteName('#__associations', 'asso1'))
			->join('INNER', $this->_db->quoteName('#__associations', 'asso2') . ' ON ' . $this->_db->quoteName('asso1.key') . ' = ' . $this->_db->quoteName('asso2.key'))
			->join('INNER', $this->_db->quoteName('#__contact_details', 'c') . ' ON ' . $this->_db->quoteName('asso2.id') . ' = ' . $this->_db->quoteName('c.id'))
			->join('INNER', $this->_db->quoteName('#__categories', 'cc') . ' ON ' . $this->_db->quoteName('c.catid') . ' = ' . $this->_db->quoteName('cc.id'))
			->where(array(
				$this->_db->quoteName('asso1.id') . ' = ' . (int) $this->id,
				$this->_db->quoteName('asso1.context') . ' = ' . $this->_db->quote('com_contact.item'),
				$this->_db->quoteName('asso2.id') . ' <> ' . (int) $this->id));

		return parent::toXML($mapKeysToText);
	}

	/**
	 * Export data
	 *
	 * @param int $id
	 *			the id of the item to be exported
	 * @param \SimpleXMLElement $xml
	 *			xml
	 * @param array $options
	 *
	 * @throws
	 * @return void
	 * @access public
	 *
	 * @since 18.8.310
	 */
	public static function export ($id, &$xml, $options)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

		if ($xml->xpath("//j2xml/contact/id[text() = '" . $id . "']"))
		{
			return;
		}

		$db = \Joomla\CMS\Factory::getDbo();
		$item = new Contact($db);
		if (!$item->load($id))
		{
			return;
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
			if (isset($item->image))
			{
				Image::export($item->image, $xml, $options);
			}
		}

		if (isset($options['tags']) && $options['tags'])
		{
			$htags = new \Joomla\CMS\Helper\TagsHelper();
			$itemtags = $htags->getItemTags('com_contact.contact', $id);
			foreach ($itemtags as $itemtag)
			{
				Tag::export($itemtag->tag_id, $xml, $options);
			}
		}

		if (isset($options['categories']) && $options['categories'] && ($item->catid > 0))
		{
			Category::export($item->catid, $xml, $options);
		}

		// associated contacts
		$query = $db->getQuery(true)
			->select($db->quoteName('c.id'))
			->from($db->quoteName('#__associations', 'asso1'))
			->join('INNER', $db->quoteName('#__associations', 'asso2') . ' ON ' . $db->quoteName('asso1.key') . ' = ' . $db->quoteName('asso2.key'))
			->join('INNER', $db->quoteName('#__contact_details', 'c') . ' ON ' . $db->quoteName('asso2.id') . ' = ' . $db->quoteName('c.id'))
			->join('INNER', $db->quoteName('#__categories', 'cc') . ' ON ' . $db->quoteName('c.catid') . ' = ' . $db->quoteName('cc.id'))
			->where(array(
				$db->quoteName('asso1.id') . ' = ' . (int) $id,
				$db->quoteName('asso1.context') . ' = ' . $db->quote('com_contact.item'),
				$db->quoteName('asso2.id') . ' <> ' . (int) $id));
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
	 *			xml
	 * @param \JRegistry $params
	 *			@option int 'users' 0: No | 1: Yes, if not exists | 2: Yes,
	 *			overwrite if exists
	 *			@option string 'context'
	 *
	 * @throws
	 * @return void
	 * @access public
	 *
	 * @since 19.2.322
	 */
	public static function import ($xml, &$params)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

		$import_contacts = $params->get('contacts', 0);
		if ($import_contacts == 0)
		{
			return;
		}

		$db     = \Joomla\CMS\Factory::getDbo();
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

			$contactId = $data['id'];
			unset($data['id']);

			$query = $db->getQuery(true)
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

				$table->bind($data);
				if ($table->store())
				{
					self::setAssociations($table->id, $table->language, $data['associations'], 'com_contact.item');

					\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_CONTACT_IMPORTED', $table->name), \Joomla\CMS\Log\Log::INFO, 'lib_j2xml'));
				}
				else
				{
					\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_CONTACT_NOT_IMPORTED', $data['name'], $table->getError()), \Joomla\CMS\Log\Log::ERROR, 'lib_j2xml'));
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
	public static function prepareData ($record, &$data, $params)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

		$db = \Joomla\CMS\Factory::getDbo();

		$params->set('extension', 'com_contact');
		parent::prepareData($record, $data, $params);

		if (empty($data['associations']))
		{
			$data['associations'] = array();
		}

		if (isset($data['associationlist']))
		{
			foreach ($data['associationlist']['association'] as $association)
			{
				$id = self::getContactId($association);
				if ($id)
				{
					$tag = $db->setQuery($db->getQuery(true)
						->select($db->quoteName('language'))
						->from($db->quoteName('#__contact_details'))
						->where($db->quoteName('id') . ' = ' . $id))
						->loadResult();
					if ($tag !== '*')
					{
						$data['associations'][$tag] = $id;
					}
				}
			}
			unset($data['associationlist']);
		}
		elseif (isset($data['association']))
		{
			$id = self::getContactId($data['association']);
			if ($id)
			{
				$tag = $db->setQuery($db->getQuery(true)
					->select($db->quoteName('language'))
					->from($db->quoteName('#__contact_details'))
					->where($db->quoteName('id') . ' = ' . $id))
					->loadResult();
				if ($tag !== '*')
				{
					$data['associations'][$tag] = $id;
				}
			}
			unset($data['association']);
		}

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
