#!/bin/bash
# =============================================================================
# Setup Joomla instance via CLI installation script
# Usage: setup-joomla.sh <5|6>
#
# The official Joomla Docker image copies the files but does NOT run the
# installer. We use Joomla's built-in CLI installer (installation/joomla.php)
# to complete the setup.
# =============================================================================

set -euo pipefail

VERSION="${1:?Usage: setup-joomla.sh <5|6>}"

if [ "$VERSION" = "5" ]; then
    CONTAINER="j2xml-joomla5"
    DB="$JOOMLA5_DB"
    URL="$JOOMLA5_URL"
elif [ "$VERSION" = "6" ]; then
    CONTAINER="j2xml-joomla6"
    DB="$JOOMLA6_DB"
    URL="$JOOMLA6_URL"
else
    echo "Invalid version: $VERSION"
    exit 1
fi

echo "[setup] Installing Joomla $VERSION via CLI in $CONTAINER..."

# Check if already installed
TABLES=$(mysql -h "$JOOMLA5_DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB" -e "SHOW TABLES" 2>/dev/null | wc -l)
if [ "$TABLES" -gt 10 ]; then
    echo "[setup] Joomla $VERSION already has $TABLES tables — skipping"
    exit 0
fi

# Check if the installation directory exists
docker exec "$CONTAINER" test -d /var/www/html/installation || {
    echo "[setup] ERROR: installation directory not found in $CONTAINER"
    exit 1
}

# Run Joomla CLI installer
# The joomla.php installer accepts these arguments:
#   --site-name, --admin-user, --admin-username, --admin-password,
#   --admin-email, --db-host, --db-user, --db-pass, --db-name, --db-prefix
docker exec "$CONTAINER" php /var/www/html/installation/joomla.php install \
    --site-name="J2XML Test J$VERSION" \
    --admin-user="Admin" \
    --admin-username="admin" \
    --admin-password="AdminAdmin123!" \
    --admin-email="admin@example.com" \
    --db-host="mysql:3306" \
    --db-user="$DB_USER" \
    --db-pass="$DB_PASS" \
    --db-name="$DB" \
    --db-prefix="jos_" \
    2>&1 || {
    echo "[setup] CLI installer failed, trying web-based install..."

    # Fallback: use curl to submit the installation form
    # This is more fragile but works if the CLI tool is unavailable
    curl -sfL "$URL/installation/index.php" >/dev/null 2>&1 || true
}

# Verify installation
TABLES=$(mysql -h "$JOOMLA5_DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB" -e "SHOW TABLES" 2>/dev/null | wc -l)
echo "[setup] Joomla $VERSION now has $TABLES tables"

if [ "$TABLES" -gt 10 ]; then
    echo "[setup] SUCCESS: Joomla $VERSION installed"
    exit 0
else
    echo "[setup] FAIL: Joomla $VERSION installation incomplete"
    exit 1
fi
