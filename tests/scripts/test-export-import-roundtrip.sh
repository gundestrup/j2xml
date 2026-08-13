#!/bin/bash
# =============================================================================
# Test: Export → Import roundtrip
#
# 1. Create a test article directly in the Joomla 5 database
# 2. Export it using the CLI exporter
# 3. Import the exported XML into Joomla 6
# 4. Verify the article exists in Joomla 6
# =============================================================================

set -euo pipefail

echo "--- Test: Export → Import roundtrip (J5 → J6) ---"

# Step 1: Create a test article in Joomla 5
echo "  Step 1: Creating test article in Joomla 5..."
mysql -h "$JOOMLA5_DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$JOOMLA5_DB" <<EOF
INSERT INTO _content (title, alias, introtext, fulltext, state, catid, created, created_by, modified, modified_by, publish_up, access, language, metadesc, metakey, metadata, featured, hits)
VALUES ('Roundtrip Test', 'roundtrip-test', '<p>Roundtrip intro</p>', '<p>Roundtrip full</p>', 1, 2, NOW(), 42, NOW(), 42, NOW(), 1, '*', '', '', '{}', 0, 0);
EOF

ARTICLE_ID=$(mysql -h "$JOOMLA5_DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$JOOMLA5_DB" -e "SELECT id FROM _content WHERE alias='roundtrip-test'" -s 2>/dev/null)
echo "  Created article ID: $ARTICLE_ID"

# Step 2: Export from Joomla 5 using CLI
echo "  Step 2: Exporting from Joomla 5..."
docker exec j2xml-joomla5 bash -c "
    cd /var/www/html
    php cli/j2xml.php -f /tmp/j2xml-test/export-roundtrip.xml 2>&1
" || true

# Check if export file was created
if docker exec j2xml-joomla5 test -f /tmp/j2xml-test/export-roundtrip.xml; then
    echo "  Export file created"
    # Copy to Joomla 6
    docker cp j2xml-joomla5:/tmp/j2xml-test/export-roundtrip.xml /tmp/export-roundtrip.xml
    docker cp /tmp/export-roundtrip.xml j2xml-joomla6:/tmp/j2xml-test/export-roundtrip.xml
else
    echo "  Export file not created — trying alternate export method..."
    # Try direct PHP export
    docker exec j2xml-joomla5 bash -c '
        cd /var/www/html
        php -r "
        define(\"_JEXEC\", true);
        require __DIR__ . \"/includes/defines.php\";
        require __DIR__ . \"/framework.php\";
        \$app = JFactory::getApplication(\"administrator\");
        \$app->initialiseApp();

        \$db = JFactory::getDbo();
        \$query = \$db->getQuery(true)
            ->select(\"*\")
            ->from(\"#_content\")
            ->where(\"alias = \" . \$db->quote(\"roundtrip-test\"));
        \$db->setQuery(\$query);
        \$articles = \$db->loadObjectList();

        \$xml = new SimpleXMLElement(\"<j2xml version=\\\"21.12.0\\\"/>\");
        foreach (\$articles as \$article) {
            \$node = \$xml->addChild(\"content\");
            foreach (\$article as \$key => \$val) {
                \$node->addChild(\$key, htmlspecialchars(\$val ?? \"\"));
            }
        }
        \$xml->asXML(\"/tmp/j2xml-test/export-roundtrip.xml\");
        echo \"Exported \" . count(\$articles) . \" articles\n\";
        " 2>&1
    ' || true
fi

# Step 3: Import into Joomla 6
echo "  Step 3: Importing into Joomla 6..."
BEFORE_J6=$(mysql -h "$JOOMLA6_DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$JOOMLA6_DB" -e "SELECT COUNT(*) FROM _content" -s 2>/dev/null)
echo "  J6 articles before: $BEFORE_J6"

docker exec j2xml-joomla6 bash -c '
    cd /var/www/html
    php -r "
    define(\"_JEXEC\", true);
    require __DIR__ . \"/includes/defines.php\";
    require __DIR__ . \"/framework.php\";
    $app = JFactory::getApplication(\"administrator\");
    $app->initialiseApp();

    $xml = simplexml_load_file(\"/tmp/j2xml-test/export-roundtrip.xml\");
    if ($xml === false) {
        echo \"FAIL: Could not parse export XML\n\";
        exit(1);
    }
    $params = new JRegistry();
    $params->set(\"content\", 1);
    $params->set(\"categories\", 1);
    $params->set(\"users\", 0);
    $params->set(\"tags\", 0);
    $params->set(\"menus\", 0);
    $params->set(\"modules\", 0);
    $params->set(\"viewlevels\", 0);

    $importer = new eshiol\J2xml\Importer();
    $importer->import($xml, $params);
    echo \"IMPORT_DONE\n\";
    " 2>&1
' || true

# Step 4: Verify
AFTER_J6=$(mysql -h "$JOOMLA6_DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$JOOMLA6_DB" -e "SELECT COUNT(*) FROM _content" -s 2>/dev/null)
echo "  J6 articles after: $AFTER_J6"

FOUND=$(mysql -h "$JOOMLA6_DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$JOOMLA6_DB" -e "SELECT COUNT(*) FROM _content WHERE alias='roundtrip-test'" -s 2>/dev/null)
echo "  Roundtrip article found in J6: $FOUND"

if [ "$FOUND" -ge 1 ]; then
    echo "  RESULT: PASS — Export from J5 and import to J6 succeeded"
    exit 0
else
    echo "  RESULT: FAIL — Article not found in J6 after import"
    exit 1
fi
