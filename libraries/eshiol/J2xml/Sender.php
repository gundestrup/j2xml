<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2xml
 *
 * @version     __DEPLOY_VERSION__
 * @since       1.5.3beta3.38
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2026 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free and open source software licenses.
 */
namespace eshiol\J2xml;

// no direct access
defined('_JEXEC') or die;

use eshiol\J2xml\Messages;
use eshiol\J2xml\Version;

/**
 * Sender
 *
 * Sends J2XML data to a remote Joomla site via the REST API.
 *
 * @since  1.5.3beta3.38
 */
class Sender
{

	public static $codes = [
		'-1' => 'message',
		'message', // LIB_J2XML_MSG_ARTICLE_IMPORTED
		'notice', // LIB_J2XML_MSG_ARTICLE_NOT_IMPORTED
		'message', // LIB_J2XML_MSG_USER_IMPORTED
		'notice', // LIB_J2XML_MSG_USER_NOT_IMPORTED
		'notice', // 'message', // not used: LIB_J2XML_MSG_SECTION_IMPORTED
		'notice', // not used: LIB_J2XML_MSG_SECTION_NOT_IMPORTED
		6 => 'message', // LIB_J2XML_MSG_CATEGORY_IMPORTED
		'notice', // LIB_J2XML_MSG_CATEGORY_NOT_IMPORTED
		'message', // LIB_J2XML_MSG_FOLDER_WAS_SUCCESSFULLY_CREATED
		'notice', // LIB_J2XML_MSG_ERROR_CREATING_FOLDER
		'message', // LIB_J2XML_MSG_IMAGE_IMPORTED
		'notice', // LIB_J2XML_MSG_IMAGE_NOT_IMPORTED
		'message', // LIB_J2XML_MSG_WEBLINK_IMPORTED
		'notice', // LIB_J2XML_MSG_WEBLINK_NOT_IMPORTED
		'notice', // not used: LIB_J2XML_MSG_WEBLINKCAT_NOT_PRESENT
		15 => 'error', // LIB_J2XML_MSG_XMLRPC_NOT_SUPPORTED
		'notice', // LIB_J2XML_MSG_CATEGORY_ID_PRESENT 16
		'error', // LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED 17
		'error', // LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN 18
		'error', // JERROR_ALERTNOTAUTH 19
		'message', // LIB_J2XML_MSG_TAG_IMPORTED 20
		'notice', // LIB_J2XML_MSG_TAG_NOT_IMPORTED 21
		'message', // LIB_J2XML_MSG_CONTACT_IMPORTED 22
		'notice', // LIB_J2XML_MSG_CONTACT_NOT_IMPORTED 23
		'message', // LIB_J2XML_MSG_VIEWLEVEL_IMPORTED 24
		'notice', // LIB_J2XML_MSG_VIEWLEVEL_NOT_IMPORTED 25
		'message', // LIB_J2XML_MSG_BUTTON_IMPORTED 26
		'notice', // LIB_J2XML_MSG_BUTTON_NOT_IMPORTED 27
		'error', // LIB_J2XML_MSG_UNKNOWN_ERROR 28
		'warning', // LIB_J2XML_MSG_UNKNOWN_WARNING 29
		'notice', // LIB_J2XML_MSG_UNKNOWN_NOTICE 30
		'message', // LIB_J2XML_MSG_UNKNOWN_MESSAGE 31
		'notice', // LIB_J2XML_MSG_XMLRPC_DISABLED 32
		'message', // LIB_J2XML_MSG_MENUTYPE_IMPORTED 33
		'notice', // LIB_J2XML_MSG_MENUTYPE_NOT_IMPORTED 34
		'message', // LIB_J2XML_MSG_MENU_IMPORTED 35
		'notice', // LIB_J2XML_MSG_MENU_NOT_IMPORTED 36
		'notice', // LIB_J2XML_ERROR_COMPONENT_NOT_FOUND 37
		'message', // LIB_J2XML_MSG_MODULE_IMPORTED 38
		'notice', // LIB_J2XML_MSG_MODULE_NOT_IMPORTED 39
		'message', // LIB_J2XML_MSG_FIELD_IMPORTED 40
		'notice', // LIB_J2XML_MSG_FIELD_NOT_IMPORTED 41
		'message', // LIB_J2XML_MSG_USERNOTE_IMPORTED 42
		'notice', // LIB_J2XML_MSG_USERNOTE_NOT_IMPORTED 43
		'message', // LIB_J2XML_MSG_FIELDGROUP_IMPORTED 44
		'notice', // LIB_J2XML_MSG_FIELDGROUP_NOT_IMPORTED 45
		'notice' // LIB_J2XML_MSG_USER_SKIPPED 46
	];

