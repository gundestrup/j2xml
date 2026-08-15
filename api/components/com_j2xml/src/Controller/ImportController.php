<?php
/**
 * @package     Joomla.API
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2026 Helios Ciancio. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free and open source software licenses.
 */

namespace Joomla\Component\J2xml\Api\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Log\LogEntry;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Registry\Registry;

/**
 * J2XML API import controller.
 *
 * Handles POST /api/index.php/v1/j2xml/import
 *
 * The request body is a J2XML XML document (Content-Type: application/xml).
 * Import options may be passed as a JSON string in the "options" query
 * parameter.
 *
 * Authentication is handled by Joomla's built-in token-based API
 * authentication (X-Joomla-Token header). The authenticated user must
 * have core.admin permission on com_j2xml.
 *
 * @since   __DEPLOY_VERSION__
 */
class ImportController extends BaseController
{
	/**
	 * Import J2XML content from the request body.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function import(): void
	{
		Log::add(new LogEntry(__METHOD__, Log::DEBUG, 'com_j2xml'));

		$app   = Factory::getApplication();
		$user  = Factory::getApplication()->getIdentity();

		// Check authorisation — the API token plugin already authenticated
		// the user, but we still need the core.admin ACL on com_j2xml.
		if (!$user->authorise('core.admin', 'com_j2xml'))
		{
			http_response_code(401);
			echo new JsonResponse(null, Text::_('JGLOBAL_AUTH_ACCESS_DENIED'), true);
			$app->close();
		}

		// Load language files.
		$lang = $app->getLanguage();
		$lang->load('lib_j2xml', JPATH_SITE, null, false, false)
			|| $lang->load('lib_j2xml', JPATH_SITE, null, true);

		// Read the raw XML body.
		$raw = file_get_contents('php://input');

		if (empty($raw))
		{
			http_response_code(400);
			echo new JsonResponse(null, Text::_('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), true);
			$app->close();
		}

		// Handle gzip-compressed body.
		$decoded = $this->gzdecode($raw);
		if ($decoded !== false)
		{
			$raw = $decoded;
		}

		// If the body is JSON, extract the XML from the "data" field.
		$contentType = $app->getInput()->server->getString('HTTP_CONTENT_TYPE', '');
		if (str_contains($contentType, 'application/json'))
		{
			$json = json_decode($raw, true);
			if (is_array($json) && isset($json['data']))
			{
				$raw = $json['data'];
				// Options may also be in the JSON body.
				if (isset($json['options']) && is_array($json['options']))
				{
					$app->getInput()->set('options', json_encode($json['options']));
				}
			}
		}

		// Extract the XML declaration and parse.
		$raw = strstr($raw, '<?xml version="1.0" ');

		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_PARSEHUGE | LIBXML_NONET);

		if (!$xml)
		{
			http_response_code(400);
			$errors = libxml_get_errors();
			$msg = [];
			foreach ($errors as $error)
			{
				$msg[] = $error->code . ' - ' . $error->message;
			}
			libxml_clear_errors();
			echo new JsonResponse(null, implode("\n", $msg), true);
			$app->close();
		}

		if (strtoupper($xml->getName()) !== 'J2XML' || !isset($xml['version']))
		{
			http_response_code(400);
			echo new JsonResponse(null, Text::_('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), true);
			$app->close();
		}

		// Build import options from the "options" query param, falling
		// back to the component's global configuration.
		$optionsParam = $app->getInput()->getString('options', '{}');
		$fparams = new Registry($optionsParam);

		$cparams = ComponentHelper::getParams('com_j2xml');

		$params = new Registry();
		$params->set('categories', $fparams->get('categories', $cparams->get('categories', 1)));
		$params->set('contacts', $fparams->get('contacts', $cparams->get('contacts', 1)));
		$params->set('content', $fparams->get('content', $cparams->get('content', 1)));
		$params->set('fields', $fparams->get('fields', $cparams->get('fields', 1)));
		$params->set('images', $fparams->get('images', $cparams->get('images', 1)));
		$params->set('menus', $fparams->get('menus', $cparams->get('menus', 1)));
		$params->set('modules', $fparams->get('modules', $cparams->get('modules', 1)));
		$params->set('keep_category', $fparams->get('keep_category', $cparams->get('keep_category', 1)));
		if ($params->get('keep_category') == 2)
		{
			$params->set('content_category_forceto', $cparams->get('category'));
		}
		$params->set('keep_id', $fparams->get('keep_id', $cparams->get('keep_id', 0)));
		$params->set('keep_user_id', $fparams->get('keep_user_id', $cparams->get('keep_user_id', 0)));
		$params->set('tags', $fparams->get('tags', $cparams->get('tags', 1)));
		$params->set('superusers', $fparams->get('superusers', $cparams->get('superusers', 0)));
		$params->set('usernotes', $fparams->get('usernotes', $cparams->get('usernotes', 0)));
		$params->set('users', $fparams->get('users', $cparams->get('users', 1)));
		$params->set('viewlevels', $fparams->get('viewlevels', $cparams->get('viewlevels', 1)));
		$params->set('weblinks', $fparams->get('weblinks', $cparams->get('weblinks', 0)));
		$params->set('keep_data', $fparams->get('keep_data', $cparams->get('keep_data', 0)));
		$params->set('version', (string) $xml['version']);

		// Fire onContentBeforeImport event.
		PluginHelper::importPlugin('j2xml');
		$app->triggerEvent('onContentBeforeImport', ['com_j2xml.api', &$xml, $params]);

		// Run the import.
		$importer = class_exists('eshiol\\J2xmlpro\\Importer')
			? new \eshiol\J2xmlpro\Importer()
			: new \eshiol\J2xml\Importer();

		try
		{
			$importer->import($xml, $params);
		}
		catch (\Throwable $e)
		{
			// The import may throw during post-save workflow hooks
			// (e.g. ArticleModel::getForm() fails in the API context).
			// The data is typically already saved by this point, so we
			// log the error and continue to return a response.
			Log::add(new LogEntry('Import error: ' . $e->getMessage(), Log::WARNING, 'com_j2xml'));
		}

		// Collect the message queue and return as JSON.
		$messages = $app->getMessageQueue();
		$items = [];
		foreach ($messages as $msg)
		{
			$items[] = [
				'type'    => $msg['type'],
				'message' => $msg['message'],
			];
		}

		echo new JsonResponse($items);
		$app->close();
	}

	/**
	 * Decode a gzip-encoded string.
	 *
	 * @param   string  $data  Raw data
	 *
	 * @return  string|false  Decoded data or false on failure
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	private function gzdecode(string $data)
	{
		if (strlen($data) < 18 || substr($data, 0, 2) !== "\x1f\x8b")
		{
			return false;
		}

		return gzdecode($data);
	}
}
