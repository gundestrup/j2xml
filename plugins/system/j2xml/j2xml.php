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
 * @copyright   Copyright (C) 2010 - 2023 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die('Restricted access.');

JLoader::import('eshiol.J2xml.Exporter');
JLoader::import('eshiol.J2xml.Sender');

JLoader::register('eshiol\\J2xml\\Helper\\Joomla', __DIR__ . '/src/J2xml/Helper/Joomla.php');

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
	 * @var JApplicationCms
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
				array('text_file' => $this->params->get('log', 'eshiol.log.php'), 'extension' => 'plg_system_j2xml_file'),
				\Joomla\CMS\Log\Log::ALL,
				array('plg_system_j2xml'));
		}
		if (PHP_SAPI == 'cli')
		{
			\Joomla\CMS\Log\Log::addLogger(
				array('logger' => 'echo', 'extension' => 'plg_system_j2xml'),
				\Joomla\CMS\Log\Log::ALL & ~ \Joomla\CMS\Log\Log::DEBUG,
				array('plg_system_j2xml'));
		}
		else
		{
			\Joomla\CMS\Log\Log::addLogger(
				array('logger' => (null !== $this->params->get('logger')) ? $this->params->get('logger') : 'messagequeue', 'extension' => 'plg_system_j2xml'),
				\Joomla\CMS\Log\Log::ALL & ~ \Joomla\CMS\Log\Log::DEBUG,
				array('plg_system_j2xml'));
		}
		\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'plg_system_j2xml'));

		$version = new \Joomla\CMS\Version();

		if (!$version->isCompatible('4'))
		{
			// overwrite original Joomla
			$loader = require JPATH_LIBRARIES . '/vendor/autoload.php';

			// update class maps
			$classMap = $loader->getClassMap();
			if ($version->isCompatible('3.8'))
			{
				$classMap['Joomla\CMS\Layout\FileLayout'] = __DIR__ . '/src/joomla/src/Layout/FileLayout.php';
			}
			else
			{
				$classMap['JLayoutFile'] = __DIR__ . '/src/joomla/cms/layout/file.php';
			}
			$loader->addClassMap($classMap);
		}

		// Only render in backend
		if ($version->isCompatible('3.7'))
		{
			if (!$this->app->isClient('administrator'))
			{
				return;
			}
		}
		else
		{
			if (!$this->app->isAdmin())
			{
				return;
			}
		}

		// Only render if J2XML is installed and enabled
		$db = \Joomla\CMS\Factory::getDbo();
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
		if (\Joomla\CMS\Factory::getDocument()->getType() !== 'html')
		{
			return;
		}

		// Only render in backend
		$version = new \Joomla\CMS\Version();

		if ($version->isCompatible('3.7'))
		{
			if (!$this->app->isClient('administrator'))
			{
				return;
			}
		}
		else
		{
			if (!$this->app->isAdmin())
			{
				return;
			}
		}

		// Only render if J2XML is installed and enabled
		$db = \Joomla\CMS\Factory::getDbo();
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
        if (!\Joomla\CMS\Filesystem\File::exists(JPATH_ADMINISTRATOR . '/components/com_j2xml/views/' . $contentType . '/view.raw.php'))
		{
			return true;
		}

		if (\Joomla\CMS\Filesystem\File::exists(JPATH_ADMINISTRATOR . '/components/com_j2xml/views/export/tmpl/' . $contentType . '.php'))
		{
			if (class_exists('eshiol\\J2xml\\Exporter') && method_exists('eshiol\\J2xml\\Exporter', $contentType))
			{
				$bar = \Joomla\CMS\Toolbar\Toolbar::getInstance('toolbar');

				$version = new \Joomla\CMS\Version();
				if ($version->isCompatible('4'))
				{
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
					$layout = new JLayoutFile('joomla4.toolbar.modal');
				}
				else
				{
					$buttonClass = 'btn btn-small';
					$iconExport = 'download';
					$iconSend = 'out';
					$layout = new JLayoutFile('joomla.toolbar.modal');
				}

				$layout->addIncludePath(JPATH_PLUGINS . '/system/j2xml/layouts');
				$selector = 'j2xmlExport';
				$dHtml	= $layout->render(
					array(
						'selector' => $selector,
						'icon'	   => $iconExport,
						'text'	   => \Joomla\CMS\Language\Text::_('JTOOLBAR_EXPORT'),
						'title'	   => \Joomla\CMS\Language\Text::_('PLG_SYSTEM_J2XML_EXPORT_' . strtoupper($contentType)),
						'class'	   => $buttonClass,
						'doTask'   => \Joomla\CMS\Router\Route::_('index.php?option=com_j2xml&amp;view=export&amp;layout=' . $contentType . '&amp;format=html&amp;tmpl=component'),
						'ok'	   => \Joomla\CMS\Language\Text::_('JTOOLBAR_EXPORT'),
						'onclick'  => 'var cids=new Array();jQuery(\'input:checkbox[name=\\\'cid\[\]\\\']:checked\').each( function(){cids.push(jQuery(this).val());});jQuery(\'#' . $selector . 'Modal iframe\').contents().find(\'#jform_cid\').val(cids);'
				));

				$bar->appendButton('Custom', $dHtml, 'download');

				if ($version->isCompatible('3.9'))
				{
					$lib_xmlrpc = 'eshiol/phpxmlrpc';
				}
				else
				{
					$lib_xmlrpc = 'phpxmlrpc';
				}

				$query = $db->getQuery(true)
					->select($db->quoteName('extension_id'))
					->from($db->quoteName('#__extensions'))
					->where($db->quoteName('type') . ' = ' . $db->quote('library'))
					->where($db->quoteName('element') . ' = ' . $db->quote($lib_xmlrpc));
				\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry($query, \Joomla\CMS\Log\Log::DEBUG, 'plg_system_j2xml'));

				if ($db->setQuery($query)->loadResult() && \Joomla\CMS\Helper\LibraryHelper::isEnabled($lib_xmlrpc))
				{
					\Joomla\CMS\Language\Text::script('LIB_J2XML_ERROR_UNKNOWN');

					$layout->addIncludePath(JPATH_PLUGINS . '/system/j2xml/layout');
					$selector = 'j2xmlSend';
					$dHtml	= $layout->render(
						array(
							'selector'       => $selector,
							'icon'	         => $iconSend,
							'text'	         => \Joomla\CMS\Language\Text::_('PLG_SYSTEM_J2XML_BUTTON_SEND'),
							'title'	         => \Joomla\CMS\Language\Text::_('PLG_SYSTEM_J2XML_SEND_' . strtoupper($contentType)),
							'class'	         => $buttonClass,
							'doTask'         => \Joomla\CMS\Router\Route::_('index.php?option=com_j2xml&amp;view=send&amp;layout=' . $contentType . '&amp;format=html&amp;tmpl=component'),
							'ok'	         => \Joomla\CMS\Language\Text::_('PLG_SYSTEM_J2XML_BUTTON_SEND'),
							'onclick'        => 'var cids=new Array();jQuery(\'input:checkbox[name=\\\'cid\[\]\\\']:checked\').each( function(){cids.push(jQuery(this).val());});jQuery(\'#' . $selector . 'Modal iframe\').contents().find(\'#jform_cid\').val(cids);',
							'formValidation' => true
						));
					$bar->appendButton('Custom', $dHtml, 'send');
				}
			}
		}

		// Trigger the onAfterDispatch event.
		// \Joomla\CMS\Plugin\PluginHelper::importPlugin('j2xml');
		// \Joomla\CMS\Factory::getApplication()->triggerEvent('onLoadJS');

		return true;
	}

	/**
	 * Add an assets for debugger.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function onBeforeCompileHead()
	{
		// $version = new \Joomla\CMS\Version();
		$version = new Joomla\CMS\Version();

		if ($version->isCompatible( '4' ))
		{
			// Use our own jQuery and fontawesome instead of the debug bar shipped version
			$assetManager = $this->app->getDocument()->getWebAssetManager();
			$assetManager->useScript('core')->useScript('jquery');
		}
	}

}