<?php
/**
 * @package     J2XML
 *
 * @copyright   Copyright (C) 2026 Svend Gundestrup. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */
/**
 * J2XML integration-test coverage collector.
 *
 * Installed as PHP's auto_prepend_file inside the Joomla test containers by
 * tests/scripts/coverage-enable.sh. Records per-request line coverage via
 * pcov and dumps the raw data (Xdebug-style line format) to /tmp/j2xml-cov
 * for later merging by tests/scripts/merge-coverage.php.
 */

if (!extension_loaded('pcov')) {
    return;
}

\pcov\start();

register_shutdown_function(static function (): void {
    \pcov\stop();

    $files = \pcov\waiting();

    if (!$files) {
        return;
    }

    $data = \pcov\collect(\pcov\inclusive, $files);

    \pcov\clear();

    if ($data) {
        $dir = '/tmp/j2xml-cov';

        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }

        @file_put_contents(
            $dir . '/' . uniqid('cov_' . getmypid() . '_', true) . '.cov',
            serialize($data)
        );
    }
});
