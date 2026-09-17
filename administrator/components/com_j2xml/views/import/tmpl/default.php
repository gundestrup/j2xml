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
 * @copyright   Copyright (C) 2026 Svend Gundestrup. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

$ui = 'uitab';

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('com_j2xml.library')
    ->useScript('com_j2xml.admin')
    ->useScript('joomla.dialog')
    ->useStyle('com_j2xml.admin');

Text::script('LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED');
Text::script('COM_J2XML_MSG_INSTALL_ENTER_A_URL');

Factory::getApplication()->getLanguage()->load('com_j2xml.sys');
?>

<div id="j2xml-import" class="clearfix">
    <form enctype="multipart/form-data" action="<?php echo Route::_('index.php?option=com_j2xml'); ?>"
        method="post" name="adminForm" id="adminForm">
        <?php if (!empty($this->sidebar)) : ?>
        <div id="j-sidebar-container" class="col-md-2">
            <?php echo $this->sidebar; ?>
        </div>
        <div id="j-main-container" class="col-md-10">
            <?php else : ?>
            <div id="j-main-container">
                <?php endif; ?>
                <?php if ($this->showMessage) : ?>
                    <?php echo $this->loadTemplate('message'); ?>
                <?php endif; ?>
                <?php echo HTMLHelper::_($ui . '.startTabSet', 'myTab', ['active' => 'package']); ?>

                <?php
                $tabs = [];
                $tab = [
                    'name'  => 'package',
                    'label' => Text::_('COM_J2XML_PACKAGEIMPORTER_UPLOAD_DATA_FILE'),
                ];

                ob_start();
                include __DIR__ . '/default_package.php';
                $tab['content'] = ob_get_clean();

                $tabs[] = $tab;
                ?>

                <?php foreach ($tabs as $tab) : ?>
                    <?php echo HTMLHelper::_($ui . '.addTab', 'myTab', $tab['name'], $tab['label']); ?>
                    <fieldset class="uploadform">
                        <?php echo $tab['content']; ?>
                    </fieldset>
                    <?php echo HTMLHelper::_($ui . '.endTab'); ?>
                <?php endforeach; ?>

                <?php if (!$tabs) : ?>
                    <?php Factory::getApplication()->enqueueMessage(Text::_('COM_J2XML_NO_INSTALLATION_PLUGINS_FOUND'), 'warning'); ?>
                <?php endif; ?>

                <?php if (!empty($this->ftp)) : ?>
                    <?php echo HTMLHelper::_($ui . '.addTab', 'myTab', 'ftp', Text::_('COM_J2XML_MSG_DESCFTPTITLE')); ?>
                    <?php echo $this->loadTemplate('ftp'); ?>
                    <?php echo HTMLHelper::_($ui . '.endTab'); ?>
                <?php endif; ?>

                <input type="hidden" name="installtype" value=""/>
                <input type="hidden" name="task" value="import.import"/>
                <?php echo HTMLHelper::_('form.token'); ?>

                <?php echo HTMLHelper::_($ui . '.endTabSet'); ?>
            </div>
            <button class="hidden" id="j2xmlImportCloseBtn" type="button"></button>
            <button class="hidden" id="j2xmlImportBtn" type="button"></button>
    </form>
</div>
<div id="loading"></div>

<?php
// Trigger the onLoadJS event.
PluginHelper::importPlugin('j2xml');
Factory::getApplication()->getDispatcher()->dispatch('onLoadJS', new \Joomla\Event\Event('onLoadJS'));

// Build the import options dialog lazily with the JoomlaDialog API
// (joomla-dialog web component, J5.1+/J6). popupButtons contain JS
// callbacks, so the dialog must be constructed via `new JoomlaDialog()`
// before it is attached to the DOM — buttons render on connect.
$selector = 'j2xmlImport';
$modalUrl = Route::_('index.php?' . http_build_query([
    'option' => 'com_j2xml',
    'view'   => 'import',
    'layout' => 'options',
    'tmpl'   => 'component',
    Session::getFormToken() => 1,
]));
?>
<script>
(function () {
    if (typeof eshiol === 'undefined') {
        window.eshiol = {};
    }
    if (typeof eshiol.j2xml === 'undefined') {
        eshiol.j2xml = {};
    }

    eshiol.j2xml.showImportDialog = function () {
        var existing = document.getElementById('<?php echo $selector; ?>Modal');
        if (existing) {
            existing.show();
            return;
        }
        customElements.whenDefined('joomla-dialog').then(function () {
            var Dialog = customElements.get('joomla-dialog');
            var dialog = new Dialog({
                id: '<?php echo $selector; ?>Modal',
                popupType: 'iframe',
                src: <?php echo json_encode(str_replace('&amp;', '&', $modalUrl)); ?>,
                textHeader: <?php echo json_encode(Text::_('COM_J2XML_IMPORT')); ?>,
                width: '50vw',
                height: '420px',
                popupButtons: [
                    {
                        label: <?php echo json_encode(Text::_('JTOOLBAR_CANCEL')); ?>,
                        className: 'btn btn-secondary',
                        onClick: function () { dialog.close(); }
                    },
                    {
                        label: <?php echo json_encode(Text::_('COM_J2XML_IMPORT')); ?>,
                        className: 'btn btn-success',
                        onClick: function () {
                            if (eshiol.j2xml && typeof eshiol.j2xml.importerModal === 'function') {
                                eshiol.j2xml.importerModal();
                            }
                            dialog.close();
                        }
                    }
                ]
            });
            dialog.show();
        });
    };
})();
</script>
