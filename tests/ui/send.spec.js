/**
 * UI tests for the J2XML "Send" toolbar button (Joomla Webservices transfer).
 *
 * Covers: button presence, dialog open, required-field validation, and —
 * when J2XML_TOKEN_J5/J2XML_TOKEN_J6 are provided — a real cross-instance
 * send to the other Joomla container's REST endpoint.
 *
 * @package     J2XML
 * @copyright   Copyright (C) 2026 Svend Gundestrup. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 */
const zlib = require('zlib');
const { test, expect } = require('@playwright/test');
const {
    collectPageErrors,
    expectNoBrowserErrors,
    adminLogin,
    openModalDialog,
    clickDialogOk,
    selectRows,
    exerciseJform
} = require('./helpers');

const ARTICLES_URL = '/administrator/index.php?option=com_content&view=articles';

async function createSendArticle(page) {
    const suffix = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
    const title = `J2XML Send ${suffix}`;
    const alias = `j2xml-send-${suffix}`;
    const xml = `<?xml version="1.0" encoding="UTF-8"?>\n<j2xml version="21.12.0"><content><id>0</id><title>${title}</title><alias>${alias}</alias><introtext><![CDATA[Send UI test article.]]></introtext><state>1</state><catid>2</catid><language>*</language><publish_up>2020-01-01 00:00:00</publish_up><publish_down>0000-00-00 00:00:00</publish_down></content></j2xml>`;

    await page.goto('/administrator/index.php?option=com_j2xml&view=import');
    await page.setInputFiles('#install_package', {
        name: 'j2xml-send-test.xml',
        mimeType: 'application/xml',
        buffer: Buffer.from(xml)
    });
    await page.locator('#j2xmlImportModal').waitFor({state: 'attached'});
    const frame = page.frameLocator('#j2xmlImportModal iframe');
    await frame.locator('#adminForm').waitFor({state: 'attached'});
    await frame.locator('select[name="jform[import_content]"]').selectOption('1');
    await clickDialogOk(page, 'j2xmlImportModal');
    await page.waitForSelector('#import-progress', {timeout: 30000});
    await page.waitForFunction(() => {
        const loading = document.getElementById('loading');
        return loading && getComputedStyle(loading).display === 'none';
    }, null, {timeout: 60000});
    await expect(
        page.locator('#system-message-container joomla-alert[type="error"], #system-message-container joomla-alert[type="danger"]')
    ).toHaveCount(0);

    await page.goto(ARTICLES_URL);
    const row = page.locator('tbody tr').filter({hasText: title}).first();
    const checkbox = row.locator('input[name="cid[]"]');
    await checkbox.check();
    return {title, alias, id: await checkbox.getAttribute('value')};
}

async function sendSelectedArticle(page, remoteURL, remoteToken, compression) {
    const frame = await openModalDialog(page, 'j2xmlSendOpen', 'j2xmlSendModal');
    await exerciseJform(frame);
    const compressionField = frame.locator(`input[name="jform[send_compression]"][value="${compression}"]`);
    await compressionField.check();
    await expect(compressionField).toBeChecked();
    await frame.locator('input[name="jform[remote_url]"]').fill(remoteURL);
    await frame.locator('input[name="jform[token]"]').fill(remoteToken);

    const remoteEndpoint = new URL('/api/index.php/v1/j2xml/import', remoteURL).toString();
    const sendRequest = page.waitForRequest(
        (request) => request.url() === remoteEndpoint && request.method() === 'POST',
        { timeout: 60000 }
    );
    const sendResponse = page.waitForResponse(
        (response) => response.url() === remoteEndpoint && response.request().method() === 'POST',
        { timeout: 60000 }
    );
    await clickDialogOk(page, 'j2xmlSendModal');

    const [request, response] = await Promise.all([sendRequest, sendResponse]);
    expect(response.status()).toBe(200);
    const body = request.postDataBuffer();
    expect(body).not.toBeNull();
    const payloadBuffer = body || Buffer.alloc(0);
    let payload;
    if (compression === '1') {
        expect(payloadBuffer.subarray(0, 2)).toEqual(Buffer.from([0x1f, 0x8b]));
        payload = JSON.parse(zlib.gunzipSync(payloadBuffer).toString('utf8'));
    } else {
        expect(payloadBuffer[0]).toBe(0x7b);
        payload = JSON.parse(payloadBuffer.toString('utf8'));
    }
    expect(payload.options).toMatchObject({fields: '2', images: '1', tags: '1'});
    expect(payload.options).not.toHaveProperty('compression');
    await expect(page.locator('#send-progress')).toHaveCount(0, {timeout: 60000});

    return {payload, response};
}

