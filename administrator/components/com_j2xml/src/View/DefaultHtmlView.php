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

namespace Joomla\Component\J2xml\Administrator\View;

\defined('_JEXEC') or die;

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Log\LogEntry;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Base HTML view for J2XML admin views.
 *
 * @since  __DEPLOY_VERSION__
 */
class DefaultHtmlView extends HtmlView
{
	public function __construct($config = null)
	{
		Log::add(new LogEntry(__METHOD__, Log::DEBUG, 'com_j2xml'));

		$app = \Joomla\CMS\Factory::getApplication();
		parent::__construct($config);
		$this->_addPath('template', $this->_basePath . '/views/default/tmpl');
		$this->_addPath('template', JPATH_THEMES . '/' . $app->getTemplate() . '/html/com_j2xml/default');
	}

	public function display($tpl = null)
	{
		Log::add(new LogEntry(__METHOD__, Log::DEBUG, 'com_j2xml'));

		$state = $this->get('State');

		$showMessage = false;
		if (is_object($state))
		{
			$message = $state->get('message');
			$showMessage = (bool) $message;
		}

		$this->showMessage = $showMessage;
		$this->state = &$state;

		$this->addToolbar();
		parent::display($tpl);
	}

	protected function addToolbar()
	{
		Log::add(new LogEntry(__METHOD__, Log::DEBUG, 'com_j2xml'));

		$canDo = ContentHelper::getActions('com_j2xml');
		ToolbarHelper::title(Text::_('COM_J2XML_HEADER_' . $this->getName()), 'upload import');

		if ($canDo->get('core.admin') || $canDo->get('core.options'))
		{
			ToolbarHelper::preferences('com_j2xml');
			ToolbarHelper::divider();
		}
	}
}
