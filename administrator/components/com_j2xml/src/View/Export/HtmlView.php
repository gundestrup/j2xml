<?php
/**
 * @package     Joomla.Administrator
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
namespace Joomla\Component\J2xml\Administrator\View\Export;

\defined('_JEXEC') or die;

/**
 * View class for export items.
 *
 * @since 3.9.0
 */
class HtmlView extends \Joomla\CMS\MVC\View\HtmlView
{

	/**
	 * The JForm object
	 *
	 * @var JForm
	 */
	protected $form;

	/**
	 * Display the view
	 *
	 * @param string $tpl
	 *			The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return mixed A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'com_j2xml'));

		// Initialiase variables.
		$this->form  = $this->get('Form');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \Exception(implode("\n", $errors), 500);
		}

		return parent::display($tpl);
	}
}
