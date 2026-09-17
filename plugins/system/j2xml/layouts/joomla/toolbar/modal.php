<?php
/**
 * @package     Joomla.Plugins
 * @subpackage  System.J2xml
 *
 * @version     __DEPLOY_VERSION__
 * @since       3.9
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
defined('_JEXEC') or die('Restricted access.');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

Factory::getApplication()->getDocument()->getWebAssetManager()
    ->useScript('joomla.dialog')
    ->useScript('webcomponent.toolbar-button');

/**
 * Generic toolbar button layout to open a modal
 * -----------------------------------------------
 * @param   array   $displayData    Button parameters. Default supported parameters:
 *                                - selector  string  Unique DOM identifier for the modal. CSS id without #
 *                                - class    string  Button class
 *                                - icon      string  Button icon
 *                                - text      string  Button text
 */

$tagName = $tagName ?? 'button';

$selector = $displayData['selector'];
$id    = isset($displayData['id']) ? $displayData['id'] : '';
$class  = isset($displayData['class']) ? $displayData['class'] : 'btn btn-sm btn-primary';
$icon    = isset($displayData['icon']) ? $displayData['icon'] : 'fas fa-download';
$title  = $displayData['title'];
$text    = isset($displayData['text']) ? $displayData['text'] : '';
$cancel   = isset($displayData['cancel']) ? $displayData['cancel'] : Text::_('JCANCEL');
$ok    = isset($displayData['ok']) ? $displayData['ok'] : Text::_('JOK');
$onclick  = isset($displayData['onclick']) ? $displayData['onclick'] : '';
$modalUrl = str_replace('&amp;', '&', (string) $displayData['doTask']);
?>

<joomla-toolbar-button<?php echo $id; ?> id="<?php echo $selector; ?>Open">
<<?php echo $tagName; ?>
    class="<?php echo $class ?? ''; ?>"
    <?php echo $htmlAttributes ?? ''; ?>
    <?php echo $title; ?>
    >
    <span class="<?php echo $icon; ?>" aria-hidden="true"></span>
    <?php echo $text ?? ''; ?>
</<?php echo $tagName; ?>>
</joomla-toolbar-button>

<!-- Open the options dialog on click (joomla-dialog web component, J5.1+/J6) -->
<script>
(function () {
    var init = function () {
        var trigger = document.getElementById('<?php echo $selector; ?>Open');
        if (!trigger || trigger.dataset.j2xmlDialogBound) {
            return;
        }
        trigger.dataset.j2xmlDialogBound = '1';
        trigger.addEventListener('click', function () {
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
                    src: <?php echo json_encode($modalUrl); ?>,
                    textHeader: <?php echo json_encode(strip_tags($title)); ?>,
                    width: '40vw',
                    height: '310px',
                    popupButtons: [
                        {
                            label: <?php echo json_encode($cancel); ?>,
                            className: 'btn btn-secondary',
                            onClick: function () { dialog.close(); }
                        },
                        {
                            label: <?php echo json_encode($ok); ?>,
                            className: 'btn btn-success',
                            onClick: function () {
                                <?php echo $onclick; ?>
                                var iframe = dialog.getBody() ? dialog.getBody().querySelector('iframe') : null;
                                if (iframe && iframe.contentWindow) {
                                    iframe.contentWindow.document.getElementById('<?php echo $selector; ?>OkBtn').click();
                                }
                            }
                        }
                    ]
                });
                dialog.show();
            });
        });
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
