<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
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

// no direct access
defined('_JEXEC') or die();

jimport('eshiol.J2xmlpro.Version');
jimport('eshiol.J2xml.Version');

$params = \Joomla\CMS\Component\ComponentHelper::getParams('com_j2xml');

JLoader::import('joomla.log.log');
if ($params->get('debug') || defined('JDEBUG') && JDEBUG)
{
	\Joomla\CMS\Log\Log::addLogger(
		array('text_file' => $params->get('log', 'eshiol.log.php'), 'extension' => 'com_j2xml_file'),
		\Joomla\CMS\Log\Log::ALL,
		array('lib_j2xml', 'com_j2xml'));
}

$headers   = getallheaders();
\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry('headers: ' . print_r($headers, true), \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));
\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry('$_SERVER: ' . print_r($_SERVER, true), \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

$app       = \Joomla\CMS\Factory::getApplication();

$poweredBy = 'J2XML/' . (class_exists('eshiol\J2xmlpro\Version') ? \eshiol\J2xmlpro\Version::getShortVersion() : \eshiol\J2xml\Version::getShortVersion());
header('X-Powered-By: ' . $poweredBy);

$jversion  = new \Joomla\CMS\Version();
$forceCORS = $app->get('cors', !$jversion->isCompatible('4'));
if ($forceCORS)
{
	/**
	 * Enable CORS (Cross-origin resource sharing)
	 * Obtain allowed CORS origin from Global Settings.
	 * Set to * (=all) if not set.
	 */
	$allowedOrigin = $app->get('cors_allow_origin', '*');
	$allowedOrigin = $allowedOrigin != '*' ? $allowedOrigin : $headers['Origin'];

	$allowedHeaders = $app->get('cors_allow_headers', 'Content-Type,X-Joomla-Token');

	header('Access-Control-Allow-Origin: ' . $allowedOrigin);
	header('Access-Control-Allow-Credentials: true');

	// respond to preflights
	if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS')
	{
		header('Access-Control-Allow-Headers: ' . $allowedHeaders);

		exit;
	}
}

$jinput = \Joomla\CMS\Factory::getApplication()->input;
$controllerClass = 'J2xmlController';
$task = $jinput->getCmd('task');

if (strpos($task, '.') === false)
{
	$controllerPath = JPATH_COMPONENT . '/controller.php';
}
else
{
	// We have a defined controller/task pair -- lets split them out
	list ($controllerName, $task) = explode('.', $task);

	// Define the controller name and path
	$controllerName = strtolower($controllerName);

	$controllerPath = JPATH_COMPONENT . '/controllers/' . $controllerName;

	$format = $jinput->getCmd('format');
	if ($format == 'xmlrpc')
	{
		$jversion = new \Joomla\CMS\Version();
		if ($jversion->isCompatible('3.9'))
		{
			$lib_xmlrpc = 'eshiol/phpxmlrpc';
		}
		else
		{
			$lib_xmlrpc = 'phpxmlrpc';
		}

		if (\Joomla\CMS\Helper\LibraryHelper::isEnabled($lib_xmlrpc) && $params->get('xmlrpc'))
		{
			require_once JPATH_LIBRARIES . '/eshiol/phpxmlrpc/Log/Logger/XmlrpcLogger.php';
			\Joomla\CMS\Log\Log::addLogger(
				array('logger' => 'xmlrpc', 'extension' => 'com_j2xml', 'service' => 'XMLRPCJ2XMLServices'),
				\Joomla\CMS\Log\Log::ALL & ~ \Joomla\CMS\Log\Log::DEBUG,
				array('lib_j2xml', 'com_j2xml'));
		}
		else
		{
			\Joomla\CMS\Factory::getApplication()->enqueueMessage(\Joomla\CMS\Language\Text::_('LIB_J2XML_MSG_XMLRPC_DISABLED'), 'error');
		}
		$controllerPath .= '.' . strtolower($format);
	}
	else
	{
		\Joomla\CMS\Log\Log::addLogger(
			array('logger' => 'messagequeue', 'extension' => 'com_j2xml'),
			\Joomla\CMS\Log\Log::ALL & ~ \Joomla\CMS\Log\Log::DEBUG,
			array('lib_j2xml', 'com_j2xml'));
	}
	$controllerPath .= '.php';
	// Set the name for the controller and instantiate it
	$controllerClass .= ucfirst($controllerName);
}

\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($controllerPath, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));
\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($controllerClass, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

// If the controller file path exists, include it ... else lets die with a 500 error
if (file_exists($controllerPath))
{
	require_once $controllerPath;
}
else
{
	throw new Exception('Invalid Controller ' . $controllerName, 500);
}

if (class_exists($controllerClass))
{
	$controller = new $controllerClass();
}
else
{
	throw new Exception('Invalid Controller Class - ' . $controllerName, 500);
}

$lang = \Joomla\CMS\Factory::getApplication()->getLanguage();
$lang->load('lib_j2xml', JPATH_SITE, null, false, false) || $lang->load('lib_j2xml', JPATH_ADMINISTRATOR, null, false, false) ||
// Fallback to the lib_j2xml file in the default language
$lang->load('lib_j2xml', JPATH_SITE, null, true) || $lang->load('lib_j2xml', JPATH_ADMINISTRATOR, null, true);

// Perform the Request task
$controller->execute($task);

// Redirect if set by the controller
$controller->redirect();
