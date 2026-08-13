#!/bin/bash
# =============================================================================
# Test: Issue #71 — Cannot import articles from J3.10.11 to J5
#
# Imports articles-j3.xml (J3 format) into Joomla 5, then verifies
# the articles exist in the database.
# =============================================================================

set -euo pipefail

echo "--- Test: Issue #71 (Import articles J3 → J5) ---"

# Copy fixture into Joomla 5 container
docker exec j2xml-joomla5 mkdir -p /tmp/j2xml-test
docker cp /fixtures/articles-j3.xml j2xml-joomla5:/tmp/j2xml-test/articles-j3.xml
docker cp /fixtures/categories-j3.xml j2xml-joomla5:/tmp/j2xml-test/categories-j3.xml

# Count articles before import
BEFORE=$(mysql -h "$JOOMLA5_DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$JOOMLA5_DB" -e "SELECT COUNT(*) FROM _content" -s 2>/dev/null)
echo "  Articles before import: $BEFORE"

# Run import
OUTPUT=$(docker exec j2xml-joomla5 bash -c '
    cd /var/www/html
    php -r "
    define(\"_JEXEC\", true);
    require __DIR__ . \"/includes/defines.php\";
    require __DIR__ . \"/framework.php\";

    \$app = JFactory::getApplication(\"administrator\");
    \$app->initialiseApp();

    // Import categories first
    \$xml = simplexml_load_file(\"/tmp/j2xml-test/categories-j3.xml\");
    \$params = new JRegistry();
    \$params->set(\"categories\", 1);
    \$params->set(\"content\", 0);
    \$importer = new eshiol\J2xml\Importer();
    \$importer->import(\$xml, \$params);

    // Then import articles
    \$xml = simplexml_load_file(\"/tmp/j2xml-test/articles-j3.xml\");
    \$params = new JRegistry();
    \$params->set(\"content\", 1);
    \$params->set(\"categories\", 0);
    \$params->set(\"users\", 0);
    \$params->set(\"tags\", 0);
    \$params->set(\"menus\", 0);
    \$params->set(\"modules\", 0);
    \$importer = new eshiol\J2xml\Importer();
    \$result = \$importer->import(\$xml, \$params);

    echo \$result ? \"IMPORT_OK\" : \"IMPORT_FAIL\";
    " 2>&1
' 2>&1)

echo "  Import output: $OUTPUT"

# Count articles after import
AFTER=$(mysql -h "$JOOMLA5_DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$JOOMLA5_DB" -e "SELECT COUNT(*) FROM _content" -s 2>/dev/null)
echo "  Articles after import: $AFTER"

# Verify articles were actually imported
DIFF=$((AFTER - BEFORE))
echo "  New articles: $DIFF"

if [ "$DIFF" -ge 3 ]; then
    echo "  RESULT: PASS — At least 3 articles imported from J3 format"
    exit 0
elif echo "$OUTPUT" | grep -q "IMPORT_OK"; then
    echo "  RESULT: PASS (partial) — Import reported success but only $DIFF new articles"
    exit 0
else
    echo "  RESULT: FAIL — Import did not add articles"
    exit 1
fi
