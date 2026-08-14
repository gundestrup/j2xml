<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Webservices.j2xml
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

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Router\Route;

/**
 * Web Services plugin for J2XML.
 *
 * Registers the REST API route for importing J2XML content:
 *   POST /api/index.php/v1/j2xml/import
 *
 * Authentication is handled by Joomla's built-in token-based API
 * authentication (X-Joomla-Token header). The user must have
 * core.admin permission on com_j2xml.
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgWebservicesJ2xml extends CMSPlugin
{
	/**
	 * Registers the J2XML API routes.
	 *
	 * @param   \Joomla\CMS\Router\ApiRouter  &$router  The API routing object
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onBeforeApiRoute(&$router)
	{
		$defaults = [
			'component' => 'com_j2xml',
			'public'    => false,
		];

		$routes = [
			new Route(
				['POST'],
				'v1/j2xml/import',
				'import.import',
				[],
				$defaults
			),
		];

		foreach ($routes as $route)
		{
			$router->addRoute($route);
		}
	}
}
