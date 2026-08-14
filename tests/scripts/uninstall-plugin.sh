#!/bin/bash
# =============================================================================
# Uninstall J2XML package from a Joomla container via the Joomla web installer.
#
# Navigates to Extensions → Manage, finds the J2XML package, and uninstalls it.
# Then verifies that all J2XML extensions and files are removed.
#
# Usage: uninstall-plugin.sh <5|6>
# =============================================================================

set -euo pipefail

VERSION="${1:?Usage: uninstall-plugin.sh <5|6>}"

if [ "$VERSION" = "5" ]; then
    CONTAINER="j2xml-joomla5"
    JOOMLA_URL="http://localhost:8085"
    DB="${JOOMLA5_DB:-joomla5}"
elif [ "$VERSION" = "6" ]; then
    CONTAINER="j2xml-joomla6"
    JOOMLA_URL="http://localhost:8086"
    DB="${JOOMLA6_DB:-joomla6}"
else
    echo "Invalid version: $VERSION"
    exit 1
fi

echo "[uninstall] Uninstalling J2XML from Joomla $VERSION ($CONTAINER)..."

COOKIE_FILE="/tmp/j2xml-uninstall-cookies-$VERSION.txt"
rm -f "$COOKIE_FILE"

# Step 1: Log in to Joomla admin
echo "[uninstall] Logging in to Joomla admin..."

LOGIN_PAGE=$(curl -s -c "$COOKIE_FILE" "$JOOMLA_URL/administrator/index.php" 2>/dev/null)

TOKEN=$(echo "$LOGIN_PAGE" | sed -n 's/.*name="\([a-f0-9]\{32\}\)" value="1".*/\1/p' | head -1)
if [ -z "$TOKEN" ]; then
    echo "FAIL: Could not find CSRF token on login page"
    exit 1
fi

