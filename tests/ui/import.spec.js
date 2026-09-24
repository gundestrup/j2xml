/**
 * UI tests for the J2XML import view and its options dialog.
 *
 * Covers the real browser flow: selecting a file on the import screen opens
 * the options dialog, all settings are exercised, and pressing Import runs
 * the chunked AJAX importer. Malformed and unsupported files must produce an
 * error alert instead of a crash.
 *
 * @package     J2XML
 * @copyright   Copyright (C) 2026 Svend Gundestrup. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 */
const fs = require('fs');
const path = require('path');
const zlib = require('zlib');
const { test, expect } = require('@playwright/test');
const {
    collectPageErrors,
    expectNoBrowserErrors,
    adminLogin,
    clickDialogOk,
    clickDialogCancel,
    activateTab,
    exerciseJform
} = require('./helpers');

const FIXTURES = path.join(__dirname, '..', 'fixtures');
const IMPORT_URL = '/administrator/index.php?option=com_j2xml&view=import';

let errors = [];

test.beforeEach(async ({ page }) => {
    errors = collectPageErrors(page);
    await adminLogin(page);
    await page.goto(IMPORT_URL);
    await expect(page.locator('#dragarea')).toBeAttached();
});

test.afterEach(async () => {
    expectNoBrowserErrors(errors);
});

/**
 * Select a file in the (hidden) file input and wait for the options dialog.
 */
async function uploadFile(page, fixture) {
    await page.setInputFiles('#install_package', path.join(FIXTURES, fixture));
    const modal = page.locator('#j2xmlImportModal');
    await modal.waitFor({ state: 'attached', timeout: 15000 });
    const frame = page.frameLocator('#j2xmlImportModal iframe');
    await frame.locator('#adminForm').waitFor({ state: 'attached', timeout: 30000 });
    return frame;
}

test('import: selecting a current-format file opens the options dialog', async ({ page }) => {
    const frame = await uploadFile(page, 'all-content-types.xml');
    await expect(frame.locator('#adminForm')).toBeAttached();
});

test('import: options dialog cancel closes without importing', async ({ page }) => {
    await uploadFile(page, 'all-content-types.xml');
    await clickDialogCancel(page, 'j2xmlImportModal');
    await expect(page.locator('#import-progress')).toHaveCount(0);
});

test('import: current-format document completes the UI flow', async ({ page }) => {
    const frame = await uploadFile(page, 'all-content-types.xml');

    // Exercise every option across all tabs (radios, selects, showon fields).
    await exerciseJform(frame);
    await activateTab(frame, 'import');
    await frame.locator('select[name="jform[import_content]"]').selectOption('2');
    await frame.locator('select[name="jform[import_categories]"]').selectOption('2');
    for (const name of ['import_fields', 'import_contacts', 'import_menus', 'import_modules', 'import_viewlevels', 'import_weblinks']) {
        const control = frame.locator(`select[name="jform[${name}]"]`);
        if (await control.count()) {
            await control.selectOption('0');
        }
    }
    for (const name of ['import_tags', 'import_images']) {
        const control = frame.locator(`input[name="jform[${name}]"][value="0"]`);
        if (await control.count()) {
            await control.check();
        }
    }
    await activateTab(frame, 'users');
    await frame.locator('select[name="jform[import_users]"]').selectOption('0');
    await activateTab(frame, 'content');
    const keepId = frame.locator('input[name="jform[import_keep_id]"][value="0"]');
    await keepId.check();
    await expect(keepId).toBeChecked();

    await clickDialogOk(page, 'j2xmlImportModal');

    // The importer must show progress and finish without a hard error.
    await page.waitForSelector('#import-progress', { timeout: 30000 });
    await page.waitForFunction(
        () => {
            const loading = document.getElementById('loading');
            return loading && getComputedStyle(loading).display === 'none';
        },
        null,
        { timeout: 120000 }
    );

    const alerts = page.locator('#system-message-container joomla-alert');
    await expect(alerts.first()).toBeAttached({ timeout: 30000 });
    await expect(
        page.locator('#system-message-container joomla-alert[type="error"], #system-message-container joomla-alert[type="danger"]'),
        'import reported errors'
    ).toHaveCount(0);

    // Imported articles must be visible in the articles list.
    await page.goto('/administrator/index.php?option=com_content&view=articles');
    await expect(page.locator('#adminForm')).toBeAttached();
    await expect(page.locator('text=Fixture Article One').first()).toBeAttached();
});

