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

document.addEventListener('DOMContentLoaded', function () {
    if (typeof FormData === 'undefined') {
        const legacy = document.getElementById('legacy-uploader');
        const wrapper = document.getElementById('uploader-wrapper');
        if (legacy) {
            legacy.style.display = 'block';
        }
        if (wrapper) {
            wrapper.style.display = 'none';
        }
        return;
    }

    const dragZone = document.getElementById('dragarea');
    const fileInput = document.getElementById('install_package');
    const button = document.getElementById('select-file-button');

    if (!dragZone || !fileInput) {
        return;
    }

    function handleFile(file) {
        const reader = new FileReader();
        reader.onload = function () {
            let data;
            try {
                data = pako.ungzip(this.result, {to: 'string'});
            } catch (err) {
                data = this.result;
            }

            // Strip any content before the XML declaration
            const xmlDeclPos = data.indexOf('<?xml version="1.0" ');
            if (xmlDeclPos > 0) {
                data = data.substring(xmlDeclPos);
            }

            // Validate the XML data
            let validated = false;
            eshiol.j2xml.validate.forEach(function (fn) {
                validated = validated || fn(data);
            });
            if (!validated) {
                Joomla.renderMessages({error: [Joomla.Text._('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN')]});
                return;
            }

            // Run any registered converters
            eshiol.j2xml.convert.forEach(function (fn) {
                data = fn(data);
            });

            try {
                const xmlDoc = new DOMParser().parseFromString(data, 'text/xml');
                const root = xmlDoc.documentElement;

                if (root.nodeName !== 'j2xml') {
                    Joomla.renderMessages({error: [Joomla.Text._('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN')]});
                }
            } catch (e) {
                // Ignore parse errors – the file may still be processable
            }

            // Store the data for the import modal
            const filenameField = document.getElementById('j2xml_filename');
            const dataField = document.getElementById('j2xml_data');
            if (filenameField) {
                filenameField.value = file.name;
            }
            if (dataField) {
                dataField.value = btoa(unescape(encodeURIComponent(data)));
            }

            // Show the import options modal
            const modalEl = document.getElementById('j2xmlImportModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modal.show();
            }

            fileInput.value = '';
        };
        reader.readAsText(file, 'UTF-8');
    }

    if (button) {
        button.addEventListener('click', function () {
            fileInput.click();
        });
    }

    fileInput.addEventListener('change', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const files = e.target.files || (e.dataTransfer ? e.dataTransfer.files : []);
        if (files.length) {
            handleFile(files[0]);
        }
    });

    // Drag and drop handlers
    dragZone.addEventListener('dragenter', function (e) {
        e.preventDefault();
        e.stopPropagation();
        dragZone.classList.add('hover');
    });

    dragZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        e.stopPropagation();
        dragZone.classList.add('hover');
    });

    dragZone.addEventListener('dragleave', function (e) {
        e.preventDefault();
        e.stopPropagation();
        dragZone.classList.remove('hover');
    });

    dragZone.addEventListener('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        dragZone.classList.remove('hover');

        const files = e.dataTransfer.files;
        if (files.length) {
            handleFile(files[0]);
        }
    });
});
