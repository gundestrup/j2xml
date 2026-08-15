<?php
/**
 * @package     Joomla.Plugins
 * @subpackage  System.J2xml
 *
 * @version     __DEPLOY_VERSION__
 * @since       1.5
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
if (!class_exists('eshiol\\J2xml\\Exporter'))
{
	\JLoader::registerNamespace('eshiol\\J2xml', JPATH_LIBRARIES . '/eshiol/J2xml');
}

/**
 *
 */
class plgSystemJ2xml extends \Joomla\CMS\Plugin\CMSPlugin
{

	/**
	 * Load the language file on instantiation.
	 *
	 * @var boolean
	 */
	protected $autoloadLanguage = true;

	/**
	 * Application object.
	 *
	 * @var \Joomla\CMS\Application\CMSApplication
	 * @since 3.9.0
	 */
	protected $app;

	/**
	 * Constructor
	 *
	 * @param object $subject
	 *			The object to observe
	 * @param array $config
	 *			An array that holds the plugin configuration
	 */
	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$cparams = \Joomla\CMS\Component\ComponentHelper::getParams('com_j2xml');
		if ($this->params->get('debug', $cparams->get('debug', false)) || defined('JDEBUG') && JDEBUG)
		{
			\Joomla\CMS\Log\Log::addLogger(
				['text_file' => $this->params->get('log', 'eshiol.log.php'), 'extension' => 'plg_system_j2xml_file'],
				\Joomla\CMS\Log\Log::ALL,
				['plg_system_j2xml']);
		}
		if (PHP_SAPI == 'cli')
		{
			\Joomla\CMS\Log\Log::addLogger(
				['logger' => 'echo', 'extension' => 'plg_system_j2xml'],
				\Joomla\CMS\Log\Log::ALL & ~ \Joomla\CMS\Log\Log::DEBUG,
				['plg_system_j2xml']);
		}
		else
		{
			\Joomla\CMS\Log\Log::addLogger(
				['logger' => (null !== $this->params->get('logger')) ? $this->params->get('logger') : 'messagequeue', 'extension' => 'plg_system_j2xml'],
				\Joomla\CMS\Log\Log::ALL & ~ \Joomla\CMS\Log\Log::DEBUG,
				['plg_system_j2xml']);
		}
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'plg_system_j2xml'));

		// Only render in backend
		if (!$this->app->isClient('administrator'))
		{
			return;
		}

		// Only render if J2XML is installed and enabled
		$db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select($db->quoteName('enabled'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('name') . ' = ' . $db->quote('com_j2xml'));
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'plg_system_j2xml'));
		$is_enabled = $db->setQuery($query)->loadResult();
		if (!$is_enabled)
		{
			\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(\Joomla\CMS\Language\Text::sprintf('PLG_SYSTEM_J2XML_MSG_REQUIREMENTS_COM', \Joomla\CMS\Language\Text::_('PLG_SYSTEM_J2XML')), \Joomla\CMS\Log\Log::WARNING, 'plg_system_j2xml'));
		}
	}

	/**
	 * Method is called by index.php and administrator/index.php
	 *
	 * @access public
	 */
	public function onAfterDispatch()
	{
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'plg_system_j2xml'));

		if ($this->app->input->get('format') == 'xmlrpc')
		{
			return;
		}

		// Only render for HTML output.
		if ($this->app->getDocument()->getType() !== 'html')
		{
			return;
		}

		// Only render in backend
		if (!$this->app->isClient('administrator'))
		{
			return;
		}

		// Only render if J2XML is installed and enabled
		$db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select($db->quoteName('enabled'))
			->from('#__extensions')
			->where($db->quoteName('name') . ' = ' . $db->quote('com_j2xml'));
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'plg_system_j2xml'));

		$is_enabled = $db->setQuery($query)->loadResult();
		if (!$is_enabled)
		{
			return;
		}

		$input = $this->app->input;
		$option = $input->get('option');
		$contentType = substr($option, 4);

		$allowedView = $contentType;
		if (substr($allowedView, -1) != 's')
		{
			$allowedView .= 's';
		}
		$view = $input->get('view', $allowedView);

		if ($contentType == 'content')
		{
			if (($view != 'contents') && ($view != 'articles') && ($view != 'featured'))
			{
				return true;
			}

		}
		elseif ($contentType == 'users')
		{
			if ($view == 'notes')
			{
				$contentType = 'usernotes';
			}
			elseif ($view != $allowedView)
			{
				return true;
			}
		}
		elseif ($view != $allowedView)
		{
			return true;
		}

		// Only render if J2XML view exists and J2XML Library is loaded
		if (!class_exists('eshiol\\J2xml\\Exporter') || !method_exists('eshiol\\J2xml\\Exporter', $contentType))
		{
			return true;
		}

		if (file_exists(JPATH_ADMINISTRATOR . '/components/com_j2xml/views/export/tmpl/' . $contentType . '.php')
			|| file_exists(JPATH_ADMINISTRATOR . '/components/com_j2xml/views/export/tmpl/default.php'))
		{
			$bar = \Joomla\CMS\Toolbar\Toolbar::getInstance('toolbar');

			$buttonClass = 'button-download btn btn-sm';

			foreach ($bar->getItems() as $button)
			{
				if (gettype($button) != 'array')
				{
					if ($button->getName() == 'status-group')
					{
						$bar = $button->getChildToolbar();
						$buttonClass = 'button-download dropdown-item';
						break;
					}
				}
			}
			$iconExport = 'icon-download';
			$iconSend = 'icon-out';
			$layout = new \Joomla\CMS\Layout\FileLayout('joomla.toolbar.modal');

			$layout->addIncludePath(JPATH_PLUGINS . '/system/j2xml/layouts');
			$selector = 'j2xmlExport';
			$dHtml	= $layout->render(
				[
					'selector' => $selector,
					'icon'	   => $iconExport,
					'text'	   => \Joomla\CMS\Language\Text::_('JTOOLBAR_EXPORT'),
					'title'	   => \Joomla\CMS\Language\Text::_('PLG_SYSTEM_J2XML_EXPORT_' . strtoupper($contentType)),
					'class'	   => $buttonClass,
					'doTask'   => \Joomla\CMS\Router\Route::_('index.php?option=com_j2xml&amp;view=export&amp;layout=' . $contentType . '&amp;format=html&amp;tmpl=component'),
					'ok'	   => \Joomla\CMS\Language\Text::_('JTOOLBAR_EXPORT'),
					'onclick'  => 'var cids=[];document.querySelectorAll(\'input[type=checkbox][name=&quot;cid[]&quot;]:checked\').forEach(function(cb){cids.push(cb.value);});document.querySelector(\'#' . $selector . 'Modal iframe\').contentWindow.document.getElementById(\'jform_cid\').value=cids;'
				]);

			$bar->appendButton('Custom', $dHtml, 'download');

			// Check if the J2XML webservices plugin is enabled (REST API).
			$query = $db->getQuery(true)
				->select($db->quoteName('extension_id'))
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
				->where($db->quoteName('folder') . ' = ' . $db->quote('webservices'))
				->where($db->quoteName('element') . ' = ' . $db->quote('j2xml'));
			\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'plg_system_j2xml'));

			if ($db->setQuery($query)->loadResult())
			{
				\Joomla\CMS\Language\Text::script('LIB_J2XML_ERROR_UNKNOWN');

				$selector = 'j2xmlSend';
				$dHtml	= $layout->render(
					[
						'selector'       => $selector,
						'icon'	         => $iconSend,
						'text'	         => \Joomla\CMS\Language\Text::_('PLG_SYSTEM_J2XML_BUTTON_SEND'),
						'title'	         => \Joomla\CMS\Language\Text::_('PLG_SYSTEM_J2XML_SEND_' . strtoupper($contentType)),
						'class'	         => $buttonClass,
						'doTask'         => \Joomla\CMS\Router\Route::_('index.php?option=com_j2xml&amp;view=send&amp;layout=' . $contentType . '&amp;format=html&amp;tmpl=component'),
						'ok'	         => \Joomla\CMS\Language\Text::_('PLG_SYSTEM_J2XML_BUTTON_SEND'),
						'onclick'        => 'var cids=[];document.querySelectorAll(\'input[type=checkbox][name=&quot;cid[]&quot;]:checked\').forEach(function(cb){cids.push(cb.value);});document.querySelector(\'#' . $selector . 'Modal iframe\').contentWindow.document.getElementById(\'jform_cid\').value=cids;',
						'formValidation' => true
					]);
				$bar->appendButton('Custom', $dHtml, 'send');
			}
		}

		// Trigger the onAfterDispatch event.
		// \Joomla\CMS\Plugin\PluginHelper::importPlugin('j2xml');
		// \Joomla\CMS\Factory::getApplication()->triggerEvent('onLoadJS');

		return true;
	}
}
