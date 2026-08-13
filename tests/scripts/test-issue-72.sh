#!/bin/bash
# =============================================================================
# Test: Issue #72 — Import fails with HTTP 500 on Joomla 5.2+
#
# Tests the actual AJAX upload endpoint that was failing.
# Uses the Joomla web interface (curl) to simulate a real user upload.
# =============================================================================

set -euo pipefail

echo "--- Test: Issue #72 (Import HTTP 500 on J5.2+) ---"

JOOMLA_URL="${JOOMLA5_URL:-http://localhost:8085}"
COOKIE_FILE="/tmp/j2xml-cookies.txt"
rm -f "$COOKIE_FILE"

# Step 1: Login to Joomla admin
echo "  Step 1: Logging in to Joomla admin..."
# Get the login page and extract the CSRF token
LOGIN_PAGE=$(curl -s -c "$COOKIE_FILE" "$JOOMLA_URL/administrator/index.php" 2>/dev/null)
# Joomla uses a token field like <input type="hidden" name="HASH" value="1">
# Extract it with sed (works on macOS and Linux)
TOKEN=$(echo "$LOGIN_PAGE" | sed -n 's/.*name="\([a-f0-9]\{32\}\)" value="1".*/\1/p' | head -1)
echo "  Token: ${TOKEN:-not found}"

# Login
LOGIN_HTTP=$(curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" -L -o /dev/null -w "%{http_code}" \
    -X POST "$JOOMLA_URL/administrator/index.php" \
    -d "username=admin&passwd=AdminAdmin123!&option=com_login&task=login&${TOKEN:-token}=1" \
    2>/dev/null)
echo "  Login HTTP code: $LOGIN_HTTP"

# Step 2: Access the J2XML import page
echo "  Step 2: Accessing J2XML import page..."
IMPORT_HTTP=$(curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" -o /dev/null -w "%{http_code}" \
    "$JOOMLA_URL/administrator/index.php?option=com_j2xml&view=import" 2>/dev/null)
echo "  Import page HTTP code: $IMPORT_HTTP"

if [ "$IMPORT_HTTP" != "200" ]; then
    echo "  RESULT: FAIL — Cannot access J2XML import page (HTTP $IMPORT_HTTP)"
    exit 1
fi

# Step 3: Upload the XML file via the AJAX upload endpoint
echo "  Step 3: Uploading XML file via AJAX..."
HTTP_CODE=$(curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" -o /tmp/upload-response.txt -w "%{http_code}" \
    -X POST "$JOOMLA_URL/administrator/index.php?option=com_j2xml&task=import.ajax_upload" \
    -F "jform[import_content]=1" \
    -F "jform[import_categories]=1" \
    -F "jform[import_users]=0" \
    -F "jform[import_tags]=0" \
    -F "jform[import_menus]=0" \
    -F "jform[import_modules]=0" \
    -F "jform[import_viewlevels]=0" \
    -F "file=@/Users/svend/workspace/j2xml/tests/fixtures/articles-j3.xml" \
    2>/dev/null)
echo "  Upload HTTP code: $HTTP_CODE"
echo "  Upload response: $(cat /tmp/upload-response.txt | head -c 300)"

if [ "$HTTP_CODE" = "200" ]; then
    echo "  RESULT: PASS — Upload did not return HTTP 500"
    exit 0
elif [ "$HTTP_CODE" = "500" ]; then
    echo "  RESULT: FAIL — HTTP 500 (the original bug)"
    exit 1
else
    echo "  RESULT: FAIL — Unexpected HTTP code: $HTTP_CODE"
    exit 1
fi
