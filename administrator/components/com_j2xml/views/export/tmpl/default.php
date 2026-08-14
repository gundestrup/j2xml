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
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$ui       = 'uitab';
$layout   = $this->getLayout();
$tabSetId = 'j2xml' . ucfirst($layout);

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('showon')
	->useScript('form.validate')
	->useScript('com_j2xml.admin');
?>
<form
	action="<?php echo Route::_('index.php?option=com_j2xml&task=' . $layout . '.display&format=raw'); ?>"
	id="adminForm" method="post" name="adminForm" autocomplete="off"
	class="form-horizontal">

	<?php echo LayoutHelper::render('j2xml.fieldsets', ['form' => $this->form, 'tabSetId' => $tabSetId, 'ui' => $ui], JPATH_ADMINISTRATOR . "/components/com_j2xml"); ?>

	<button class="hidden" id="j2xmlExportOkBtn" type="button"
		data-j2xml-task="export-submit">
	</button>
</form>
