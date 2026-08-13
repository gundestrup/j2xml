#!/bin/bash
# =============================================================================
# Install J2XML package into a Joomla container via the Joomla web installer.
#
# Uploads the compiled pkg_j2xml.zip through Joomla's extension manager,
# exactly as a user would via the admin UI (Extensions → Install → Upload).
#
# Usage: install-plugin.sh <5|6> [zip_path]
#
# If zip_path is not given, defaults to ../../build/pkg_j2xml.zip
# =============================================================================

set -euo pipefail

VERSION="${1:?Usage: install-plugin.sh <5|6> [zip_path]}"
ZIP_PATH="${2:-$(cd "$(dirname "$0")/../.." && pwd)/build/pkg_j2xml.zip}"

if [ "$VERSION" = "5" ]; then
    CONTAINER="j2xml-joomla5"
    JOOMLA_URL="http://localhost:8085"
elif [ "$VERSION" = "6" ]; then
    CONTAINER="j2xml-joomla6"
    JOOMLA_URL="http://localhost:8086"
else
    echo "Invalid version: $VERSION"
    exit 1
fi

if [ ! -f "$ZIP_PATH" ]; then
    echo "FAIL: Package zip not found at $ZIP_PATH"
    echo "Run scripts/build-package.sh first."
    exit 1
fi

echo "[install] Installing J2XML into Joomla $VERSION ($CONTAINER)..."
echo "[install] Package: $ZIP_PATH ($(du -h "$ZIP_PATH" | cut -f1))"

COOKIE_FILE="/tmp/j2xml-install-cookies-$VERSION.txt"
rm -f "$COOKIE_FILE"

# Step 1: Log in to Joomla admin
echo "[install] Logging in to Joomla admin..."

LOGIN_PAGE=$(curl -s -c "$COOKIE_FILE" "$JOOMLA_URL/administrator/index.php" 2>/dev/null)

# Extract CSRF token from login form (hidden field name is the token hash)
TOKEN=$(echo "$LOGIN_PAGE" | sed -n 's/.*name="\([a-f0-9]\{32\}\)" value="1".*/\1/p' | head -1)
if [ -z "$TOKEN" ]; then
    echo "FAIL: Could not find CSRF token on login page"
    exit 1
fi

# Submit login form
LOGIN_CODE=$(curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" -L -o /dev/null -w "%{http_code}" \
    -X POST "$JOOMLA_URL/administrator/index.php" \
    -d "username=admin&passwd=AdminAdmin123!&option=com_login&task=login&${TOKEN}=1" \
    2>/dev/null)

if [ "$LOGIN_CODE" != "200" ]; then
    echo "FAIL: Login returned HTTP $LOGIN_CODE"
    exit 1
fi
echo "[install] Logged in (HTTP $LOGIN_CODE)"

# Step 2: Get the installer page and extract CSRF token
echo "[install] Fetching installer page..."
INSTALLER_PAGE=$(curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" \
    "$JOOMLA_URL/administrator/index.php?option=com_installer&view=install" 2>/dev/null)

CSRF=$(echo "$INSTALLER_PAGE" | sed -n 's/.*"csrf.token":"\([a-f0-9]\{32\}\)".*/\1/p' | head -1)
if [ -z "$CSRF" ]; then
    echo "FAIL: Could not find CSRF token on installer page"
    exit 1
fi
echo "[install] CSRF token: $CSRF"

# Step 3: Upload the package zip via Joomla's installer
echo "[install] Uploading package zip..."

# Copy zip into container as a fallback (in case curl can't upload directly)
docker cp "$ZIP_PATH" "$CONTAINER:/tmp/pkg_j2xml.zip"

# Upload via curl (use X-CSRF-Token header for PHP 8.4 compatibility)
INSTALL_CODE=$(curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" -L -o /tmp/j2xml-install-result-$VERSION.html \
    -w "%{http_code}" \
    -H "X-CSRF-Token: $CSRF" \
    -X POST "$JOOMLA_URL/administrator/index.php?option=com_installer&task=install.install" \
    -F "task=install.install" \
    -F "${CSRF}=1" \
    -F "installtype=upload" \
    -F "install_package=@${ZIP_PATH}" \
    2>/dev/null)

echo "[install] Install HTTP code: $INSTALL_CODE"

# Step 4: Check the result
# Joomla redirects to the installer page after install. Look for success/error messages.
RESULT_HTML=$(cat /tmp/j2xml-install-result-$VERSION.html 2>/dev/null || echo "")

# Check for success message
if echo "$RESULT_HTML" | grep -q "Installation of the package was successful\|alert-success"; then
    echo "[install] Installation appears successful (success message found)"
elif echo "$RESULT_HTML" | grep -q "alert-danger\|alert-error"; then
    echo "[install] Installation may have failed (error message found)"
    # Try to extract the error message
    echo "$RESULT_HTML" | grep -o 'alert-danger[^<]*<[^>]*>[^<]*' | head -3
fi

# Step 5: Verify extensions are registered in the database
echo "[install] Verifying installation in database..."

# Get DB credentials from environment or defaults
if [ "$VERSION" = "5" ]; then
    DB="${JOOMLA5_DB:-joomla5}"
else
    DB="${JOOMLA6_DB:-joomla6}"
fi

VERIFY=$(docker exec "$CONTAINER" php -r "
\$mysqli = new mysqli('mysql', 'joomla', 'joomlapass', '${DB}');
if (\$mysqli->connect_errno) { echo 'FAIL: DB connect\n'; exit(1); }

// Find extensions table
\$result = \$mysqli->query(\"SHOW TABLES LIKE '%extensions'\");
\$extTable = null;
while (\$row = \$result->fetch_row()) {
    if (strpos(\$row[0], 'action_logs') === false && strpos(\$row[0], 'update_sites') === false) {
        \$extTable = \$row[0]; break;
    }
}
if (!\$extTable) { echo 'FAIL: no extensions table\n'; exit(1); }

// Check for J2XML extensions (libraries use eshiol/J2xml and eshiol/phpxmlrpc as element)
\$result = \$mysqli->query(\"SELECT type, element, name, enabled FROM \`\$extTable\` WHERE element IN ('com_j2xml','eshiol/J2xml','eshiol/phpxmlrpc','pkg_j2xml','basicauth','j2xml') OR name LIKE '%J2XML%' OR name LIKE '%eshiol%' OR name LIKE '%XML-RPC%php%' ORDER BY type, element\");
\$count = 0;
\$lines = '';
while (\$row = \$result->fetch_assoc()) {
    \$lines .= '  ' . \$row['type'] . ' / ' . \$row['element'] . ' (enabled=' . \$row['enabled'] . \")\\n\";
    \$count++;
}
echo \$lines;
echo 'COUNT:' . \$count . \"\\n\";
" 2>&1)

echo "$VERIFY"

EXT_COUNT=$(echo "$VERIFY" | tr -d '\r' | grep "^COUNT:" | cut -d: -f2)

if [ "${EXT_COUNT:-0}" -ge 5 ]; then
    echo "SUCCESS: J2XML installed on Joomla $VERSION ($EXT_COUNT extensions found)"
    exit 0
else
    echo "WARNING: Only $EXT_COUNT extensions found (expected 5+)"
    # Check if the install result page has any useful info
    if [ -f /tmp/j2xml-install-result-$VERSION.html ]; then
        echo "[install] Checking install result page for errors..."
        grep -i "error\|fail\|warning" /tmp/j2xml-install-result-$VERSION.html | grep -v "script\|css\|noscript\|JavaScript" | head -5
    fi
    exit 1
fi
