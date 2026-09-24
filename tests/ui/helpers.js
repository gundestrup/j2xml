/**
 * Shared helpers for the J2XML UI (Playwright) test suite.
 *
 * @package     J2XML
 * @copyright   Copyright (C) 2026 Svend Gundestrup. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 */
const fs = require('fs');
const zlib = require('zlib');
const { expect } = require('@playwright/test');

const ADMIN_USER = process.env.J2XML_ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.J2XML_ADMIN_PASS || 'AdminAdmin123!';
const onboardingHandledOrigins = new Set();

/**
 * Collect uncaught exceptions (pageerror) and console.error messages for the
 * whole page, including same-origin iframes. This is the check that catches
 * bugs like a malformed querySelectorAll selector inside injected onclick code.
 *
 * J2XML_UI_IGNORE_ERRORS: '|'-separated regexes for known-benign noise.
 */
function collectPageErrors(page) {
    const errors = [];
    const patterns = (process.env.J2XML_UI_IGNORE_ERRORS || 'favicon')
        .split('|')
        .filter(Boolean)
        .map((p) => new RegExp(p, 'i'));
    const ignored = (text) => patterns.some((re) => re.test(text));
    const isJ2xmlEndpoint = (url) => /(?:[?&]option=com_j2xml(?:&|$)|\/api\/index\.php\/v1\/j2xml\/import(?:[?&#]|$))/i.test(url);

    page.on('pageerror', (err) => {
        if (!ignored(err.message)) {
            errors.push(`uncaught exception: ${err.message}`);
        }
    });
    page.on('console', (msg) => {
        if (msg.type() !== 'error') {
            return;
        }
        const text = msg.text();
        if (!ignored(text)) {
            errors.push(`console.error: ${text}`);
        }
    });
    page.on('response', (response) => {
        if (response.status() >= 500 || (response.status() >= 400 && isJ2xmlEndpoint(response.url()))) {
            const text = `HTTP ${response.status()}: ${response.url()}`;
            if (!ignored(text)) {
                errors.push(text);
            }
        }
    });
    page.on('requestfailed', (request) => {
        if (!isJ2xmlEndpoint(request.url())) {
            return;
        }
        const failure = request.failure()?.errorText || 'unknown error';
        if (failure === 'net::ERR_ABORTED') {
            return;
        }
        const text = `Request failed: ${request.url()} (${failure})`;
        if (!ignored(text)) {
            errors.push(text);
        }
    });
    return errors;
}

/** Assert the collected browser errors list is empty. */
function expectNoBrowserErrors(errors) {
    expect(errors, `Browser reported errors:\n${errors.join('\n')}`).toEqual([]);
}

/** Log in to the Joomla administrator backend (no-op if already logged in). */
async function adminLogin(page) {
    await page.goto('/administrator/index.php', { waitUntil: 'domcontentloaded' });

    const passwd = page.locator('input[name="passwd"]');
    if ((await passwd.count()) !== 0) {
        await page.locator('input[name="username"]').fill(ADMIN_USER);
        await passwd.fill(ADMIN_PASS);
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}),
            page.locator('button[type="submit"], input[type="submit"]').first().click()
        ]);

        // The login form must be gone; if it is still present the login failed.
        await expect(passwd).toHaveCount(0, { timeout: 20000 });
    }

    const origin = new URL(page.url()).origin;
    if (!onboardingHandledOrigins.has(origin)) {
        await dismissGuidedTour(page, true);
        const statisticsPrompt = page.getByRole('alertdialog', { name: /help us make joomla!? better/i });
        if (await statisticsPrompt.count() && await statisticsPrompt.first().isVisible().catch(() => false)) {
            await statisticsPrompt.getByRole('button', { name: /^no$/i }).click({ timeout: 5000 });
        }
        onboardingHandledOrigins.add(origin);
    }
    await expect(
        page.locator('#system-message-container joomla-alert').filter({hasText: /Only variables should be passed by reference/i}),
        'Joomla rendered a PHP reference-argument notice'
    ).toHaveCount(0);
}

/**
 * Joomla's guided-tour overlay appears on every admin page until dismissed and
 * blocks pointer events — click "Hide forever" to disable it for this user.
 */
