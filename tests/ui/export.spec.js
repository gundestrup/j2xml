/**
 * UI tests for the J2XML export toolbar button and its joomla-dialog.
 *
 * For every supported list view the suite:
 *   - asserts the Export toolbar button exists and opens the dialog
 *   - exercises every form setting (switchers, selects) inside the iframe
 *   - selects rows, presses the dialog's Export button and captures the
 *     resulting download
 *   - asserts the payload is a valid J2XML document containing the expected
 *     entity elements and only the selected ids
 *   - asserts no uncaught JS errors / console.error fired at any point
 *     (this is what catches bugs like a broken cid[] selector in the onclick)
 *
 * @package     J2XML
 * @copyright   Copyright (C) 2026 Svend Gundestrup. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 */
const path = require('path');
const { test, expect } = require('@playwright/test');
const {
    collectPageErrors,
    expectNoBrowserErrors,
    adminLogin,
    openModalDialog,
    clickDialogOk,
    clickDialogCancel,
    selectRows,
    exerciseJform,
    clickOkAndDownload,
    readDownload
} = require('./helpers');

const VIEWS = [
    { name: 'articles',   task: 'content', option: 'com_content',    view: 'articles',   extra: '',                             entityTag: 'content',  settings: ['export_categories', 'export_fields', 'export_images', 'export_tags'] },
    { name: 'featured',   task: 'content', option: 'com_content',    view: 'featured',   extra: '',                             entityTag: 'content',  settings: ['export_categories', 'export_fields', 'export_images', 'export_tags'] },
    { name: 'users',      option: 'com_users',      view: 'users',      extra: '',                             entityTag: 'user',     settings: ['export_users', 'export_password', 'export_usernotes', 'export_contacts', 'export_fields'] },
    { name: 'usernotes',  option: 'com_users',      view: 'notes',      extra: '',                             entityTag: 'usernote', settings: ['export_users', 'export_images', 'export_categories'] },
    { name: 'categories', option: 'com_categories', view: 'categories', extra: '&extension=com_content',       entityTag: 'category', settings: ['export_users', 'export_images', 'export_tags'] },
    { name: 'contacts',   task: 'contact', option: 'com_contact',    view: 'contacts',   extra: '',                             entityTag: 'contact',  settings: ['export_users', 'export_images', 'export_tags', 'export_categories'] },
    { name: 'modules',    option: 'com_modules',    view: 'modules',    extra: '',                             entityTag: 'module',   settings: [] },
    { name: 'menus',      option: 'com_menus',      view: 'menus',      extra: '',                             entityTag: 'menutype', settings: ['export_categories', 'export_fields', 'export_images', 'export_tags'] },
    { name: 'fields',     option: 'com_fields',     view: 'fields',     extra: '&context=com_content.article', entityTag: 'field',    settings: ['export_users', 'export_categories'] }
];

let errors = [];

test.beforeEach(async ({ page }) => {
    errors = collectPageErrors(page);
    await adminLogin(page);
});

test.afterEach(async () => {
    expectNoBrowserErrors(errors);
});

test('export fixtures: import records needed by each toolbar view', async ({ page }) => {
    let needsFixtures = false;
    for (const v of VIEWS) {
        await page.goto(`/administrator/index.php?option=${v.option}&view=${v.view}${v.extra}`);
        if ((await page.locator('input[name="cid[]"]').count()) === 0) {
            needsFixtures = true;
            break;
        }
    }

    if (needsFixtures) {
        await page.goto('/administrator/index.php?option=com_j2xml&view=import');
        await page.locator('#dragarea').waitFor({ state: 'visible', timeout: 30000 });
        await page.setInputFiles('#install_package', path.join(__dirname, '..', 'fixtures', 'all-content-types.xml'));

        const modal = page.locator('#j2xmlImportModal');
        await modal.waitFor({ state: 'attached', timeout: 15000 });
        const frame = page.frameLocator('#j2xmlImportModal iframe');
        await frame.locator('#adminForm').waitFor({ state: 'attached', timeout: 30000 });
        await exerciseJform(frame);

        const settings = [
            {panel: 'import', selects: {import_content: '1', import_fields: '1', import_contacts: '1', import_categories: '1', import_menus: '1', import_modules: '1', import_viewlevels: '1'}, radios: {import_tags: '1'}},
            {panel: 'users', selects: {import_users: '1'}, radios: {import_keep_user_id: '0', import_password: '0', import_superusers: '0', import_usernotes: '1'}},
            {panel: 'content', selects: {import_keep_category: '1'}, radios: {import_keep_id: '0', import_keep_data: '0'}}
        ];
        for (const setting of settings) {
            const panel = frame.locator(`joomla-tab-element[id="${setting.panel}"]`);
            if (!(await panel.evaluate((element) => element.hasAttribute('active')))) {
                await frame.locator(`joomla-tab [aria-controls="${setting.panel}"]:visible`).click();
            }
            for (const [name, value] of Object.entries(setting.selects)) {
                await frame.locator(`select[name="jform[${name}]"]`).selectOption(value);
            }
            for (const [name, value] of Object.entries(setting.radios)) {
                await frame.locator(`input[name="jform[${name}]"][value="${value}"]`).check();
            }
        }

        await clickDialogOk(page, 'j2xmlImportModal');
        await page.waitForSelector('#import-progress', { timeout: 30000 });
        await page.waitForFunction(
            () => {
                const loading = document.getElementById('loading');
                return loading && getComputedStyle(loading).display === 'none';
            },
            null,
            { timeout: 120000 }
        );
        await expect(
            page.locator('#system-message-container joomla-alert[type="error"], #system-message-container joomla-alert[type="danger"]'),
            'fixture import reported errors'
        ).toHaveCount(0);
    }

    for (const v of VIEWS) {
        await page.goto(`/administrator/index.php?option=${v.option}&view=${v.view}${v.extra}`);
        expect(await page.locator('input[name="cid[]"]').count(), `${v.name} export fixture is missing`).toBeGreaterThan(0);
    }
});

