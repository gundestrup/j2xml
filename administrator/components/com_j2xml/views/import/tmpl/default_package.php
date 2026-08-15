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
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

Text::script('COM_J2XML_IMPORTING');
Text::script('COM_J2XML_PACKAGEIMPORTER_UPLOAD_ERROR_UNKNOWN');
Text::script('COM_J2XML_PACKAGEIMPORTER_UPLOAD_ERROR_EMPTY');
Text::script('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN');
Text::script('LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED');
Text::script('COM_J2XML_PACKAGEIMPORTER_NO_PACKAGE');

$token  = Session::getFormToken();
$return = Factory::getApplication()->getInput()->getBase64('return');

$params = ComponentHelper::getParams('com_j2xml');
$this->document->addScriptOptions('J2XML', ['HaltOnError' => (bool) $params->get('haltonerror', 1)]);

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('com_j2xml.import')
	->useScript('bootstrap.modal')
	->useStyle('com_j2xml.import');

$max = strtoupper(trim((string) ini_get('upload_max_filesize')));
if ($max !== '' && $max !== '0')
{
	$unit = substr($max, -1);
	$value = (int) $max;
	switch ($unit)
	{
		case 'G': $value *= 1024;
		case 'M': $value *= 1024;
		case 'K': $value *= 1024;
	}
	$maxSize = $value;
}
else
{
	$maxSize = 0;
}

$this->document->addScriptOptions('progressBarContainerClass', 'progress');
$this->document->addScriptOptions('progressBarClass', 'progress-bar progress-bar-striped progress-bar-animated bg');
$this->document->addScriptOptions('progressBarErrorClass', 'progress-bar progress-bar-striped progress-bar-animated bg-error');
?>
<legend><?php echo Text::_('COM_J2XML_PACKAGEIMPORTER_UPLOAD_IMPORT_DATA'); ?></legend>

<div id="uploader-wrapper">
	<div id="dragarea" data-state="pending">
		<div id="dragarea-content" class="text-center">
			<p>
				<span id="upload-icon" class="icon-upload" aria-hidden="true"></span>
			</p>
			<div class="upload-progress">
				<div class="progress">
					<div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
						style="width:0"
						role="progressbar"
						aria-valuenow="0"
						aria-valuemin="0"
						aria-valuemax="100"
					></div>
				</div>
				<p class="lead">
					<span class="uploading-text">
						<?php echo Text::_('COM_J2XML_PACKAGEIMPORTER_UPLOADING'); ?>
					</span>
					<span class="uploading-number">0</span><span class="uploading-symbol">%</span>
				</p>
			</div>
			<div class="install-progress">
				<div class="progress">
					<div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%;"></div>
				</div>
				<p class="lead">
					<span class="installing-text">
						<?php echo Text::_('COM_J2XML_PACKAGEIMPORTER_IMPORTING'); ?>
					</span>
				</p>
			</div>
			<div class="upload-actions">
				<p class="lead">
					<?php echo Text::_('COM_J2XML_PACKAGEIMPORTER_DRAG_FILE_HERE'); ?>
				</p>
				<p>
					<button id="select-file-button" type="button" class="btn btn-success">
						<span class="icon-copy" aria-hidden="true"></span>
						<?php echo Text::_('COM_J2XML_PACKAGEIMPORTER_SELECT_FILE'); ?>
					</button>
				</p>
				<p>
					<?php echo Text::sprintf('JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT', $maxSize); ?>
				</p>
			</div>
		</div>
	</div>
</div>

<div id="legacy-uploader" style="display: none;">
	<div class="row mb-3">
		<label for="install_package" class="form-label col-sm-3 col-form-label"><?php echo Text::_('COM_J2XML_PACKAGEIMPORTER_DATA_FILE'); ?></label>
		<div class="col-sm-9">
			<input class="form-control" id="install_package" name="install_package" type="file" />
			<?php echo Text::sprintf('JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT', $maxSize); ?>
		</div>
	</div>
	<div class="d-grid gap-2">
		<button class="btn btn-primary" type="button" id="installbutton_package">
			<?php echo Text::_('COM_J2XML_PACKAGEIMPORTER_UPLOAD_AND_INSTALL'); ?>
		</button>
	</div>

	<input id="installer-return" name="return" type="hidden" value="<?php echo $return; ?>" />
	<input id="installer-token" name="token" type="hidden" value="<?php echo $token; ?>" />
</div>

<input id="j2xml_filename" name="j2xml_filename" type="hidden" value="" />
<input id="j2xml_data" name="j2xml_data" type="hidden" value="" />
