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

'use strict';

// URL import form submission
if (typeof Joomla !== 'undefined') {
Joomla.submitbutton4 = function () {
    const form = document.getElementById('adminForm');

    if (!form.install_url.value || form.install_url.value === 'http://' || form.install_url.value === 'https://') {
        alert(Joomla.Text._('COM_J2XML_MSG_INSTALL_ENTER_A_URL'));
    } else {
        JoomlaInstaller.showLoading();
        form.installtype.value = 'url';
        form.submit();
    }
};

// Package upload form submission (legacy uploader)
Joomla.submitbuttonpackage = function () {
    const form = document.getElementById('adminForm');

    if (!form.install_package.value) {
        alert(Joomla.Text._('COM_J2XML_PACKAGEIMPORTER_NO_PACKAGE'));
    } else {
        JoomlaInstaller.showLoading();
        form.installtype.value = 'upload';
        form.submit();
    }
};
}

document.addEventListener('DOMContentLoaded', function () {
    // Position the loading overlay
    const outerDiv = document.getElementById('j2xml-import');
    if (outerDiv) {
        const overlay = JoomlaInstaller.getLoadingOverlay();
        if (overlay) {
            overlay.style.top = (outerDiv.offsetTop - window.scrollY) + 'px';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.display = 'none';
            overlay.style.marginTop = '-10px';
        }
    }

    // Export modal submit button
    const exportBtn = document.getElementById('j2xmlExportOkBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            this.form.submit();
            window.top.setTimeout(function () {
                hideParentModal('j2xmlExportModal');
            }, 700);
        });
    }

    // Send modal submit button
    const sendBtn = document.getElementById('j2xmlSendOkBtn');
    if (sendBtn) {
        sendBtn.addEventListener('click', function () {
            const parentMsgContainer = window.parent.document.getElementById('system-message-container');
            eshiol.removeMessages(parentMsgContainer);

            const form = document.getElementById('adminForm');

            // Use HTML5 form validation
            if (form && form.checkValidity()) {
                window.top.setTimeout(function () {
                    hideParentModal('j2xmlSendModal');
                }, 700);

                const exportUrl = sendBtn.getAttribute('data-j2xml-export-url');
                const remoteUrlField = document.getElementById('jform_remote_url');
                const tokenField = document.getElementById('jform_token');
                const compressionField = document.getElementById('jform_compression');

                const remoteUrl = (remoteUrlField ? remoteUrlField.value : '').replace(/\/?$/, '') + 'api/index.php/v1/j2xml/import';
                const token = tokenField ? tokenField.value : '';
                const compression = compressionField ? compressionField.value : '';

                const passwordField = document.querySelector('input[name="jform[password]"]:checked');
                const fieldsField = document.querySelector('input[name="jform[fields]"]:checked');

                eshiol.j2xml.send({
                    export_url: exportUrl,
                    remote_url: remoteUrl,
                    token: token,
                    compression: compression,
                    password: passwordField ? passwordField.value : '',
                    fields: fieldsField ? fieldsField.value : ''
                });
            } else {
                form.reportValidity();
            }
        });
    }

    // Import modal trigger button (data-attribute driven)
    document.querySelectorAll('[data-j2xml-task="import-modal"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (typeof eshiol.j2xml.importerModal === 'function') {
                eshiol.j2xml.importerModal();
            }
        });
    });

    // Import close button
    const importCloseBtn = document.getElementById('j2xmlImportCloseBtn');
    if (importCloseBtn) {
        importCloseBtn.addEventListener('click', function () {
            const pkg = document.getElementById('install_package');
            if (pkg) {
                pkg.value = '';
            }
        });
    }

    // Import button
    const importBtn = document.getElementById('j2xmlImportBtn');
    if (importBtn) {
        importBtn.addEventListener('click', function () {
            const pkg = document.getElementById('install_package');
            if (pkg) {
                pkg.value = '';
            }
        });
    }

    // Legacy uploader submit button
    const installBtn = document.getElementById('installbutton_package');
    if (installBtn) {
        installBtn.addEventListener('click', function () {
            Joomla.submitbuttonpackage();
        });
    }
});
