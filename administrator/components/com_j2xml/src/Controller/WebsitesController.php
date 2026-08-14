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

namespace Joomla\Component\J2xml\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

/**
 * Websites list controller.
 */
class WebsitesController extends AdminController
{
	protected $text_prefix = 'COM_J2XML_WEBSITES';

	public function getModel($name = 'Website', $prefix = '', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}
