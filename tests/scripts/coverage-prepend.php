<?php
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
            @mkdir($dir, 0777, true);
            @chmod($dir, 0777);
        }

        @file_put_contents(
            $dir . '/' . uniqid('cov_' . getmypid() . '_', true) . '.cov',
            serialize($data)
        );
    }
});
