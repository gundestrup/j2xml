/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @version     __DEPLOY_VERSION__
 * @since       16.11.288
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

if (typeof eshiol === 'undefined') {
    window.eshiol = {};
}

if (typeof eshiol.j2xml === 'undefined') {
    eshiol.j2xml = {};
}

if (typeof eshiol.j2xml.convert === 'undefined') {
    eshiol.j2xml.convert = [];
}

if (typeof eshiol.j2xml.validate === 'undefined') {
    eshiol.j2xml.validate = [];
}

eshiol.j2xml.version = '__DEPLOY_VERSION__';

/**
 * Loading overlay helper. Defined here so both the library and component
 * JS can rely on it without a backwards dependency.
 */
const JoomlaInstaller = {
    getLoadingOverlay: function () {
        return document.getElementById('loading');
    },
    showLoading: function () {
        const el = this.getLoadingOverlay();
        if (el) {
            el.style.display = 'block';
        }
    },
    hideLoading: function () {
        const el = this.getLoadingOverlay();
        if (el) {
            el.style.display = 'none';
        }
    }
};

eshiol.j2xml.validate.push(function (data) {
    try {
        const xmlDoc = new DOMParser().parseFromString(data, 'text/xml');
        const root = xmlDoc.documentElement;

        return root.nodeName === 'j2xml' && versionCompare(root.getAttribute('version'), '15.9.0') >= 0;
    } catch (e) {
        return false;
    }
});

/**
 * Remove messages from the system message container.
 *
 * @param  {HTMLElement|string|undefined}  container  The messages container
 */
if (typeof eshiol.removeMessages === 'undefined') {
    eshiol.removeMessages = function (container) {
        let el;

        if (typeof container === 'undefined') {
            el = document.getElementById('system-message-container');
        } else if (typeof container === 'string') {
            el = document.querySelector(container);
        } else {
            el = container;
        }

        if (!el) {
            return;
        }

        while (el.firstChild) {
            el.removeChild(el.firstChild);
        }

        // Force reflow (Chrome height bug workaround)
        el.style.display = 'none';
        void el.offsetHeight;
        el.style.display = '';
    };
}

/**
 * Render messages sent via JSON using Joomla's web-component alerts.
 *
 * @param  {object}  messages  Object keyed by type, values are string or string[]
 * @param  {HTMLElement|string|undefined}  container  Where to render
 */
if (typeof eshiol.renderMessages === 'undefined') {
    eshiol.renderMessages = function (messages, container) {
        let el;

        if (typeof container === 'undefined' || container === '#system-message-container') {
            el = document.getElementById('system-message-container');
        } else if (container instanceof HTMLElement) {
            el = container;
        } else {
            el = document.querySelector(container);
        }

        if (!el) {
            return;
        }

        const typeMap = {
            notice: 'info',
            message: 'success',
            error: 'danger'
        };

        Object.keys(messages).forEach(function (type) {
            const alertClass = typeMap[type] || type;
            const typeMessages = messages[type];

            const messagesBox = document.createElement('joomla-alert');
            messagesBox.setAttribute('type', alertClass);
            messagesBox.setAttribute('close-text', Joomla.Text._('JCLOSE'));
            messagesBox.setAttribute('dismiss', true);

            const title = Joomla.Text._(type);
            if (typeof title !== 'undefined') {
                const titleWrapper = document.createElement('div');
                titleWrapper.className = 'alert-heading';
                titleWrapper.innerHTML = Joomla.sanitizeHtml(
                    '<span class="' + type + '"></span>' +
                    '<span class="visually-hidden">' + (Joomla.Text._(type) || type) + '</span>'
                );
                messagesBox.appendChild(titleWrapper);
            }

            const messageWrapper = document.createElement('div');
            messageWrapper.className = 'alert-wrapper';

            const items = typeof typeMessages === 'string' ? [typeMessages] : typeMessages;
            items.forEach(function (msg) {
                messageWrapper.innerHTML += Joomla.sanitizeHtml('<div class="alert-message">' + msg + '</div>');
            });

            messagesBox.appendChild(messageWrapper);
            el.appendChild(messagesBox);
        });
    };
}

