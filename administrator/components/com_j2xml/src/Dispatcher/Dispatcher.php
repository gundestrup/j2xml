<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2026 Helios Ciancio <info (at) eshiol (dot) it> (https://www.eshiol.it). All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 */

namespace Joomla\Component\J2xml\Administrator\Dispatcher;

\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcher;

/**
 * Dispatcher for the admin side of com_j2xml.
 *
 * Uses the Joomla MVC factory to create namespaced controllers.
 *
 * @since  __DEPLOY_VERSION__
 */
class Dispatcher extends ComponentDispatcher
{
	/**
	 * Dispatch the controller task.
	 *
	 * @return  void
	 */
	public function dispatch(): void
	{
		$this->checkAccess();

		// Set up logging and language as the legacy entry point did.
		$params = \Joomla\CMS\Component\ComponentHelper::getParams('com_j2xml');

		if ($params->get('debug', 0))
		{
			\ini_set('display_errors', 'On');
			\error_reporting(E_ALL);
		}

		if ($params->get('debug') || (\defined('JDEBUG') && JDEBUG))
		{
			\Joomla\CMS\Log\Log::addLogger(
				['text_file' => $params->get('log', 'eshiol.log.php'), 'extension' => 'com_j2xml_file'],
				\Joomla\CMS\Log\Log::DEBUG | \Joomla\CMS\Log\Log::ERROR,
				['lib_j2xml', 'com_j2xml']
			);
		}

		\Joomla\CMS\Log\Log::addLogger(
			['logger' => 'messagequeue', 'extension' => 'com_j2xml'],
			\Joomla\CMS\Log\Log::ALL & ~\Joomla\CMS\Log\Log::DEBUG,
			['lib_j2xml', 'com_j2xml']
		);

		$this->app->getDocument()->addScriptOptions('J2XML', ['Joomla' => 4]);

		// Load language files.
		$lang = $this->app->getLanguage();
		$lang->load('com_j2xml', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('com_j2xml', JPATH_ADMINISTRATOR, $lang->getDefault(), true);
		$lang->load('com_j2xml', JPATH_ADMINISTRATOR, null, true);
		$lang->load('lib_j2xml', JPATH_SITE, null, false, false)
			|| $lang->load('lib_j2xml', JPATH_ADMINISTRATOR, null, false, false)
			|| $lang->load('lib_j2xml', JPATH_SITE, null, true)
			|| $lang->load('lib_j2xml', JPATH_ADMINISTRATOR, null, true);

		parent::dispatch();
	}
}
