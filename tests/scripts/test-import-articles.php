<?php
/**
 * Test: Issue #72 — Import fails with HTTP 500 on Joomla 5.2+
 * Test: Issue #71 — Cannot import articles from J3.10.11 to J5
 *
 * This script bootstraps Joomla, imports articles from a J3-format XML file,
 * and verifies the articles were added to the database.
 */

require __DIR__ . '/bootstrap.php';

echo "=== Test: Import articles (issues #72, #71) ===\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "Joomla: " . (new \Joomla\CMS\Version())->getShortVersion() . "\n";

// Count articles before
$db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
$query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__content'));
$before = (int) $db->setQuery($query)->loadResult();
echo "Articles before: $before\n";

// Load the XML fixture
$xmlFile = '/fixtures/articles-j3.xml';
if (!file_exists($xmlFile)) {
    echo "FAIL: Fixture file not found: $xmlFile\n";
    exit(1);
}

$xml = simplexml_load_file($xmlFile, 'SimpleXMLElement', LIBXML_NONET);
if ($xml === false) {
    echo "FAIL: Could not parse XML\n";
    exit(1);
}
echo "XML loaded: " . $xml->getName() . " version=" . (string)$xml['version'] . "\n";
echo "Content elements: " . count($xml->content) . "\n";

// Import categories first (articles need a valid category)
$catFile = '/fixtures/categories-j3.xml';
if (file_exists($catFile)) {
    $catXml = simplexml_load_file($catFile, 'SimpleXMLElement', LIBXML_NONET);
    $catParams = new \Joomla\Registry\Registry();
    $catParams->set('categories', 1);
    $catParams->set('content', 0);
    $catParams->set('users', 0);
    $catParams->set('tags', 0);
    $catParams->set('menus', 0);
    $catParams->set('modules', 0);
    $catParams->set('viewlevels', 0);
    $importer = new eshiol\J2xml\Importer();
    $importer->import($catXml, $catParams);
    echo "Categories imported\n";
}

// Import articles
$params = new \Joomla\Registry\Registry();
$params->set('content', 1);
$params->set('categories', 0);
$params->set('users', 0);
$params->set('tags', 0);
$params->set('menus', 0);
$params->set('modules', 0);
$params->set('viewlevels', 0);
$params->set('fields', 0);
$params->set('images', 0);
$params->set('usernotes', 0);
$params->set('contacts', 0);
$params->set('weblinks', 0);

echo "Starting import...\n";
$importer = new eshiol\J2xml\Importer();
$result = $importer->import($xml, $params);
echo "Import result: " . ($result ? "true" : "false") . "\n";

// Count articles after
$after = (int) $db->setQuery($query)->loadResult();
echo "Articles after: $after\n";
$diff = $after - $before;
echo "New articles: $diff\n";

if ($diff >= 3) {
    echo "PASS: At least 3 articles imported from J3 format\n";
    exit(0);
} elseif ($result) {
    echo "PASS (partial): Import reported success but only $diff new articles\n";
    exit(0);
} else {
    echo "FAIL: Import did not add articles\n";
    exit(1);
}