eshiol.j2xml.codes = [
    'message', // LIB_J2XML_MSG_ARTICLE_IMPORTED 0
    'notice',  // LIB_J2XML_MSG_ARTICLE_NOT_IMPORTED 1
    'message', // LIB_J2XML_MSG_USER_IMPORTED 2
    'notice',  // LIB_J2XML_MSG_USER_NOT_IMPORTED 3
    'notice',  // not used: LIB_J2XML_MSG_SECTION_IMPORTED 4
    'notice',  // not used: LIB_J2XML_MSG_SECTION_NOT_IMPORTED 5
    'message', // LIB_J2XML_MSG_CATEGORY_IMPORTED 6
    'notice',  // LIB_J2XML_MSG_CATEGORY_NOT_IMPORTED 7
    'message', // LIB_J2XML_MSG_FOLDER_WAS_SUCCESSFULLY_CREATED 8
    'notice',  // LIB_J2XML_MSG_ERROR_CREATING_FOLDER 9
    'message', // LIB_J2XML_MSG_IMAGE_IMPORTED 10
    'notice',  // LIB_J2XML_MSG_IMAGE_NOT_IMPORTED 11
    'message', // LIB_J2XML_MSG_WEBLINK_IMPORTED 12
    'notice',  // LIB_J2XML_MSG_WEBLINK_NOT_IMPORTED 13
    'notice',  // not used: LIB_J2XML_MSG_WEBLINKCAT_NOT_PRESENT 14
    'error',   // LIB_J2XML_MSG_XMLRPC_NOT_SUPPORTED 15
    'notice',  // LIB_J2XML_MSG_CATEGORY_ID_PRESENT 16
    'error',   // LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED 17
    'error',   // LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN 18
    'error',   // JERROR_ALERTNOTAUTH 19
    'message', // LIB_J2XML_MSG_TAG_IMPORTED 20
    'notice',  // LIB_J2XML_MSG_TAG_NOT_IMPORTED 21
    'message', // LIB_J2XML_MSG_CONTACT_IMPORTED 22
    'notice',  // LIB_J2XML_MSG_CONTACT_NOT_IMPORTED 23
    'message', // LIB_J2XML_MSG_VIEWLEVEL_IMPORTED 24
    'notice',  // LIB_J2XML_MSG_VIEWLEVEL_NOT_IMPORTED 25
    'message', // LIB_J2XML_MSG_BUTTON_IMPORTED 26
    'notice',  // LIB_J2XML_MSG_BUTTON_NOT_IMPORTED 27
    'error',   // LIB_J2XML_MSG_UNKNOWN_ERROR 28
    'warning', // LIB_J2XML_MSG_UNKNOWN_WARNING 29
    'notice',  // LIB_J2XML_MSG_UNKNOWN_NOTICE 30
    'message', // LIB_J2XML_MSG_UNKNOWN_MESSAGE 31
    'notice',  // LIB_J2XML_MSG_XMLRPC_DISABLED 32
    'message', // LIB_J2XML_MSG_MENUTYPE_IMPORTED 33
    'notice',  // LIB_J2XML_MSG_MENUTYPE_NOT_IMPORTED 34
    'message', // LIB_J2XML_MSG_MENU_IMPORTED 35
    'notice',  // LIB_J2XML_MSG_MENU_NOT_IMPORTED 36
    'notice',  // LIB_J2XML_ERROR_COMPONENT_NOT_FOUND 37
    'message', // LIB_J2XML_MSG_MODULE_IMPORTED 38
    'notice',  // LIB_J2XML_MSG_MODULE_NOT_IMPORTED 39
    'message', // LIB_J2XML_MSG_FIELD_IMPORTED 40
    'notice',  // LIB_J2XML_MSG_FIELD_NOT_IMPORTED 41
    'message', // LIB_J2XML_MSG_USERNOTE_IMPORTED 42
    'notice',  // LIB_J2XML_MSG_USERNOTE_NOT_IMPORTED 43
    'message', // LIB_J2XML_MSG_FIELDGROUP_IMPORTED 44
    'notice',  // LIB_J2XML_MSG_FIELDGROUP_NOT_IMPORTED 45
    'notice'   // LIB_J2XML_MSG_USER_SKIPPED 46
];

/**
 * Helper: hide a Bootstrap modal in the parent window.
 */
function hideParentModal(id) {
    const modalEl = window.parent.document.getElementById(id);
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.hide();
    }
}

/**
 * Helper: show a Bootstrap modal in the parent window.
 */
function showParentModal(id) {
    const modalEl = window.parent.document.getElementById(id);
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
}

/**
 * Send a single item to a remote Joomla site via the REST API.
 */
