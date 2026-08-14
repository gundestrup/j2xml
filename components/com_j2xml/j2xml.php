<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
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

// Register the eshiol\J2xml namespace for PSR-0 autoloading.
if (!class_exists('eshiol\\J2xml\\Version'))
{
	\JLoader::registerNamespace('eshiol\\J2xml', JPATH_LIBRARIES . '/eshiol/J2xml');
}

$params = \Joomla\CMS\Component\ComponentHelper::getParams('com_j2xml');

if ($params->get('debug') || defined('JDEBUG') && JDEBUG)
{
	\Joomla\CMS\Log\Log::addLogger(
		['text_file' => $params->get('log', 'eshiol.log.php'), 'extension' => 'com_j2xml_file'],
		\Joomla\CMS\Log\Log::ALL,
		['lib_j2xml', 'com_j2xml']);
}

$headers   = getallheaders();
\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry('headers: ' . print_r($headers, true), \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));
\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry('$_SERVER: ' . print_r($_SERVER, true), \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

$app       = \Joomla\CMS\Factory::getApplication();

$poweredBy = 'J2XML/' . (class_exists('eshiol\J2xmlpro\Version') ? \eshiol\J2xmlpro\Version::getShortVersion() : \eshiol\J2xml\Version::getShortVersion());
header('X-Powered-By: ' . $poweredBy);

$forceCORS = $app->get('cors', false);
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

if (!str_contains($task, '.'))
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

	\Joomla\CMS\Log\Log::addLogger(
		['logger' => 'messagequeue', 'extension' => 'com_j2xml'],
		\Joomla\CMS\Log\Log::ALL & ~ \Joomla\CMS\Log\Log::DEBUG,
		['lib_j2xml', 'com_j2xml']);

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