	/**
	 * Send data to a remote Joomla site via the REST API.
	 *
	 * @param   \SimpleXMLElement  $xml     The J2XML data to send
	 * @param   array              $options  Options array (gzip, debug, etc.)
	 * @param   int                $sid     The remote server ID in #__j2xml_websites
	 *
	 * @return  void
	 *
	 * @since   1.5.3beta3.38
	 */
	public static function send($xml, $options, $sid)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

		$app = \Joomla\CMS\Factory::getApplication();

		$dom = new \DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($xml->asXML());
		$data = $dom->saveXML();

		if (!empty($options['gzip']))
		{
			$data = gzencode($data, 9);
		}

		$db = \Joomla\CMS\Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(['title', 'remote_url', 'token']))
			->from($db->quoteName('#__j2xml_websites'))
			->where($db->quoteName('state') . ' = 1')
			->where($db->quoteName('id') . ' = ' . (int) $sid);
		$db->setQuery($query);

		if (!($server = $db->loadAssoc()))
		{
			return;
		}

		if (empty($server['token']))
		{
			$app->enqueueMessage(
				$server['title'] . ': ' . \Joomla\CMS\Language\Text::_('LIB_J2XML_ERROR_NO_TOKEN'),
				'error'
			);
			return;
		}

		// Build the REST API endpoint URL.
		$url = $server['remote_url'];

		if (strpos($url, '://') === false)
		{
			$url = 'https://' . $url;
		}

		$url = rtrim($url, '/') . '/api/index.php/v1/j2xml/import';

		// Prepare HTTP request.
		$headers = [
			'Content-Type: application/xml',
			'X-Joomla-Token: ' . $server['token'],
		];

		if (!empty($options['gzip']))
		{
			$headers[] = 'Content-Encoding: gzip';
		}

		$context = stream_context_create([
			'http' => [
				'method'        => 'POST',
				'header'        => implode("\r\n", $headers),
				'content'       => $data,
				'user_agent'    => Version::$PRODUCT . ' ' . Version::getFullVersion(),
				'timeout'       => 60,
				'ignore_errors' => true,
			],
			'ssl' => [
				'verify_peer'      => false,
				'verify_peer_name' => false,
			],
		]);

		$response = @file_get_contents($url, false, $context);

		if ($response === false)
		{
			$app->enqueueMessage(
				$server['title'] . ': ' . \Joomla\CMS\Language\Text::_('LIB_J2XML_ERROR_CONNECTION_FAILED'),
				'error'
			);
			return;
		}

		// Parse the HTTP response code from the $http_response_header array.
		$httpCode = 0;
		if (isset($http_response_header) && is_array($http_response_header))
		{
			if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches))
			{
				$httpCode = (int) $matches[1];
			}
		}

		if ($httpCode === 401 || $httpCode === 403)
		{
			$app->enqueueMessage(
				$server['title'] . ': ' . \Joomla\CMS\Language\Text::_('JERROR_ALERTNOTAUTH'),
				'error'
			);
			return;
		}

		if ($httpCode >= 400)
		{
			// Try to extract error detail from the JSON response.
			$decoded = json_decode($response, true);
			$detail = '';

			if (is_array($decoded))
			{
				if (isset($decoded['errors']['detail']))
				{
					$detail = $decoded['errors']['detail'];
				}
				elseif (isset($decoded['errors'][0]['title']))
				{
					$detail = $decoded['errors'][0]['title'];
				}
				elseif (isset($decoded['message']))
				{
					$detail = $decoded['message'];
				}
			}

			$app->enqueueMessage(
				$server['title'] . ': ' . ($detail ?: \Joomla\CMS\Language\Text::_('LIB_J2XML_ERROR_UNKNOWN')),
				'error'
			);
			return;
		}

		// Success — parse the JSON response for import messages.
		$decoded = json_decode($response, true);

		if (is_array($decoded) && isset($decoded['data']) && is_array($decoded['data']))
		{
			foreach ($decoded['data'] as $msg)
			{
				$code    = isset($msg['code']) ? $msg['code'] : -1;
				$message = isset($msg['message']) ? $msg['message'] : '';
				$type    = isset(self::$codes[$code]) ? self::$codes[$code] : 'notice';

				if (isset(Messages::$messages[$code]) && isset($msg['strings']))
				{
					$message = vsprintf(\Joomla\CMS\Language\Text::_(Messages::$messages[$code]), $msg['strings']);
				}

				$app->enqueueMessage($server['title'] . ': ' . $message, $type);
			}
		}
		elseif (is_array($decoded) && isset($decoded['messages']) && is_array($decoded['messages']))
		{
			// Joomla message queue format.
			foreach ($decoded['messages'] as $type => $messages)
			{
				foreach ((array) $messages as $message)
				{
					$app->enqueueMessage($server['title'] . ': ' . $message, $type);
				}
			}
		}
	}
}