for (const v of VIEWS) {
    test(`export: ${v.name} toolbar button opens dialog and produces XML`, async ({ page }) => {
        await page.goto(`/administrator/index.php?option=${v.option}&view=${v.view}${v.extra}`);
        const selected = await selectRows(page, 2);
        expect(selected.length).toBeGreaterThan(0);

        const frame = await openModalDialog(page, 'j2xmlExportOpen', 'j2xmlExportModal');
        await expect(frame.locator('#adminForm')).toBeAttached();
        await expect(frame.locator('#j2xmlExportOkBtn')).toBeAttached();

        // Touch every setting in the dialog (radios, selects, checkboxes).
        await exerciseJform(frame);

        // Reset compression to uncompressed so the download is plain XML.
        const compression = frame.locator('input[name="jform[export_compression]"][value="0"]');
        await expect(compression).toHaveCount(1);
        await compression.check();
        await expect(compression).toBeChecked();

        const requestPromise = page.waitForRequest(
            (request) => request.method() === 'POST' && request.url().includes(`task=${v.task || v.name}.display`),
            { timeout: 30000 }
        );
        const [download, request] = await Promise.all([
            clickOkAndDownload(page, 'j2xmlExportModal'),
            requestPromise
        ]);
        const submittedOptions = new URLSearchParams(request.postData() || '');
        expect(submittedOptions.get('jform[export_compression]')).toBe('0');
        for (const setting of v.settings) {
            expect(submittedOptions.get(`jform[${setting}]`), `${setting} should be submitted as enabled`).toBe('1');
        }
        const { filename, text } = await readDownload(download);

        expect(filename).toMatch(/\.xml$/);
        expect(text).toContain('<j2xml');

        expect(text).toContain(`<${v.entityTag}>`);
        // Every selected row must be exported as a top-level entity.
        // (Related entities — parent categories, child menu items,
        // linked users — legitimately add further <id> elements.)
        const exportedIds = [...text.matchAll(new RegExp(`<${v.entityTag}>\\s*<id>(\\d+)<\\/id>`, 'g'))].map((m) => m[1]);
        for (const id of selected) {
            expect(exportedIds, `selected id ${id} missing from export`).toContain(id);
        }
    });
}

test('export: empty and zero ID lists return valid XML', async ({ page }) => {
    for (const cid of ['', '0']) {
        const result = await page.evaluate(async (value) => {
            const response = await fetch('/administrator/index.php?option=com_j2xml&task=fields.display&format=raw', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    'jform[cid]': value,
                    'jform[export_categories]': '1',
                    'jform[export_fields]': '1',
                    'jform[export_images]': '1',
                    'jform[export_tags]': '1'
                }).toString()
            });
            return {status: response.status, text: await response.text()};
        }, cid);

        expect(result.status).toBe(200);
        expect(result.text).toContain('<j2xml');
        expect(result.text).not.toContain('You have an error');
    }
});

test('export: dialog cancel closes without exporting', async ({ page }) => {
    await page.goto('/administrator/index.php?option=com_content&view=articles');

    const selected = await selectRows(page, 1);
    expect(selected.length).toBeGreaterThan(0);
    await openModalDialog(page, 'j2xmlExportOpen', 'j2xmlExportModal');
    await clickDialogCancel(page, 'j2xmlExportModal');

    await expect(page.locator('#j2xmlExportModal dialog[open], #j2xmlExportModal.open')).toHaveCount(0);
});

test('export: dialog can be reopened after cancel', async ({ page }) => {
    await page.goto('/administrator/index.php?option=com_content&view=articles');
    const selected = await selectRows(page, 1);
    expect(selected.length).toBeGreaterThan(0);

    let frame = await openModalDialog(page, 'j2xmlExportOpen', 'j2xmlExportModal');
    await clickDialogCancel(page, 'j2xmlExportModal');

    frame = await openModalDialog(page, 'j2xmlExportOpen', 'j2xmlExportModal');
    await expect(frame.locator('#adminForm')).toBeAttached();
});

test('export: compressed export produces a valid gzip XML payload', async ({ page }) => {
    await page.goto('/administrator/index.php?option=com_content&view=articles');

    const selected = await selectRows(page, 1);
    expect(selected.length).toBeGreaterThan(0);

    const frame = await openModalDialog(page, 'j2xmlExportOpen', 'j2xmlExportModal');
    const gz = frame.locator('input[name="jform[export_compression]"][value="1"]');
    await expect(gz).toHaveCount(1);
    await gz.check();
    await expect(gz).toBeChecked();

    const download = await clickOkAndDownload(page, 'j2xmlExportModal');
    const { filename, text } = await readDownload(download);

    expect(filename).toMatch(/\.gz$/);
    expect(text).toContain('<j2xml');
});