eshiol.j2xml.sendItem = function (options, params) {
    if (!options.cids.length) {
        const progressEl = window.parent.document.getElementById('send-progress');
        if (progressEl) {
            progressEl.remove();
        }
        return;
    }

    const cid = options.cids.shift();
    if (isNaN(options.n)) {
        options.n = 0;
    }

    const progress = Math.floor(100 * options.n / options.tot);
    const progressBar = window.parent.document.getElementById('send-progress-bar');
    const progressText = window.parent.document.getElementById('send-progress-text');

    if (progressBar) {
        progressBar.style.width = progress + '%';
        progressBar.setAttribute('aria-valuenow', options.n);
    }
    if (progressText) {
        progressText.innerHTML = Joomla.Text._('LIB_J2XML_SENDING').replace('%s', progress + '%');
    }
    options.n++;

    const body = Object.keys(params).map(function (key) {
        return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
    }).join('&');

    fetch(options.export_url + '&cid[]=' + cid, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body,
        credentials: 'same-origin'
    }).then(function (response) {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        return response.text();
    }).then(function (resp) {
        const r = JSON.parse(resp);
        const p = Object.assign({}, params);
        delete p.compression;
        delete p.token;

        fetch(options.remote_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Joomla-Token': options.token || ''
            },
            body: JSON.stringify({data: r.data, options: p})
        }).then(function (response) {
            if (!response.ok) {
                return response.json().then(function (err) {
                    throw err;
                });
            }
            return response.json();
        }).then(function (result) {
            // Uncheck the processed checkbox
            const checkAll = window.parent.document.querySelector('input[name="checkall-toggle"]');
            if (checkAll) {
                checkAll.checked = false;
            }
            const cb = window.parent.document.querySelector('input[name="cid[]"][value="' + cid + '"]');
            if (cb) {
                cb.checked = false;
            }

            const msgContainer = window.parent.document.getElementById('system-message-container');

            if (result.data && Array.isArray(result.data)) {
                result.data.forEach(function (item) {
                    const t = (item.code in eshiol.j2xml.codes) ? eshiol.j2xml.codes[item.code] : 'notice';
                    const msg = {};
                    msg[t] = [item.message];
                    eshiol.renderMessages(msg, msgContainer);
                });
            } else if (result.messages) {
                eshiol.renderMessages(result.messages, msgContainer);
            }

            eshiol.j2xml.sendItem(options, params);
        }).catch(function (error) {
            const msg = {};
            if (error && error.errors && error.errors.title) {
                msg.error = [error.errors.title];
            } else if (error && error.message) {
                msg.error = [error.message];
            } else {
                msg.error = [Joomla.Text._('LIB_J2XML_ERROR_UNKNOWN')];
            }
            eshiol.renderMessages(msg, window.parent.document.getElementById('system-message-container'));
            eshiol.j2xml.sendItem(options, params);
        });
    }).catch(function (error) {
        eshiol.renderMessages(
            {error: [error.message || Joomla.Text._('LIB_J2XML_ERROR_UNKNOWN')]},
            window.parent.document.getElementById('system-message-container')
        );
        eshiol.j2xml.sendItem(options, params);
    });
};

/**
 * Begin the send process: collect checked IDs and form parameters, then
 * create a progress bar and start sending items one by one.
 */
eshiol.j2xml.send = function (options) {
    options.cids = [];
    options.tot = 0;
    options.alert = 0;
    options.error = 0;
    options.success = 0;
    options.warning = 0;

    const progressBarContainerClass = Joomla.getOptions('progressBarContainerClass', 'progress');
    const progressBarClass = Joomla.getOptions('progressBarClass', 'progress-bar progress-bar-striped progress-bar-animated bg-success');

    const msgContainer = window.parent.document.getElementById('system-message-container');
    if (msgContainer) {
        const progressDiv = document.createElement('div');
        progressDiv.id = 'send-progress';
        progressDiv.className = 'send-progress';
        progressDiv.innerHTML =
            '<div class="' + progressBarContainerClass + '">' +
            '<div id="send-progress-bar" class="' + progressBarClass + '" aria-valuenow="0" aria-valuemin="0" aria-valuemax="' + options.tot + '"></div>' +
            '</div>' +
            '<p class="lead">' +
            '<span id="send-progress-text" class="sending-text">' +
            Joomla.Text._('LIB_J2XML_SENDING').replace('%s', '0%') +
            '</span>' +
            '</p>';
        msgContainer.insertBefore(progressDiv, msgContainer.firstChild);
    }

    // Collect form parameters
    const params = {};
    const formElements = document.querySelectorAll('#adminForm input[name^=jform], #adminForm select[name^=jform]');
    formElements.forEach(function (el) {
        if (el.type === 'radio' && !el.checked) {
            return;
        }
        const match = el.name.match(/jform\[(.*)\]/);
        if (!match) {
            return;
        }
        let name = match[1];
        if (name.startsWith('send_')) {
            name = name.substring(5);
        }
        if (!['cid', 'remote_url', 'token'].includes(name)) {
            params[name] = el.value;
        }
    });

    // Collect checked cids from parent window
    const checkboxes = window.parent.document.querySelectorAll('input[name="cid[]"]:checked');
    checkboxes.forEach(function (cb) {
        options.cids.push(cb.value);
        options.tot++;
    });

    eshiol.j2xml.sendItem(options, params);
};

