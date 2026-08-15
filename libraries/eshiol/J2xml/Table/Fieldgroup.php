<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @version     __DEPLOY_VERSION__
 * @since       19.2.323
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2026 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */
namespace eshiol\J2xml\Table;

use eshiol\J2xml\Table\Table;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Component\Fields\Administrator\Table\GroupTable;


/**
 *
 * Fieldgroup Table
 *
 */
class Fieldgroup extends Table
{

	/**
	 * Object constructor to set table and key fields.
	 * In most cases this will
	 * be overridden by child classes to explicitly set the table and key fields
	 * for a particular database table.
	 *
	 * @param
	 *			string Name of the table to model.
	 * @param
	 *			string Name of the primary key field in the table.
	 * @param
	 *			object JDatabase connector object.
	 *
	 * @since 19.2.323
	 */
	public function __construct (\Joomla\Database\DatabaseDriver $db)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

		parent::__construct('#__fields_groups', 'id', $db);
	}

	/**
	 * Import data
	 *
	 * @param \SimpleXMLElement $xml
	 *			xml
	 * @param \JRegistry $params
	 *			@option int 'fields' 0: No | 1: Yes, if not exists | 2: Yes,
	 *			overwrite if exists
	 *			@option string 'context'
	 *
	 * @throws
	 * @return void
	 * @access public
	 *
	 * @since 19.2.323
	 */
	public static function import ($xml, &$params, $db = null, $userId = null)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

		$import_fields = $params->get('fields', 0);
		if ($import_fields == 0)
			return;

		$context = $params->get('context');
		$db = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
		$nullDate = $db->getNullDate();
		$userid = $userId ?? \Joomla\CMS\Factory::getApplication()->getIdentity()->id;

		foreach ($xml->xpath("//j2xml/fieldgroup") as $record)
		{
			self::prepareData($record, $data, $params);

			$query = $db->getQuery(true)
				->select($db->quoteName('id'))
				->select($db->quoteName('title'))
				->from($db->quoteName('#__fields_groups'))
				->where($db->quoteName('context') . ' = ' . $db->quote($data['context']))
				->where($db->quoteName('title') . ' = ' . $db->quote($data['title']));
			$fieldgroup = $db->setQuery($query)->loadObject();

			if (!$fieldgroup )
			{
				$table = new GroupTable($db);

				$data['id'] = null;

				// @todo Trigger the onContentBeforeSave event.
				$table->bind($data);
				if ($table->store())
				{
					\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_FIELDGROUP_IMPORTED', $table->title), \Joomla\CMS\Log\Log::INFO, 'lib_j2xml'));
					// @todo Trigger the onContentAfterSave event.
				}
				else
				{
					\Joomla\CMS\Log\Log::add(
							new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_FIELDGROUP_NOT_IMPORTED', $data['title'], $table->getError()), \Joomla\CMS\Log\Log::ERROR,
									'lib_j2xml'));
				}
				$table = null;
			}
		}
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
	 * @since 19.2.323
	 */
	public static function export ($id, &$xml, $options, $db = null)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

		if ($xml->xpath("//j2xml/fieldgroup/id[text() = '" . $id . "']"))
		{
			return;
		}

		$db = $db ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
		$item = new Fieldgroup($db);
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
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Table::prepareData()
	 *
	 * @since 22.2.356
	 */
	public static function prepareData ($record, &$data, $params, $userId = null)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

		parent::prepareData($record, $data, $params);

		if (!isset($data['description']))
		{
			$data['description'] = '';
		}
		if (empty($data['modified']))
		{
			// From 4.0.0-2019-09-25.sql
			$data['modified'] = $data['created'] ?? null;
			$data['modified_by'] = $data['created_by'] ?? 0;
		}
	}
}