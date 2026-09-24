/**
 * Playwright configuration for the J2XML UI test suite.
 *
 * The suite runs the same spec files against both Docker Joomla instances.
 * URLs and per-instance remote targets are env-driven so the same config
 * works for the MySQL stack (8085/8086) and the PostgreSQL stack (8185/8186).
 */
const fs = require('fs');
const { execSync } = require('child_process');
const { defineConfig } = require('@playwright/test');

const JOOMLA5_URL = process.env.JOOMLA5_URL || 'http://localhost:8085';
const JOOMLA6_URL = process.env.JOOMLA6_URL || 'http://localhost:8086';

// Use an installed Chrome when available — avoids downloading a separate
// Chromium build. In CI the runner installs Chromium instead; force the
// choice with J2XML_UI_BROWSER=chrome|chromium.
function systemChromeAvailable() {
    if (fs.existsSync('/Applications/Google Chrome.app')) {
        return true;
    }
    try {
        execSync('command -v google-chrome || command -v google-chrome-stable || command -v chromium', { stdio: 'ignore' });
        return true;
    } catch {
        return false;
    }
}

const useSystemChrome =
    process.env.J2XML_UI_BROWSER === 'chrome' ||
    (process.env.J2XML_UI_BROWSER !== 'chromium' && process.env.CI !== 'true' && systemChromeAvailable());

const launchOptions = useSystemChrome ? { channel: 'chrome' } : {};

module.exports = defineConfig({
    testDir: __dirname,
    testMatch: '**/*.spec.js',
    timeout: 120000,
    expect: { timeout: 15000 },
    // The two Joomla instances share state per project and tests within a
    // project build on each other (import seeds content for send), so keep
    // execution strictly sequential.
    fullyParallel: false,
    workers: 1,
    retries: 0,
    reporter: [['list'], ['html', { outputFolder: 'playwright-report', open: 'never' }]],
    use: {
        acceptDownloads: true,
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
        ignoreHTTPSErrors: true,
        // Bound every action/navigation so a hidden element can never stall a
        // test until the global test timeout kills it.
        actionTimeout: 15000,
        navigationTimeout: 30000,
        ...launchOptions
    },
    projects: [
        {
            name: 'joomla5',
            use: {
                baseURL: JOOMLA5_URL,
                remoteURL: JOOMLA6_URL,
                remoteToken: process.env.J2XML_TOKEN_J6 || ''
            }
        },
        {
            name: 'joomla6',
            use: {
                baseURL: JOOMLA6_URL,
                remoteURL: JOOMLA5_URL,
                remoteToken: process.env.J2XML_TOKEN_J5 || ''
            }
        }
    ]
});
