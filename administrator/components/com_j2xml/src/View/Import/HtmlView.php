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
 * or other free or open source software licenses.
 */

// no direct access
namespace Joomla\Component\J2xml\Administrator\View\Import;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Log\LogEntry;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * J2xml Import View
 *
 * @since  3.9.0
 */
class HtmlView extends \Joomla\Component\J2xml\Administrator\View\DefaultHtmlView
{
	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template
	 *
	 * @return  void
	 *
	 * @since   3.9
	 */
	public function display($tpl = null)
	{
		Log::add(new LogEntry(__METHOD__, Log::DEBUG, 'com_j2xml'));

		$this->form = $this->get('Form');

		$paths = new \stdClass;
		$paths->first = '';
		$state = $this->get('state');

		$this->paths = $paths;
		$this->state = $state;

		PluginHelper::importPlugin('installer');

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since 1.6
	 */
	protected function addToolbar()
	{
		Log::add(new LogEntry(__METHOD__, Log::DEBUG, 'com_j2xml'));

		ToolbarHelper::title(Text::_('COM_J2XML_TOOLBAR_J2XML'), 'upload import');

		if (Factory::getUser()->authorise('core.admin'))
		{
			ToolbarHelper::preferences('com_j2xml');
		}

		// Load the admin stylesheet (includes toolbar-credit styling)
		$this->document->getWebAssetManager()->useStyle('com_j2xml.admin');

		$toolbar = Toolbar::getInstance('toolbar');
		$toolbar->appendButton('Popup', 'credit', 'COM_J2XML_DONATE', 'https://www.eshiol.it/' . Text::_('COM_J2XML_DONATE_1') . '?tmpl=component', 550, 350);
	}
}
