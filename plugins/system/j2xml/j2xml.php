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
 * @copyright   Copyright (C) 2026 Svend Gundestrup. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Application\AfterDispatchEvent;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\SubscriberInterface;

/**
 *
 */
class plgSystemJ2xml extends \Joomla\CMS\Plugin\CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['onAfterDispatch' => 'onAfterDispatch'];
    }

    /**
     * Load the language file on instantiation.
     *
     * @var boolean
     */
    protected $autoloadLanguage = true;

    /**
     * Constructor
     *
     * @param object $subject
     *          The object to observe
     * @param array $config
     *          An array that holds the plugin configuration
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

    }

    /**
     * Method is called by index.php and administrator/index.php
     *
     * @access public
     */
    public function onAfterDispatch(AfterDispatchEvent $event): void
    {
        $app = $event->getApplication();
        \Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(__METHOD__, \Joomla\CMS\Log\Log::DEBUG, 'plg_system_j2xml'));

        if ($app->getInput()->get('format') == 'xmlrpc')
        {
            return;
        }

        // Only render for HTML output.
        if ($app->getDocument()->getType() !== 'html')
        {
            return;
        }

        // Only render in backend
        if (!$app->isClient('administrator'))
        {
            return;
        }

        // Only render if J2XML is installed and enabled.
        if (!ComponentHelper::isEnabled('com_j2xml'))
        {
            return;
        }

        $contentType = $this->resolveContentType($app->getInput());
        if ($contentType === null)
        {
            return;
        }

        // Only render if J2XML view exists and J2XML Library is loaded
        if (!class_exists('eshiol\\J2xml\\Exporter') || !method_exists('eshiol\\J2xml\\Exporter', $contentType))
        {
            return;
        }

        if (file_exists(JPATH_ADMINISTRATOR . '/components/com_j2xml/views/export/tmpl/' . $contentType . '.php')
            || file_exists(JPATH_ADMINISTRATOR . '/components/com_j2xml/views/export/tmpl/default.php'))
        {
            $this->addToolbarButtons($contentType);
        }

    }

    /**
     * Resolve the J2XML content type for the current view, or null when the
     * view is not eligible for the export/send buttons.
     *
     * @param \Joomla\Input\Input $input
     *          the request input
     *
     * @return string|null
     */
    private function resolveContentType (\Joomla\Input\Input $input): ?string
    {
        $option = $input->get('option', '');
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
                return null;
            }
        }
        elseif ($contentType == 'users')
        {
            if ($view == 'notes')
            {
                return 'usernotes';
            }
            if ($view != $allowedView)
            {
                return null;
            }
        }
        elseif ($view != $allowedView)
        {
            return null;
        }

        return $contentType;
    }

    /**
     * Add the J2XML export and send modal buttons to the toolbar.
     *
     * @param string $contentType
     *          the resolved J2XML content type
     *
     * @return void
     */
    private function addToolbarButtons (string $contentType): void
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

        $layout = new \Joomla\CMS\Layout\FileLayout('joomla.toolbar.modal');
        $layout->addIncludePath(JPATH_PLUGINS . '/system/j2xml/layouts');

        $dHtml = $layout->render(
            $this->modalButtonData('j2xmlExport', 'icon-download', 'JTOOLBAR_EXPORT', 'PLG_SYSTEM_J2XML_EXPORT_', 'export', $contentType, $buttonClass));
        $bar->appendButton('Custom', $dHtml, 'download');

        // Check if the J2XML webservices plugin is enabled (REST API).
        if (!PluginHelper::isEnabled('webservices', 'j2xml'))
        {
            return;
        }

        \Joomla\CMS\Language\Text::script('LIB_J2XML_ERROR_UNKNOWN');

        $dHtml = $layout->render(
            $this->modalButtonData('j2xmlSend', 'icon-out', 'PLG_SYSTEM_J2XML_BUTTON_SEND', 'PLG_SYSTEM_J2XML_SEND_', 'send', $contentType, $buttonClass, true));
        $bar->appendButton('Custom', $dHtml, 'send');
    }

    /**
     * Build the display data for a toolbar modal button.
     *
     * @param string $selector
     *          the modal selector
     * @param string $icon
     *          the button icon class
     * @param string $textKey
     *          the language key used for both the button text and ok label
     * @param string $titlePrefix
     *          the language key prefix for the modal title
     * @param string $view
     *          the com_j2xml view to load in the modal
     * @param string $contentType
     *          the resolved J2XML content type
     * @param string $buttonClass
     *          the button css class
     * @param boolean $formValidation
     *          enable form validation on the modal ok button
     *
     * @return array the layout display data
     */
    private function modalButtonData (string $selector, string $icon, string $textKey, string $titlePrefix, string $view, string $contentType, string $buttonClass, bool $formValidation = false): array
    {
        $data = [
            'selector' => $selector,
            'icon'     => $icon,
            'text'     => \Joomla\CMS\Language\Text::_($textKey),
            'title'    => \Joomla\CMS\Language\Text::_($titlePrefix . strtoupper($contentType)),
            'class'    => $buttonClass,
            'doTask'   => \Joomla\CMS\Router\Route::_('index.php?option=com_j2xml&amp;view=' . $view . '&amp;layout=' . $contentType . '&amp;format=html&amp;tmpl=component'),
            'ok'       => \Joomla\CMS\Language\Text::_($textKey),
            'onclick'  => 'var cids=[];document.querySelectorAll(\'input[type=checkbox][name="cid[]"]:checked\').forEach(function(cb){cids.push(cb.value);});var dlgIframe=dialog.getBody()?dialog.getBody().querySelector(\'iframe\'):null;if(dlgIframe&&dlgIframe.contentWindow){var cidField=dlgIframe.contentWindow.document.getElementById(\'jform_cid\');if(cidField){cidField.value=cids;}}'
        ];

        if ($formValidation)
        {
            $data['formValidation'] = true;
        }

        return $data;
    }
}