eshiol.XMLToString = function (xmlDom) {
    return new XMLSerializer().serializeToString(xmlDom);
};

eshiol.download = function (filename, text) {
    const element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
};

/**
 * Import modal: extract XML nodes from the uploaded file and import them
 * one by one via AJAX.
 */
eshiol.j2xml.importerModal = function () {
    // Copy form fields from the modal iframe into the main form
    const iframe = document.getElementById('j2xmlImportModal');
    if (iframe && iframe.contentDocument) {
        const iframeFields = iframe.contentDocument.querySelectorAll('#adminForm input[name^=jform], #adminForm select[name^=jform]');
        iframeFields.forEach(function (input) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.id = input.id;
            hidden.name = input.name;
            hidden.value = input.value;
            document.getElementById('adminForm').appendChild(hidden);
        });
    }

    window.top.setTimeout(function () {
        hideParentModal('j2xmlImportModal');
    }, 700);

    const dataField = document.getElementById('j2xml_data');
    const nodes = [];

    try {
        const raw = base64.decode(dataField.value);
        const xmlDoc = new DOMParser().parseFromString(raw, 'text/xml');
        const root = xmlDoc.documentElement;

        const header = '<?xml version="1.0" encoding="UTF-8" ?>\n<j2xml version="' + root.getAttribute('version') + '">\n';
        const footer = '\n</j2xml>';

        const knownTags = ['base', 'user', 'tag', 'category', 'content', 'fieldgroup', 'field', 'menutype', 'menu', 'module'];

        knownTags.forEach(function (tag) {
            root.querySelectorAll(':scope > ' + tag).forEach(function (node) {
                nodes.push(header + eshiol.XMLToString(node) + footer);
            });
        });

        // Process any remaining unknown tags
        Array.from(root.children).forEach(function (node) {
            if (!knownTags.includes(node.nodeName)) {
                nodes.push(header + eshiol.XMLToString(node) + footer);
            }
        });
    } catch (e) {
        nodes.push(base64.decode(dataField.value));
    }

    eshiol.removeMessages();

    const options = {
        tot: nodes.length,
        info: 0,
        success: 0,
        warning: 0,
        error: 0
    };

    const progressBarContainerClass = Joomla.getOptions('progressBarContainerClass', 'progress');
    const progressBarClass = Joomla.getOptions('progressBarClass', 'progress-bar progress-bar-striped progress-bar-animated bg');
    const progressBarErrorClass = Joomla.getOptions('progressBarErrorClass', 'progress-bar progress-bar-striped progress-bar-animated bg-error');

    const existingProgress = document.getElementById('import-progress');
    if (existingProgress) {
        existingProgress.remove();
    }

    const progressDiv = document.createElement('div');
    progressDiv.id = 'import-progress';
    progressDiv.className = 'import-progress';
    progressDiv.innerHTML =
        '<div class="' + progressBarContainerClass + '" style="font-size:1rem;height:1.5rem">' +
        '<div id="import-progress-bar-info" class="' + progressBarClass + '-info"></div>' +
        '<div id="import-progress-bar-success" class="' + progressBarClass + '-success"></div>' +
        '<div id="import-progress-bar-warning" class="' + progressBarClass + '-warning"></div>' +
        '<div id="import-progress-bar-error" class="' + progressBarErrorClass + '"></div>' +
        '</div>';

    const msgContainer = document.getElementById('system-message-container');
    if (msgContainer && msgContainer.nextSibling) {
        msgContainer.parentNode.insertBefore(progressDiv, msgContainer.nextSibling);
    } else if (msgContainer) {
        msgContainer.parentNode.appendChild(progressDiv);
    }

    eshiol.j2xml.importer(nodes, options);
};

/**
 * Update a progress bar element's width and text.
 */
function updateProgressBar(id, count, total) {
    const bar = document.getElementById(id);
    if (bar) {
        bar.style.width = (100 * count / total) + '%';
        bar.textContent = count;
    }
}

