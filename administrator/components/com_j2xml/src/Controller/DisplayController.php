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

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Default display controller for J2XML.
 *
 * Handles the default `display` task, rendering the view specified
 * in the URL (defaults to `import`).
 */
class DisplayController extends BaseController
{
	/**
	 * The default view.
	 *
	 * @var string
	 */
	protected $default_view = 'import';
}
