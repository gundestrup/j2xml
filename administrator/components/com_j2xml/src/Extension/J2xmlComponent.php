<?php
/**
 * @package     Joomla.Administrator
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

namespace Joomla\Component\J2xml\Administrator\Extension;

use Joomla\CMS\Extension\MVCComponent;

\defined('_JEXEC') or die;

/**
 * Component class for com_j2xml.
 *
 * This is a minimal MVCComponent that enables the Joomla service provider
 * to discover the component's API controllers via the MVCFactory.
 * The legacy backend (administrator/) and frontend (site/) controllers
 * continue to work as before — this class only bridges the new
 * namespaced MVC factory used by the API application.
 *
 * @since   __DEPLOY_VERSION__
 */
class J2xmlComponent extends MVCComponent
{
}