async function dismissGuidedTour(page, waitForPrompt = false) {
    const hide = page.getByRole('button', { name: /hide forever/i });
    if (waitForPrompt) {
        await hide.first().waitFor({ state: 'visible', timeout: 2500 }).catch(() => {});
    }
    if (await hide.count() && await hide.first().isVisible().catch(() => false)) {
        await hide.first().click({ timeout: 5000 }).catch(() => {});
        return;
    }
    const tour = page.locator('.guided-tour, [class*="guidedtour"], .modal:has-text("Welcome to Joomla")');
    if (await tour.count() && await tour.first().isVisible().catch(() => false)) {
        await tour.first().getByRole('button', { name: /cancel|close|hide/i }).first()
            .click({ timeout: 5000 }).catch(() => {});
    }
}

/**
 * Click a J2XML toolbar button. On list views with an "Actions" dropdown the
 * button is a dropdown-item hidden until the group is opened.
 */
async function clickToolbarButton(page, wrapperId) {
    const wrapper = page.locator('#' + wrapperId);
    await wrapper.waitFor({ state: 'attached', timeout: 15000 });

    const button = wrapper.locator('button, a').first();
    if (await button.isVisible()) {
        await expect(button).toBeEnabled();
        await button.click();
        return;
    }

    const toggle = wrapper
        .locator('xpath=ancestor::div[contains(@class,"dropdown-menu")][1]/..')
        .locator('button.dropdown-toggle, [data-bs-toggle="dropdown"]')
        .first();
    await expect(toggle).toBeVisible();
    await expect(toggle).toBeEnabled();
    await toggle.click();
    await expect(button).toBeVisible();
    await expect(button).toBeEnabled();
    await button.click();
}

/**
 * Open a J2XML modal via its toolbar button and return the iframe locator.
 * Asserts the dialog element and the iframe form actually render.
 */
async function openModalDialog(page, wrapperId, modalId) {
    await dismissGuidedTour(page);
    await clickToolbarButton(page, wrapperId);

    const modal = page.locator('#' + modalId);
    await modal.waitFor({ state: 'attached', timeout: 15000 });

    const frame = page.frameLocator(`#${modalId} iframe`);
    await frame.locator('#adminForm').waitFor({ state: 'attached', timeout: 30000 });
    return frame;
}

/** Click the green (primary/OK) footer button of a joomla-dialog. */
async function clickDialogOk(page, modalId) {
    await page.locator(`#${modalId} button.btn-success`).first().click();
}

/** Click the cancel (secondary) footer button of a joomla-dialog. */
async function clickDialogCancel(page, modalId) {
    await page.locator(`#${modalId} button.btn-secondary`).first().click();
}

/**
 * Check the first `count` cid[] checkboxes of a list view and return the
 * selected values. Returns [] when the list has no rows.
 */
async function selectRows(page, count) {
    const boxes = page.locator('input[name="cid[]"]');
    const total = await boxes.count();
    const values = [];
    for (let i = 0; i < Math.min(count, total); i++) {
        const box = boxes.nth(i);
        await box.check();
        await expect(box).toBeChecked();
        values.push(await box.getAttribute('value'));
    }
    return values;
}

/**
 * Exercise every jform control inside an iframe form: radios get each option
 * clicked through its label (the real switcher UI), selects cycle through all
 * options, checkboxes toggle on/off. Hidden and cid fields are skipped, as are
 * free-text inputs (required text fields are filled by the caller).
 */
async function exerciseJformControls(scope) {
    const controls = await scope.locator('[name^="jform["]').evaluateAll((els) =>
        els.map((e) => ({
            name: e.name,
            tag: e.tagName.toLowerCase(),
            type: (e.type || '').toLowerCase()
        }))
    );

    const seen = new Set();
    for (const c of controls) {
        if (!c.name || c.name === 'jform[cid]' || c.type === 'hidden' || seen.has(c.name)) {
            continue;
        }
        seen.add(c.name);

        const control = scope.locator(`[name="${c.name}"]`).first();
        const group = control.locator('xpath=ancestor::div[contains(@class,"control-group")][1]');
        if (await group.count() && !(await group.isVisible())) {
            continue;
        }

        if (c.tag === 'select') {
            const values = await control.locator('option:not([disabled])').evaluateAll((options) => options.map((option) => option.value));
            for (const value of values) {
                await control.selectOption(value);
                await expect(control).toHaveValue(value);
            }
        } else if (c.type === 'radio') {
            const options = await scope.locator(`input[type="radio"][name="${c.name}"]`).evaluateAll((els) =>
                els.map((el) => ({ id: el.id, value: el.value }))
            );
            for (const option of options) {
                const radio = scope.locator(`#${option.id}`);
                await radio.check();
                await expect(radio, `radio ${c.name}=${option.value} should be checked after selecting it`).toBeChecked();
            }
        } else if (c.type === 'checkbox') {
            const box = control;
            await box.check();
            await expect(box).toBeChecked();
            await box.uncheck();
            await expect(box).not.toBeChecked();
        }
    }
}