/**
 * Remove striped/animated classes from all progress bars.
 */
function stopProgressBars() {
    ['import-progress-bar-info', 'import-progress-bar-success', 'import-progress-bar-error', 'import-progress-bar-warning'].forEach(function (id) {
        const bar = document.getElementById(id);
        if (bar) {
            bar.classList.remove('progress-bar-striped', 'progress-bar-animated');
        }
    });
}

/**
 * Import items one by one via AJAX.
 *
 * @param {string[]}  nodes    Array of XML fragments
 * @param {object}    options  Progress counters
 */
eshiol.j2xml.importer = function (nodes, options) {
    if (nodes.length === 0) {
        stopProgressBars();
        setTimeout(function () {
            const p = document.getElementById('import-progress');
            if (p) {
                p.remove();
            }
        }, 10000);
        return;
    }

    const item = nodes.shift();
    const tokenField = document.getElementById('installer-token');
    const token = tokenField ? tokenField.value : '';
    const url = 'index.php?option=com_j2xml&task=import.ajax_upload';

    const formData = new FormData();
    const blob = new Blob([new TextEncoder().encode(item)], {type: 'text/xml'});
    const filenameField = document.getElementById('j2xml_filename');
    formData.append('install_package', blob, filenameField ? filenameField.value : 'j2xml.xml');
    formData.append('installtype', 'upload');
    formData.append(token, '1');

    // Copy form fields from the modal iframe
    const iframe = document.getElementById('j2xmlImportModal');
    if (iframe && iframe.contentDocument) {
        const iframeFields = iframe.contentDocument.querySelectorAll('#adminForm input[name^=jform], #adminForm select[name^=jform]');
        iframeFields.forEach(function (el) {
            if (el.type === 'radio' && !el.checked) {
                return;
            }
            formData.append(el.name, el.value);
        });
    }

    JoomlaInstaller.showLoading();

    fetch(url, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    }).then(function (response) {
        return response.json();
    }).then(function (res) {
        if (res.messages) {
            Object.keys(res.messages).forEach(function (type) {
                if (type === 'notice') {
                    options.info++;
                    updateProgressBar('import-progress-bar-info', options.info, options.tot);
                } else if (type === 'message') {
                    options.success++;
                    updateProgressBar('import-progress-bar-success', options.success, options.tot);
                } else if (type === 'error') {
                    const j2xmlOptions = Joomla.getOptions('J2XML');
                    if (j2xmlOptions && j2xmlOptions.HaltOnError) {
                        options.error = options.tot - options.info - options.success - options.warning;
                        const errorBar = document.getElementById('import-progress-bar-error');
                        if (errorBar) {
                            errorBar.style.width = (100 * options.error / options.tot) + '%';
                            errorBar.innerHTML = res.messages[type];
                        }
                        nodes.length = 0;
                    } else {
                        options.error++;
                        updateProgressBar('import-progress-bar-error', options.error, options.tot);
                    }
                } else {
                    options.warning++;
                    updateProgressBar('import-progress-bar-warning', options.warning, options.tot);
                }
            });
            eshiol.renderMessages(res.messages);
        } else if (res.message) {
            if (res.success) {
                options.success++;
                updateProgressBar('import-progress-bar-success', options.success, options.tot);
                eshiol.renderMessages({message: [res.message]});
            } else {
                options.error++;
                updateProgressBar('import-progress-bar-error', options.error, options.tot);
                eshiol.renderMessages({error: [res.message]});
            }
        } else {
            options.tot--;
            updateProgressBar('import-progress-bar-info', options.info, options.tot);
            updateProgressBar('import-progress-bar-success', options.success, options.tot);
            updateProgressBar('import-progress-bar-error', options.error, options.tot);
            updateProgressBar('import-progress-bar-warning', options.warning, options.tot);
        }

        // Scroll to latest message
        const msgEl = document.getElementById('system-message-container');
        if (msgEl && msgEl.childNodes.length > 0) {
            const last = msgEl.childNodes[msgEl.childNodes.length - 1];
            if (last) {
                last.scrollIntoView();
            }
        }

        if (nodes.length === 0) {
            JoomlaInstaller.hideLoading();
        }

        // Continue with next item
        eshiol.j2xml.importer(nodes, options);
    }).catch(function (error) {
        JoomlaInstaller.hideLoading();
        eshiol.renderMessages({error: [error.message || error.toString()]});
        stopProgressBars();
        setTimeout(function () {
            const p = document.getElementById('import-progress');
            if (p) {
                p.remove();
            }
        }, 10000);
    });
};
