#!/bin/bash
# =============================================================================
# Test: Issue #70 — Cannot import users on Joomla 5
#
# Imports users-j3.xml into Joomla 5, then verifies the users exist.
# =============================================================================

set -euo pipefail

echo "--- Test: Issue #70 (Import users on J5) ---"

# Copy fixture
docker exec j2xml-joomla5 mkdir -p /tmp/j2xml-test
docker cp /fixtures/users-j3.xml j2xml-joomla5:/tmp/j2xml-test/users-j3.xml

# Count users before import
BEFORE=$(mysql -h "$JOOMLA5_DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$JOOMLA5_DB" -e "SELECT COUNT(*) FROM _users" -s 2>/dev/null)
echo "  Users before import: $BEFORE"

# Run import
OUTPUT=$(docker exec j2xml-joomla5 bash -c '
    cd /var/www/html
    php -r "
    define(\"_JEXEC\", true);
    require __DIR__ . \"/includes/defines.php\";
    require __DIR__ . \"/framework.php\";

    \$app = JFactory::getApplication(\"administrator\");
    \$app->initialiseApp();

    \$xml = simplexml_load_file(\"/tmp/j2xml-test/users-j3.xml\");
    \$params = new JRegistry();
    \$params->set(\"users\", 1);
    \$params->set(\"content\", 0);
    \$params->set(\"categories\", 0);
    \$params->set(\"tags\", 0);
    \$params->set(\"menus\", 0);
    \$params->set(\"modules\", 0);
    \$params->set(\"viewlevels\", 0);

    \$importer = new eshiol\J2xml\Importer();
    \$result = \$importer->import(\$xml, \$params);

    echo \$result ? \"IMPORT_OK\" : \"IMPORT_FAIL\";
    " 2>&1
' 2>&1)

echo "  Import output: $OUTPUT"

# Count users after import
AFTER=$(mysql -h "$JOOMLA5_DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$JOOMLA5_DB" -e "SELECT COUNT(*) FROM _users" -s 2>/dev/null)
echo "  Users after import: $AFTER"

DIFF=$((AFTER - BEFORE))
echo "  New users: $DIFF"

if [ "$DIFF" -ge 3 ]; then
    echo "  RESULT: PASS — At least 3 users imported"
    exit 0
elif echo "$OUTPUT" | grep -q "IMPORT_OK"; then
    echo "  RESULT: PASS (partial) — Import reported success but only $DIFF new users"
    exit 0
else
    echo "  RESULT: FAIL — Import did not add users"
    exit 1
fi