LOGIN_CODE=$(curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" -L -o /dev/null -w "%{http_code}" \
    -X POST "$JOOMLA_URL/administrator/index.php" \
    -d "username=admin&passwd=AdminAdmin123!&option=com_login&task=login&${TOKEN}=1" \
    2>/dev/null)

if [ "$LOGIN_CODE" != "200" ]; then
    echo "FAIL: Login returned HTTP $LOGIN_CODE"
    exit 1
fi
echo "[uninstall] Logged in (HTTP $LOGIN_CODE)"

# Step 2: Find the package extension ID from the database
echo "[uninstall] Finding J2XML package extension ID..."

PKG_ID=$(docker exec "$CONTAINER" php -r '
$mysqli = new mysqli("mysql", "joomla", "joomlapass", "'"$DB"'");
if ($mysqli->connect_errno) { echo "FAIL\n"; exit(1); }
$result = $mysqli->query("SELECT extension_id FROM joom_extensions WHERE element=\"pkg_j2xml\" AND type=\"package\"");
$row = $result->fetch_row();
echo $row[0] ?? "NOTFOUND";
' 2>&1)

if [ "$PKG_ID" = "NOTFOUND" ] || [ -z "$PKG_ID" ]; then
    echo "[uninstall] J2XML package not found in database - already uninstalled"
    echo "SUCCESS: J2XML not installed on Joomla $VERSION"
    exit 0
fi

echo "[uninstall] Package extension ID: $PKG_ID"

# Step 3: Get the manage page and extract CSRF token
echo "[uninstall] Fetching extension manager page..."
MANAGE_PAGE=$(curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" \
    "$JOOMLA_URL/administrator/index.php?option=com_installer&view=manage" 2>/dev/null)

CSRF=$(echo "$MANAGE_PAGE" | sed -n 's/.*"csrf.token":[[:space:]]*"\([a-f0-9]\{32\}\)".*/\1/p' | head -1)
if [ -z "$CSRF" ]; then
    # Try to get it from the form
    CSRF=$(echo "$MANAGE_PAGE" | sed -n 's/.*name="\(token\)" value="\([a-f0-9]\{32\}\)".*/\2/p' | head -1)
fi
if [ -z "$CSRF" ]; then
    echo "FAIL: Could not find CSRF token on manage page"
    exit 1
fi
echo "[uninstall] CSRF token: $CSRF"

# Step 4: Uninstall the package via Joomla's installer
# We uninstall the package which should cascade to sub-extensions.
# If that doesn't work, we also uninstall each sub-extension individually.
echo "[uninstall] Uninstalling package (ID: $PKG_ID)..."

UNINSTALL_CODE=$(curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" -L \
    -o /tmp/j2xml-uninstall-result-$VERSION.html \
    -w "%{http_code}" \
    -H "X-CSRF-Token: $CSRF" \
    -X POST "$JOOMLA_URL/administrator/index.php?option=com_installer&task=manage.remove" \
    -F "task=manage.remove" \
    -F "cid[]=${PKG_ID}" \
    -F "${CSRF}=1" \
    2>/dev/null)

echo "[uninstall] Uninstall HTTP code: $UNINSTALL_CODE"

# Wait a moment for uninstall to complete
sleep 2

# Check if any J2XML extensions remain
REMAINING_CHECK=$(docker exec "$CONTAINER" php -r '
$mysqli = new mysqli("mysql", "joomla", "joomlapass", "'"$DB"'");
$result = $mysqli->query("SELECT extension_id, type, element FROM joom_extensions WHERE element IN (\"com_j2xml\",\"eshiol/J2xml\",\"pkg_j2xml\",\"j2xml\") OR name LIKE \"%J2XML%\" OR name LIKE \"%eshiol%\"");
$ids = [];
while ($row = $result->fetch_assoc()) {
    echo $row["extension_id"] . ":" . $row["type"] . ":" . $row["element"] . "\n";
    $ids[] = $row["extension_id"];
}
echo "IDS:" . implode(",", $ids) . "\n";
' 2>&1)

echo "[uninstall] Remaining after package uninstall:"
echo "$REMAINING_CHECK"

REMAINING_IDS=$(echo "$REMAINING_CHECK" | tr -d '\r' | grep "^IDS:" | cut -d: -f2)

# If extensions remain, uninstall them individually
if [ -n "$REMAINING_IDS" ] && [ "$REMAINING_IDS" != "" ]; then
    echo "[uninstall] Uninstalling remaining extensions individually..."
    IFS=',' read -ra ID_ARRAY <<< "$REMAINING_IDS"
    for ID in "${ID_ARRAY[@]}"; do
        echo "[uninstall] Removing extension ID: $ID"
        curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" -L -o /dev/null -w "%{http_code}" \
            -H "X-CSRF-Token: $CSRF" \
            -X POST "$JOOMLA_URL/administrator/index.php?option=com_installer&task=manage.remove" \
            -F "task=manage.remove" \
            -F "cid[]=${ID}" \
            -F "${CSRF}=1" \
            2>/dev/null
        echo ""
        sleep 1
    done
fi

# Step 5: Verify all J2XML extensions are removed from the database
echo "[uninstall] Verifying removal from database..."

REMAINING_COUNT=$(docker exec "$CONTAINER" php -r '
$mysqli = new mysqli("mysql", "joomla", "joomlapass", "'"$DB"'");
if ($mysqli->connect_errno) { echo "0"; exit(0); }
$result = $mysqli->query("SELECT COUNT(*) as cnt FROM joom_extensions WHERE element IN (\"com_j2xml\",\"eshiol/J2xml\",\"pkg_j2xml\",\"j2xml\") OR name LIKE \"%J2XML%\" OR name LIKE \"%eshiol%\"");
$row = $result->fetch_assoc();
echo $row["cnt"];
' 2>/dev/null || echo "0")

echo "[uninstall] Extensions remaining in DB: $REMAINING_COUNT"

# Show any remaining extensions for debugging
if [ "${REMAINING_COUNT:-0}" -gt 0 ]; then
    docker exec "$CONTAINER" php -r '
$mysqli = new mysqli("mysql", "joomla", "joomlapass", "'"$DB"'");
$result = $mysqli->query("SELECT type, element, name FROM joom_extensions WHERE element IN (\"com_j2xml\",\"eshiol/J2xml\",\"pkg_j2xml\",\"j2xml\") OR name LIKE \"%J2XML%\" OR name LIKE \"%eshiol%\"");
while ($row = $result->fetch_assoc()) {
    echo "  REMAINS: " . $row["type"] . " / " . $row["element"] . " / " . $row["name"] . PHP_EOL;
}
' 2>/dev/null || true
fi

# Step 6: Verify files are removed from the filesystem
echo "[uninstall] Verifying files removed from filesystem..."

FILES_REMAINING=0
for path in \
    "/var/www/html/administrator/components/com_j2xml" \
    "/var/www/html/components/com_j2xml" \
    "/var/www/html/libraries/eshiol/J2xml" \
    "/var/www/html/plugins/system/j2xml" \
    "/var/www/html/plugins/webservices/j2xml"; do
    if docker exec "$CONTAINER" test -e "$path" 2>/dev/null; then
        echo "  FILE_REMAINS: $path"
        FILES_REMAINING=$((FILES_REMAINING + 1))
    fi
done

echo "[uninstall] Files remaining: $FILES_REMAINING"

# Step 7: Report results
if [ "${REMAINING_COUNT:-0}" -eq 0 ] && [ "$FILES_REMAINING" -eq 0 ]; then
    echo "SUCCESS: J2XML cleanly uninstalled from Joomla $VERSION"
    exit 0
else
    echo "WARNING: $REMAINING_COUNT extensions and $FILES_REMAINING files still remain"
    if [ "${REMAINING_COUNT:-0}" -gt 0 ]; then
        echo "  Database entries remaining: $REMAINING_COUNT"
    fi
    if [ "$FILES_REMAINING" -gt 0 ]; then
        echo "  Files/directories remaining: $FILES_REMAINING"
    fi
    exit 1
fi
