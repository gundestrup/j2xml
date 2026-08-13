#!/bin/bash
# =============================================================================
# Test: PHP 8.4 deprecation check
#
# Runs the import on Joomla 6 (PHP 8.4) and checks for deprecation warnings
# in the output. The fixes we applied (E_STRICT, utf8_encode, casts, case;)
# should produce zero deprecation notices.
# =============================================================================

set -euo pipefail

echo "--- Test: PHP 8.4 deprecation check (Joomla 6) ---"

# Copy fixtures into Joomla 6 container
docker exec j2xml-joomla6 mkdir -p /tmp/j2xml-test
docker cp /fixtures/articles-j3.xml j2xml-joomla6:/tmp/j2xml-test/articles-j3.xml
docker cp /fixtures/categories-j3.xml j2xml-joomla6:/tmp/j2xml-test/categories-j3.xml
docker cp /fixtures/users-j3.xml j2xml-joomla6:/tmp/j2xml-test/users-j3.xml

# Run import with error reporting maxed out
OUTPUT=$(docker exec j2xml-joomla6 bash -c '
    cd /var/www/html
    php -d error_reporting=E_ALL -d display_errors=1 -r "
    define(\"_JEXEC\", true);
    require __DIR__ . \"/includes/defines.php\";
    require __DIR__ . \"/framework.php\";

    \$app = JFactory::getApplication(\"administrator\");
    \$app->initialiseApp();

    \$xml = simplexml_load_file(\"/tmp/j2xml-test/articles-j3.xml\");
    \$params = new JRegistry();
    \$params->set(\"content\", 1);
    \$params->set(\"categories\", 1);
    \$params->set(\"users\", 1);
    \$params->set(\"tags\", 0);
    \$params->set(\"menus\", 0);
    \$params->set(\"modules\", 0);
    \$params->set(\"viewlevels\", 0);

    \$importer = new eshiol\J2xml\Importer();
    \$importer->import(\$xml, \$params);
    echo \"DONE\";
    " 2>&1
' 2>&1)

echo "  Output (last 20 lines):"
echo "$OUTPUT" | tail -20

# Check for deprecation warnings
DEPRECATIONS=$(echo "$OUTPUT" | grep -ci "deprecated\|deprecation" || true)
echo "  Deprecation warnings: $DEPRECATIONS"

# Check for fatal errors
FATALS=$(echo "$OUTPUT" | grep -ci "fatal error\|parse error" || true)
echo "  Fatal errors: $FATALS"

if [ "$DEPRECATIONS" -eq 0 ] && [ "$FATALS" -eq 0 ]; then
    echo "  RESULT: PASS — No deprecations or fatal errors on PHP 8.4"
    exit 0
else
    echo "  RESULT: FAIL — Found $DEPRECATIONS deprecations, $FATALS fatal errors"
    exit 1
fi