async function activateTab(frame, id) {
    const panel = frame.locator(`joomla-tab-element[id="${id}"]`);
    if (!(await panel.evaluate((element) => element.hasAttribute('active')))) {
        const toggle = frame.locator(`joomla-tab [aria-controls="${id}"]:visible`).first();
        await expect(toggle).toBeVisible();
        await toggle.click();
        await expect(panel).toHaveAttribute('active', '');
    }
}

async function exerciseJform(frame) {
    const panels = frame.locator('joomla-tab-element');
    const panelCount = await panels.count();
    if (!panelCount) {
        await exerciseJformControls(frame.locator('#adminForm'));
        return;
    }

    const initialActiveId = await panels.evaluateAll((elements) =>
        elements.find((element) => element.hasAttribute('active'))?.id || null
    );

    for (let i = 0; i < panelCount; i++) {
        const panel = panels.nth(i);
        const id = await panel.getAttribute('id');
        await activateTab(frame, id);
        await exerciseJformControls(panel);
    }

    if (initialActiveId) {
        const initialPanel = frame.locator(`joomla-tab-element[id="${initialActiveId}"]`);
        if (!(await initialPanel.evaluate((element) => element.hasAttribute('active')))) {
            const toggle = frame.locator(`joomla-tab [aria-controls="${initialActiveId}"]:visible`).first();
            await expect(toggle).toBeVisible();
            await toggle.click();
            await expect(initialPanel).toHaveAttribute('active', '');
        }
    }
}

/**
 * Click the dialog OK button and wait for the browser download produced by
 * the raw-format export response (Content-Disposition: attachment).
 */
async function clickOkAndDownload(page, modalId) {
    let cleanup = () => {};
    const outcome = new Promise((resolve, reject) => {
        const finish = (result) => {
            cleanup();
            resolve(result);
        };
        const onDownload = (download) => finish({download});
        const onResponse = (response) => {
            const url = response.url();
            if (response.status() >= 400 && /[?&]option=com_j2xml(?:&|$)/i.test(url) && /[?&]task=[^&]+\.display(?:&|$)/i.test(url)) {
                finish({response});
            }
        };
        const timeout = setTimeout(() => {
            cleanup();
            reject(new Error('Timed out waiting for export download or error response'));
        }, 60000);
        cleanup = () => {
            clearTimeout(timeout);
            page.off('download', onDownload);
            page.off('response', onResponse);
        };
        page.on('download', onDownload);
        page.on('response', onResponse);
    });

    try {
        await clickDialogOk(page, modalId);
        const result = await outcome;
        if (result.response) {
            throw new Error(`Export request failed with HTTP ${result.response.status()}: ${result.response.url()}`);
        }
        return result.download;
    } finally {
        cleanup();
    }
}

/**
 * Read a downloaded export file. Transparently gunzips .gz payloads and
 * returns { filename, text }.
 */
async function readDownload(download) {
    const filename = download.suggestedFilename();
    const filePath = await download.path();
    let buffer = fs.readFileSync(filePath);
    if (filename.endsWith('.gz') || (buffer[0] === 0x1f && buffer[1] === 0x8b)) {
        buffer = zlib.gunzipSync(buffer);
    }
    return { filename, text: buffer.toString('utf8') };
}

module.exports = {
    ADMIN_USER,
    ADMIN_PASS,
    collectPageErrors,
    expectNoBrowserErrors,
    adminLogin,
    dismissGuidedTour,
    activateTab,
    clickToolbarButton,
    openModalDialog,
    clickDialogOk,
    clickDialogCancel,
    selectRows,
    exerciseJform,
    clickOkAndDownload,
    readDownload
};
