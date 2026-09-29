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
 * Read installed J2XML versions or clear Joomla's plugin cache in a test container.
 *
 * Expects /tmp/j2xml-bootstrap.php to be present (copied from
 * tests/scripts/bootstrap.php by test-update-server.sh).
 */

declare(strict_types=1);

require '/tmp/j2xml-bootstrap.php';

$mode = $argv[1] ?? 'version';
$db = getDb();
$query = $db->getQuery(true)
    ->select('manifest_cache')
    ->from('#__extensions')
    ->where("(element = 'pkg_j2xml' AND type = 'package')")
    ->orWhere("(element = 'com_j2xml' AND type = 'component')")
    ->orWhere("(element = 'eshiol/J2xml' AND type = 'library')")
    ->orWhere("(element = 'j2xml' AND type = 'plugin' AND folder IN ('system', 'webservices'))");
$caches = $db->setQuery($query)->loadColumn();
$versions = [];
foreach ($caches as $cache) {
    $version = json_decode((string) $cache, true)['version'] ?? null;
    if (is_string($version) && preg_match('/^[0-9]+\\.[0-9]+\\.[0-9]+$/', $version)) {
        $versions[] = $version;
    }
}

if ($mode === 'versions') {
    echo implode("\n", $versions);
    exit($versions === [] ? 1 : 0);
}

$package = $db->setQuery(
    $db->getQuery(true)
        ->select('manifest_cache')
        ->from('#__extensions')
        ->where("element = 'pkg_j2xml'")
        ->where("type = 'package'")
)->loadResult();
$version = json_decode((string) $package, true)['version'] ?? null;
if (!is_string($version) || $version === '') {
    fwrite(STDERR, "pkg_j2xml is not installed\n");
    exit(1);
}
echo $version;
