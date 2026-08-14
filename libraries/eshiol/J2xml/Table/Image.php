<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @version     __DEPLOY_VERSION__
 * @since       18.8.310
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


/**
 *
 * Image Table
 *
 */
class Image
{

	/**
	 * Import data
	 *
	 * @param \SimpleXMLElement $xml
	 *			xml
	 * @param \JRegistry $params
	 *			@option int 'images' 1: Yes, if not exists; 2: Yes, overwrite
	 *			if exists
	 *			@option string 'context'
	 *
	 * @throws
	 * @return void
	 * @access public
	 *
	 * @since 18.8.310
	 */
	public static function import ($xml, &$params)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

		$import_images = $params->get('images', 0);
		if ($import_images == 0)
		{
			return;
		}

		foreach ($xml->img as $image)
		{
			$src = JPATH_SITE . '/' . urldecode(html_entity_decode($image['src'], ENT_QUOTES, 'UTF-8'));
			$data = $image;
			if (!\Joomla\Filesystem\File::exists($src) || ($import_images == 2))
			{
				// many thx to Stefanos Tzigiannis
				$folder = dirname($src);
				if (!\Joomla\Filesystem\Folder::exists($folder))
				{
					if (\Joomla\Filesystem\Folder::create($folder))
					{
						\Joomla\CMS\Log\Log::add(
								new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_FOLDER_WAS_SUCCESSFULLY_CREATED', $folder), \Joomla\CMS\Log\Log::INFO, 'lib_j2xml'));
					}
					else
					{
						\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_ERROR_CREATING_FOLDER', $folder), \Joomla\CMS\Log\Log::ERROR, 'lib_j2xml'));
						break;
					}
				}
				if (\Joomla\Filesystem\File::write($src, base64_decode($data)))
				{
					\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_IMAGE_IMPORTED', $image['src']), \Joomla\CMS\Log\Log::INFO, 'lib_j2xml'));
				}
				else
				{
					\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('LIB_J2XML_MSG_IMAGE_NOT_IMPORTED', $image['src'], \Joomla\CMS\Language\Text::_('LIB_J2XML_MSG_UNKNOWN_ERROR')), \Joomla\CMS\Log\Log::ERROR, 'lib_j2xml'));
				}
			}
		}
	}

	/**
	 * Export data
	 *
	 * @param string $_image
	 *			the image to be exported
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
	public static function export ($image, &$xml, $options)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($image, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

		// Joomla 4
		$image = strtok($image, '#');

		if ($xml->xpath("//j2xml/img[@src = '" . htmlentities($image, ENT_QUOTES, "UTF-8") . "']"))
		{
			return;
		}

		$file_path = JPATH_SITE . '/' . urldecode($image);
		if (\Joomla\Filesystem\File::exists($file_path))
		{
			$img = $xml->addChild('img', base64_encode(file_get_contents($file_path)));
			$img->addAttribute('src', htmlentities($image, ENT_QUOTES, "UTF-8"));
		}
	}
}
