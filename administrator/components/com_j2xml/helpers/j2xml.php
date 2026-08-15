<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
 * @since       2.5.85
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

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\Language\Text;

/**
 * Content component helper.
 */
class J2xmlHelper
{

	public static $extension = 'com_j2xml';

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return \stdClass
	 * @since 2.5
	 */
	public static function getActions ()
	{
		$user = \Joomla\CMS\Factory::getApplication()->getIdentity();
		$result = new \stdClass();

		$assetName = 'com_content';

		$actions = [
				'core.admin',
				'core.manage',
				'core.create',
				'core.edit',
				'core.edit.own',
				'core.edit.state',
				'core.delete'
		];

		foreach ($actions as $action)
		{
			$result->{$action} = $user->authorise($action, $assetName);
		}

		return $result;
	}

	/**
	 *
	 * @return boolean
	 * @since 2.5
	 */
	public static function updateReset ()
	{
		return true;
	}

	public static function copyright ()
	{
		if ($xml = simplexml_load_file(JPATH_ADMINISTRATOR . "/components/com_j2xml/j2xml.xml", 'SimpleXMLElement', LIBXML_NONET))
		{
			return '<div class="clearfix"> </div>' . '<div style="text-align:center;font-size:xx-small">' . Text::_($xml->name) . ' ' . $xml->version .
					 ' ' . str_replace('(C)', '&copy;', $xml->copyright) . '</div>';
		}
	}

	/**
	 * Configure the Linkbar.
	 *
	 * @param
	 *        	string The name of the active view.
	 */
	public static function addSubmenu ($vName = 'cpanel')
	{
		Sidebar::addEntry(Text::_('COM_J2XML_SUBMENU_CPANEL'), 'index.php?option=com_j2xml&view=cpanel', $vName == 'cpanel');
		Sidebar::addEntry(Text::_('COM_J2XML_SUBMENU_WEBSITES'), 'index.php?option=com_j2xml&view=websites', $vName == 'websites');
	}

	/**
	 * Removes invalid XML
	 *
	 * @access public
	 * @param string $value
	 * @return string
	 */
	public static function stripInvalidXml ($value)
	{
		$ret = "";
		if (empty($value))
		{
			return $ret;
		}

		$length = strlen($value);
		for ($i = 0; $i < $length; $i ++)
		{
			$current = ord($value[$i]);
			if (($current == 0x9) || ($current == 0xA) || ($current == 0xD) || (($current >= 0x20) && ($current <= 0xD7FF)) ||
					 (($current >= 0xE000) && ($current <= 0xFFFD)) || (($current >= 0x10000) && ($current <= 0x10FFFF)))
			{
				$ret .= chr($current);
			}
			else
			{
				$ret .= " ";
			}
		}
		return $ret;
	}
}
