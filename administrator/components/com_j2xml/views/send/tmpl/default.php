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
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

$ui     = 'uitab';
$layout = $this->getLayout();
$tabSetId = 'j2xml' . ucfirst($layout);

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('webcomponent.toolbar-button')
	->useScript('showon')
	->useScript('form.validate')
	->useScript('keepalive')
	->useScript('com_j2xml.admin');

$this->document->addScriptOptions('progressBarContainerClass', 'progress');
$this->document->addScriptOptions('progressBarClass', 'progress-bar progress-bar-striped progress-bar-animated bg-success');

Text::script('COM_J2XML_SEND_ERROR');
Text::script('COM_J2XML_SEND_ERROR_REMOTEURL_IS_REQUIRED');
Text::script('LIB_J2XML_SENDING');
Text::script('LIB_J2XML_MSG_XMLRPC_DISABLED');
Text::script('LIB_J2XML_ERROR_UNKNOWN');
Text::script('LIB_J2XML_ERROR_STATUS0');
?>
<form action="<?php echo Route::_('index.php?option=com_j2xml'); ?>"
	id="adminForm" method="post" name="adminForm"
	class="form-horizontal form-validate">

	<?php echo LayoutHelper::render('j2xml.fieldsets', ['form' => $this->form, 'tabSetId' => $tabSetId, 'ui' => $ui], JPATH_ADMINISTRATOR . "/components/com_j2xml"); ?>

	<button class="hidden" id="j2xmlSendOkBtn" type="button"
		data-j2xml-task="send-submit"
		data-j2xml-export-url="<?php echo Route::_('index.php?option=com_j2xml&task=' . $layout . '.export&format=json&' . Session::getFormToken() . '=1'); ?>"
		data-j2xml-token="<?php echo Session::getFormToken(); ?>">
	</button>
</form>
