<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @version     __DEPLOY_VERSION__
 * @since       22.1.355
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

/**
 *
 * Menutype Table
 *
 */
class Menutype extends \eshiol\J2XML\Table\Table
{

	/**
	 * Constructor
	 *
	 * @param
	 *        	object Database connector object
	 * @since 17.1.294
	 */
	function __construct (& $db)
	{
		parent::__construct('#__menu_types', 'id', $db);
	}

	/**
	 * Export menutype
	 *
	 * @param int $id
	 * @param SimpleXMLElement $xml
	 * @param array $options
	 *
	 * @return SimpleXMLElement
	 *
	 * @since 19.2.318
	 */
	public static function export ($id, &$xml, $options)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry('id: ' . $id, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry('options: ' . print_r($options, true), \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

		$db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
		$item = new Menutype($db);
		if (!$item->load($id))
		{
			return;
		}

		if ($xml->xpath("//j2xml/menutype/id[text() = '" . $item->id . "']"))
		{
			return;
		}

		/* export modules */
		$query = $db->getQuery(true)
			->select($db->qn('id'))
			->select($db->qn('params'))
			->from($db->qn('#__modules'))
			->where($db->qn('module') . ' = ' . $db->q('mod_menu'));
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		$modules = $db->setQuery($query)->loadObjectList();

		foreach ($modules as $module)
		{
			$params = new \Joomla\Registry\Registry($module->params);
			if ($params->get('menutype') == $item->menutype)
			{
				Module::export($module->id, $xml, $options);
			}
		}

		$doc = dom_import_simplexml($xml)->ownerDocument;
		$fragment = $doc->createDocumentFragment();

		$fragment->appendXML($item->toXML());
		$doc->documentElement->appendChild($fragment);

		/* export menus */
		$query = $db->getQuery(true)
			->select($db->qn('id'))
			->from($db->qn('#__menu'))
			->where($db->qn('menutype') . ' = ' . $db->q($item->menutype))
			->where($db->qn('parent_id') . ' = 1')
			-> // export only level 1
			                                       // ->order($db->qn('level')) //
			                                       // export all levels
		order($db->qn('lft'));
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
		$ids_menu = $db->setQuery($query)->loadColumn();

		foreach ($ids_menu as $id_menu)
		{
			Menu::export($id_menu, $xml, $options);
		}
	}

	/**
	 * importing menu types
	 *
	 * @param SimpleXMLElement $xml
	 * @param \JRegistry $params
	 *
	 * @since 19.2.318
	 */
	public static function import ($xml, &$params)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));

		$db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
		$import_menus = $params->get('import_menus', '1');

		foreach ($xml->xpath("//j2xml/menutype[not(title = '')]") as $record)
		{
			self::prepareData($record, $data, $params);

			$query = $db->getQuery(true)
				->select($db->qn([
					'id',
					'title'
			]))
				->from($db->qn('#__menu_types'))
				->where($db->qn('menutype') . '=' . $db->q($data['menutype']));
			\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'lib_j2xml'));
			$menutype = $db->setQuery($query)->loadResult();

			if (!$menutype || ($import_menus == 2))
			{
				$table = new MenuType($db);

				if (!$menutype)
				{ // new menutype
					$data['id'] = null;
				}
				else // menutype already exists
				{
					$data['id'] = $menutype->id;
					$table->load($data['id']);
				}

				// Trigger the onContentBeforeSave event.
				$table->bind($data);
				if ($table->store())
				{
					\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_MENUTYPE_IMPORTED', $table->title), \Joomla\CMS\Log\Log::INFO, 'lib_j2xml'));
					// Trigger the onContentAfterSave event.
				}
				else
				{
					\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_MENUTYPE_NOT_IMPORTED', $data['title'], $table->getError()), \Joomla\CMS\Log\Log::ERROR, 'lib_j2xml'));
				}
				$table = null;
			}
		}
	}
}