let errors = [];

test.beforeEach(async ({ page }) => {
    errors = collectPageErrors(page);
    await adminLogin(page);
});

test.afterEach(async () => {
    expectNoBrowserErrors(errors);
});

test('send: toolbar button opens the send dialog with required fields', async ({ page }) => {
    await page.goto(ARTICLES_URL);
    const selected = await selectRows(page, 1);
    expect(selected.length).toBeGreaterThan(0);

    // The Send button only renders when the webservices plugin is enabled;
    // the test runner enables it on both instances.
    await expect(page.locator('#j2xmlSendOpen')).toHaveCount(1);

    const frame = await openModalDialog(page, 'j2xmlSendOpen', 'j2xmlSendModal');
    await expect(frame.locator('#adminForm')).toBeAttached();
    await expect(frame.locator('input[name="jform[remote_url]"]')).toBeAttached();
    await expect(frame.locator('input[name="jform[token]"]')).toBeAttached();

    // Exercise the remaining settings (compression, fields, images, tags).
    await exerciseJform(frame);
});

test('send: empty required fields are blocked by form validation', async ({ page }) => {
    await page.goto(ARTICLES_URL);
    const selected = await selectRows(page, 1);
    expect(selected.length).toBeGreaterThan(0);

    const frame = await openModalDialog(page, 'j2xmlSendOpen', 'j2xmlSendModal');
    await clickDialogOk(page, 'j2xmlSendModal');

    // Validation failure keeps the dialog open and flags the invalid fields.
    await expect(page.locator('#j2xmlSendModal')).toBeAttached();
    await expect(frame.locator('#adminForm')).toBeAttached();
    const invalidCount = await frame.locator('#adminForm :invalid').count();
    expect(invalidCount).toBeGreaterThan(0);
});

test('send: compression off posts plain JSON to the remote API', async ({ page }, testInfo) => {
    const remoteURL = testInfo.project.use.remoteURL;
    const remoteToken = testInfo.project.use.remoteToken;
    test.skip(!remoteURL || !remoteToken, 'Remote instance URL/token not configured');

    const sentArticle = await createSendArticle(page);
    expect(sentArticle.id).toBeTruthy();
    const {payload} = await sendSelectedArticle(page, remoteURL, remoteToken, '0');
    expect(payload.data).toContain('<j2xml');
});

test('send: real compressed transfer to the remote instance via REST API', async ({ page, browser }, testInfo) => {
    const remoteURL = testInfo.project.use.remoteURL;
    const remoteToken = testInfo.project.use.remoteToken;
    test.skip(!remoteURL || !remoteToken, 'Remote instance URL/token not configured');

    const sentArticle = await createSendArticle(page);
    expect(sentArticle.id).toBeTruthy();

    // URL is intentionally given without a trailing slash — the JS must
    // normalise it before appending api/index.php/v1/j2xml/import.
    const {payload} = await sendSelectedArticle(page, remoteURL, remoteToken, '1');
    expect(payload.data).toContain('<j2xml');

    const remoteContext = await browser.newContext({baseURL: remoteURL});
    try {
        const remotePage = await remoteContext.newPage();
        const remoteErrors = collectPageErrors(remotePage);
        await adminLogin(remotePage);
        await remotePage.goto(ARTICLES_URL);
        await expect(remotePage.locator('tbody tr').filter({hasText: sentArticle.title}).first()).toBeVisible({timeout: 30000});
        expectNoBrowserErrors(remoteErrors);
    } finally {
        await remoteContext.close();
    }
});