test('import: legacy J2XML 12.5 document remains compatible', async ({ page }) => {
    const frame = await uploadFile(page, 'legacy-j2xml-12.5-articles.xml');
    await activateTab(frame, 'import');
    await frame.locator('select[name="jform[import_content]"]').selectOption('2');
    await frame.locator('select[name="jform[import_categories]"]').selectOption('0');
    await frame.locator('select[name="jform[import_fields]"]').selectOption('0');
    await frame.locator('select[name="jform[import_contacts]"]').selectOption('0');
    await frame.locator('select[name="jform[import_menus]"]').selectOption('0');
    await frame.locator('select[name="jform[import_modules]"]').selectOption('0');
    await frame.locator('select[name="jform[import_viewlevels]"]').selectOption('0');
    await frame.locator('select[name="jform[import_weblinks]"]').selectOption('0');
    for (const name of ['import_tags', 'import_images']) {
        const control = frame.locator(`input[name="jform[${name}]"][value="0"]`);
        if (await control.count()) {
            await control.check();
        }
    }
    await activateTab(frame, 'users');
    await frame.locator('select[name="jform[import_users]"]').selectOption('0');
    await activateTab(frame, 'content');
    await frame.locator('input[name="jform[import_keep_id]"][value="0"]').check();
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
        page.locator('#system-message-container joomla-alert[type="error"], #system-message-container joomla-alert[type="danger"]')
    ).toHaveCount(0);
    await page.goto('/administrator/index.php?option=com_content&view=articles');
    await expect(page.locator('text=Test Article with Special Characters').first()).toBeAttached();
});

test('import: gzip-compressed XML imports through the admin uploader', async ({ page }) => {
    const title = 'J2XML Gzip Import Regression';
    const xml = fs.readFileSync(path.join(FIXTURES, 'all-content-types.xml'), 'utf8')
        .replace('<title>Fixture Article One</title>', `<title>${title}</title>`)
        .replace('<alias>fixture-article-one</alias>', '<alias>j2xml-gzip-import-regression</alias>');
    await page.setInputFiles('#install_package', {
        name: 'j2xml-gzip-import.xml.gz',
        mimeType: 'application/gzip',
        buffer: zlib.gzipSync(Buffer.from(xml))
    });

    const modal = page.locator('#j2xmlImportModal');
    await modal.waitFor({state: 'attached', timeout: 15000});
    const frame = page.frameLocator('#j2xmlImportModal iframe');
    await frame.locator('#adminForm').waitFor({state: 'attached', timeout: 30000});
    await activateTab(frame, 'import');
    await frame.locator('select[name="jform[import_content]"]').selectOption('2');
    for (const name of ['import_fields', 'import_contacts', 'import_menus', 'import_modules', 'import_viewlevels', 'import_weblinks']) {
        const control = frame.locator(`select[name="jform[${name}]"]`);
        if (await control.count()) {
            await control.selectOption('0');
        }
    }
    await frame.locator('select[name="jform[import_categories]"]').selectOption('2');
    for (const name of ['import_tags', 'import_images']) {
        const control = frame.locator(`input[name="jform[${name}]"][value="0"]`);
        if (await control.count()) {
            await control.check();
        }
    }
    await activateTab(frame, 'users');
    await frame.locator('select[name="jform[import_users]"]').selectOption('0');
    await activateTab(frame, 'content');
    await frame.locator('input[name="jform[import_keep_id]"][value="0"]').check();
    await clickDialogOk(page, 'j2xmlImportModal');

    await page.waitForSelector('#import-progress', {timeout: 30000});
    await page.waitForFunction(() => {
        const loading = document.getElementById('loading');
        return loading && getComputedStyle(loading).display === 'none';
    }, null, {timeout: 120000});
    await expect(
        page.locator('#system-message-container joomla-alert[type="error"], #system-message-container joomla-alert[type="danger"]')
    ).toHaveCount(0);
    await page.goto('/administrator/index.php?option=com_content&view=articles');
    await expect(page.locator(`text=${title}`).first()).toBeAttached();
});

test('import: malformed file shows an error and no dialog', async ({ page }) => {
    await page.setInputFiles('#install_package', path.join(FIXTURES, 'malformed.xml'));

    await expect(page.locator('#system-message-container joomla-alert').first()).toBeAttached({ timeout: 15000 });
    await expect(page.locator('#j2xmlImportModal')).toHaveCount(0);
});

test('import: unsupported version file shows an error and no dialog', async ({ page }) => {
    await page.setInputFiles('#install_package', path.join(FIXTURES, 'unsupported-version.xml'));

    await expect(page.locator('#system-message-container joomla-alert').first()).toBeAttached({ timeout: 15000 });
    await expect(page.locator('#j2xmlImportModal')).toHaveCount(0);
});
