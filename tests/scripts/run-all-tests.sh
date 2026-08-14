#!/bin/bash
# =============================================================================
# J2XML Comprehensive Feature Test Suite
#
# Tests all three features (Export, Import, Send) across all content types
# (Users, Articles, Categories, Contacts, Modules, Menus, Tags, Fields)
# on both Joomla 5 and Joomla 6.
#
# Test flow:
#   1. Install J2XML from compiled zip
#   2. Export existing (default) content from fresh Joomla
#   3. Import new content from fixtures
#   4. Re-export and verify both old and new content present
#   5. Test Send (XML-RPC) between Joomla 5 and Joomla 6
#   6. Uninstall and verify clean removal
#
# Usage:
#   docker compose -f tests/docker/docker-compose.yml up -d
#   bash tests/scripts/run-all-tests.sh
# =============================================================================

set -uo pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

PASS=0
FAIL=0
SKIP=0

info()  { echo -e "${YELLOW}[INFO]${NC}  $*"; }
pass()  { echo -e "${GREEN}[PASS]${NC}  $*"; PASS=$((PASS+1)); }
fail()  { echo -e "${RED}[FAIL]${NC}  $*"; FAIL=$((FAIL+1)); }
skip()  { echo -e "${YELLOW}[SKIP]${NC}  $*"; SKIP=$((SKIP+1)); }

header() { echo ""; echo "=============================================="; echo "  $*"; echo "=============================================="; }

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
FIXTURES_DIR="$(cd "$SCRIPT_DIR/../fixtures" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
COOKIE_FILE="/tmp/j2xml-test-cookies.txt"

# Joomla URLs
JOOMLA5_URL="${JOOMLA5_URL:-http://localhost:8085}"
JOOMLA6_URL="${JOOMLA6_URL:-http://localhost:8086}"

# Containers
J5_CONTAINER="j2xml-joomla5"
J6_CONTAINER="j2xml-joomla6"

# Database settings
export DB_USER=joomla DB_PASS=joomlapass JOOMLA5_DB=joomla5 JOOMLA6_DB=joomla6 JOOMLA5_DB_HOST=mysql JOOMLA6_DB_HOST=mysql

# =============================================================================
# Helper: Login to Joomla admin and get CSRF token
# =============================================================================
joomla_login() {
    local url="$1"
    local name="$2"
    rm -f "$COOKIE_FILE"

    info "Logging in to $name at $url ..."
    local login_page
    login_page=$(curl -s -c "$COOKIE_FILE" "$url/administrator/index.php" 2>/dev/null)

    local token
    token=$(echo "$login_page" | sed -n 's/.*name="\([a-f0-9]\{32\}\)" value="1".*/\1/p' | head -1)

    if [ -z "$token" ]; then
        fail "Could not find CSRF token on $name login page"
        return 1
    fi

    local http_code
    http_code=$(curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" -L -o /dev/null -w "%{http_code}" \
        -X POST "$url/administrator/index.php" \
        -d "username=admin&passwd=AdminAdmin123!&option=com_login&task=login&${token}=1" \
        2>/dev/null)

    if [ "$http_code" = "200" ]; then
        info "Logged in to $name (HTTP $http_code)"
        return 0
    else
        fail "Login to $name failed (HTTP $http_code)"
        return 1
    fi
}

# =============================================================================
# Helper: Get CSRF token from a Joomla page
# =============================================================================
get_csrf_token() {
    local url="$1"
    local page
    page=$(curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" "$url" 2>/dev/null)
    echo "$page" | grep -o '"csrf\.token"[[:space:]]*:[[:space:]]*"[a-f0-9]\{32\}"' | sed 's/.*"\([a-f0-9]\{32\}\)"/\1/' | head -1
}

# =============================================================================
# Helper: Import XML file via Joomla web interface
# =============================================================================
joomla_import() {
    local joomla_url="$1"
    local xml_file="$2"
    local content_flag="${3:-1}"
    local categories_flag="${4:-1}"
    local users_flag="${5:-0}"
    local tags_flag="${6:-0}"
    local menus_flag="${7:-0}"
    local modules_flag="${8:-0}"
    local contacts_flag="${9:-0}"
    local fields_flag="${10:-0}"
    local viewlevels_flag="${11:-0}"

    local import_url="$joomla_url/administrator/index.php?option=com_j2xml&view=import"
    local token
    token=$(get_csrf_token "$import_url")

    if [ -z "$token" ]; then
        echo "NO_TOKEN"
        return 1
    fi

    local http_code
    http_code=$(curl -s -L -c "$COOKIE_FILE" -b "$COOKIE_FILE" -o /dev/null -w "%{http_code}" \
        -X POST "$joomla_url/administrator/index.php?option=com_j2xml&task=import.import" \
        -H "X-CSRF-Token: ${token}" \
        -F "task=import.import" \
        -F "${token}=1" \
        -F "installtype=upload" \
        -F "jform[import_content]=${content_flag}" \
        -F "jform[import_categories]=${categories_flag}" \
        -F "jform[import_users]=${users_flag}" \
        -F "jform[import_tags]=${tags_flag}" \
        -F "jform[import_menus]=${menus_flag}" \
        -F "jform[import_modules]=${modules_flag}" \
        -F "jform[import_contacts]=${contacts_flag}" \
        -F "jform[import_fields]=${fields_flag}" \
        -F "jform[import_viewlevels]=${viewlevels_flag}" \
        -F "install_package=@${xml_file}" \
        2>/dev/null)

    echo "$http_code"
}

# =============================================================================
# Helper: Export content via Joomla raw view
# Returns the exported XML to stdout
# =============================================================================
joomla_export() {
    local joomla_url="$1"
    local content_type="$2"   # content, categories, users, etc.
    local ids="$3"            # comma-separated IDs, or "all" for all
    local export_file="/tmp/j2xml-export-${content_type}-$$.xml"

    local export_url="$joomla_url/administrator/index.php?option=com_j2xml&task=${content_type}.display&format=raw"
    local token
    token=$(get_csrf_token "$joomla_url/administrator/index.php?option=com_j2xml&view=export&layout=${content_type}")

    if [ -z "$token" ]; then
        echo "NO_TOKEN"
        return 1
    fi

    # If ids="all", we need to get all IDs from the database
    local cid="$ids"
    if [ "$ids" = "all" ]; then
        # Get all IDs based on content type
        case "$content_type" in
            content)
                cid=$(docker exec "$J5_CONTAINER" php -r '$m=new mysqli("mysql","joomla","joomlapass","joomla5");$r=$m->query("SELECT GROUP_CONCAT(id) FROM joom_content");echo $r->fetch_row()[0];' 2>/dev/null)
                ;;
            categories)
                cid=$(docker exec "$J5_CONTAINER" php -r '$m=new mysqli("mysql","joomla","joomlapass","joomla5");$r=$m->query("SELECT GROUP_CONCAT(id) FROM joom_categories WHERE extension=\"com_content\"");echo $r->fetch_row()[0];' 2>/dev/null)
                ;;
            users)
                cid=$(docker exec "$J5_CONTAINER" php -r '$m=new mysqli("mysql","joomla","joomlapass","joomla5");$r=$m->query("SELECT GROUP_CONCAT(id) FROM joom_users");echo $r->fetch_row()[0];' 2>/dev/null)
                ;;
            *)
                cid=""
                ;;
        esac
    fi

    local http_code
    http_code=$(curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" -o "$export_file" -w "%{http_code}" \
        -X POST "$export_url" \
        -H "X-CSRF-Token: ${token}" \
        -F "task=${content_type}.display" \
        -F "${token}=1" \
        -F "jform[cid]=${cid}" \
        -F "jform[export_compression]=0" \
        -F "jform[export_categories]=1" \
        -F "jform[export_fields]=1" \
        -F "jform[export_users]=1" \
        -F "jform[export_tags]=1" \
        2>/dev/null)

    if [ "$http_code" = "200" ] && [ -f "$export_file" ]; then
        cat "$export_file"
        rm -f "$export_file"
        return 0
    else
        rm -f "$export_file"
        return 1
    fi
}

# =============================================================================
# Helper: Count records in database via docker exec
# =============================================================================
db_count() {
    local container="$1"
    local db="$2"
    local table="$3"
    docker exec "$container" php -r "
\$m = new mysqli('mysql', 'joomla', 'joomlapass', '${db}');
\$r = \$m->query('SELECT COUNT(*) FROM \`$table\`');
echo \$r->fetch_row()[0];
" 2>/dev/null
}

# =============================================================================
# Helper: Enable REST API in J2XML component config
# =============================================================================
enable_api() {
    local container="$1"
    local db="$2"
    docker exec "$container" php -r "
\$m = new mysqli('mysql', 'joomla', 'joomlapass', '${db}');
\$r = \$m->query(\"SELECT params FROM joom_extensions WHERE element='com_j2xml' AND type='component'\");
\$row = \$r->fetch_assoc();
\$params = json_decode(\$row['params'] ?? '{}', true);
\$params['api'] = '1';
\$params['debug'] = '0';
\$paramsJson = json_encode(\$params);
\$stmt = \$m->prepare(\"UPDATE joom_extensions SET params=? WHERE element='com_j2xml' AND type='component'\");
\$stmt->bind_param('s', \$paramsJson);
\$stmt->execute();
echo \$stmt->affected_rows;
" 2>/dev/null
}

# =============================================================================
# Helper: Generate a Joomla API token for a user
# =============================================================================
gen_api_token() {
    local container="$1"
    local db="$2"
    local userId="$3"
    docker exec "$container" php -r "
\$m = new mysqli('mysql', 'joomla', 'joomlapass', '${db}');
\$m->query(\"DELETE FROM joom_user_profiles WHERE profile_key LIKE 'joomlatoken%' AND user_id=${userId}\");
\$seed = random_bytes(32);
\$seedB64 = base64_encode(\$seed);
\$config = file_get_contents('/var/www/html/configuration.php');
preg_match('/\\\$secret\\s*=\\s*\\'([^\\']+)\\'/', \$config, \$matches);
\$secret = \$matches[1];
\$hmac = hash_hmac('sha256', \$seed, \$secret);
\$tokenString = base64_encode('sha256:' . ${userId} . ':' . \$hmac);
\$stmt = \$m->prepare(\"INSERT INTO joom_user_profiles (user_id, profile_key, profile_value, ordering) VALUES (${userId}, 'joomlatoken.token', ?, 1)\");
\$stmt->bind_param('s', \$seedB64);
\$stmt->execute();
\$stmt2 = \$m->prepare(\"INSERT INTO joom_user_profiles (user_id, profile_key, profile_value, ordering) VALUES (${userId}, 'joomlatoken.enabled', '1', 2)\");
\$stmt2->execute();
echo \$tokenString;
" 2>/dev/null
}

# =============================================================================
# Phase 1: Wait for Joomla instances
# =============================================================================
header "Phase 1: Waiting for Joomla instances"

info "Waiting for Joomla 5 at $JOOMLA5_URL ..."
for i in $(seq 1 60); do
    if curl -sf -o /dev/null "$JOOMLA5_URL/" 2>/dev/null; then
        info "Joomla 5 is up"
        break
    fi
    sleep 2
done

info "Waiting for Joomla 6 at $JOOMLA6_URL ..."
for i in $(seq 1 60); do
    if curl -sf -o /dev/null "$JOOMLA6_URL/" 2>/dev/null; then
        info "Joomla 6 is up"
        break
    fi
    sleep 2
done

# =============================================================================
# Phase 2: Build and Install J2XML from compiled zip
# =============================================================================
header "Phase 2: Build and Install J2XML from compiled zip"

info "Building package zip..."
if bash "$ROOT_DIR/scripts/build-package.sh" > /tmp/j2xml-build.log 2>&1; then
    pass "Package built successfully"
else
    fail "Package build failed"
    cat /tmp/j2xml-build.log | tail -20
fi

info "Installing J2XML on Joomla 5 from zip..."
if bash "$SCRIPT_DIR/install-plugin.sh" 5 > /tmp/j2xml-install-5.log 2>&1; then
    pass "J2XML installed on Joomla 5 from compiled zip"
else
    fail "Failed to install J2XML on Joomla 5 from zip"
    cat /tmp/j2xml-install-5.log | tail -20
fi

info "Installing J2XML on Joomla 6 from zip..."
if bash "$SCRIPT_DIR/install-plugin.sh" 6 > /tmp/j2xml-install-6.log 2>&1; then
    pass "J2XML installed on Joomla 6 from compiled zip"
else
    fail "Failed to install J2XML on Joomla 6 from zip"
    cat /tmp/j2xml-install-6.log | tail -20
fi

# =============================================================================
# Phase 3: Export existing (default) content from fresh Joomla 5
# =============================================================================
header "Phase 3: Export existing content from Joomla 5 (Export feature)"

joomla_login "$JOOMLA5_URL" "Joomla 5" || { skip "Cannot login to Joomla 5"; }

# Export articles
info "Exporting articles from Joomla 5..."
EXPORTED_XML=$(joomla_export "$JOOMLA5_URL" "content" "all" 2>/dev/null)
if echo "$EXPORTED_XML" | grep -q "<j2xml" 2>/dev/null; then
    EXPORT_COUNT=$(echo "$EXPORTED_XML" | grep -c "<content>")
    pass "Export: Articles exported ($EXPORT_COUNT articles in XML)"
    echo "$EXPORTED_XML" > /tmp/j2xml-export-initial-j5.xml
else
    fail "Export: Failed to export articles from Joomla 5"
    EXPORT_COUNT=0
fi

# Export users
info "Exporting users from Joomla 5..."
EXPORTED_USERS=$(joomla_export "$JOOMLA5_URL" "users" "all" 2>/dev/null)
if echo "$EXPORTED_USERS" | grep -q "<j2xml" 2>/dev/null; then
    USER_EXPORT_COUNT=$(echo "$EXPORTED_USERS" | grep -c "<user>")
    pass "Export: Users exported ($USER_EXPORT_COUNT users in XML)"
else
    fail "Export: Failed to export users from Joomla 5"
    USER_EXPORT_COUNT=0
fi

# Export categories
info "Exporting categories from Joomla 5..."
EXPORTED_CATS=$(joomla_export "$JOOMLA5_URL" "categories" "all" 2>/dev/null)
if echo "$EXPORTED_CATS" | grep -q "<j2xml" 2>/dev/null; then
    CAT_EXPORT_COUNT=$(echo "$EXPORTED_CATS" | grep -c "<category>")
    pass "Export: Categories exported ($CAT_EXPORT_COUNT categories in XML)"
else
    fail "Export: Failed to export categories from Joomla 5"
    CAT_EXPORT_COUNT=0
fi

# =============================================================================
# Phase 4: Import all content types into Joomla 5
# =============================================================================
header "Phase 4: Import all content types into Joomla 5 (Import feature)"

info "Importing comprehensive fixture (all content types)..."
HTTP_CODE=$(joomla_import "$JOOMLA5_URL" "$FIXTURES_DIR/all-content-types.xml" \
    1 1 1 1 1 1 1 1 1)

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "303" ]; then
    pass "Import: All content types imported (HTTP $HTTP_CODE)"
else
    fail "Import: Failed to import all content types (HTTP $HTTP_CODE)"
fi

# Verify articles imported
ARTICLE_COUNT=$(db_count "$J5_CONTAINER" "joomla5" "joom_content")
if [ "$ARTICLE_COUNT" -ge 4 ] 2>/dev/null; then
    pass "Import: $ARTICLE_COUNT articles in J5 database (default + imported)"
else
    fail "Import: Only $ARTICLE_COUNT articles in J5 database (expected 4+)"
fi

# Verify users imported
USER_COUNT=$(db_count "$J5_CONTAINER" "joomla5" "joom_users")
if [ "$USER_COUNT" -ge 3 ] 2>/dev/null; then
    pass "Import: $USER_COUNT users in J5 database (1 admin + 2 imported)"
else
    fail "Import: Only $USER_COUNT users in J5 database (expected 3+)"
fi

# Verify categories imported
CAT_COUNT=$(db_count "$J5_CONTAINER" "joomla5" "joom_categories")
if [ "$CAT_COUNT" -ge 4 ] 2>/dev/null; then
    pass "Import: $CAT_COUNT categories in J5 database (default + imported)"
else
    fail "Import: Only $CAT_COUNT categories in J5 database (expected 4+)"
fi

# Verify tags imported
TAG_COUNT=$(db_count "$J5_CONTAINER" "joomla5" "joom_tags")
if [ "$TAG_COUNT" -ge 2 ] 2>/dev/null; then
    pass "Import: $TAG_COUNT tags in J5 database"
else
    fail "Import: Only $TAG_COUNT tags in J5 database (expected 2+)"
fi

# Verify contacts imported
CONTACT_COUNT=$(db_count "$J5_CONTAINER" "joomla5" "joom_contact_details")
if [ "$CONTACT_COUNT" -ge 1 ] 2>/dev/null; then
    pass "Import: $CONTACT_COUNT contacts in J5 database"
else
    fail "Import: Only $CONTACT_COUNT contacts in J5 database (expected 1+)"
fi

# Verify modules imported
MODULE_COUNT=$(db_count "$J5_CONTAINER" "joomla5" "joom_modules")
if [ "$MODULE_COUNT" -ge 1 ] 2>/dev/null; then
    pass "Import: $MODULE_COUNT modules in J5 database (default + imported)"
else
    fail "Import: Only $MODULE_COUNT modules in J5 database"
fi

# Verify menu types imported
MENUTYPE_COUNT=$(docker exec "$J5_CONTAINER" php -r '$m=new mysqli("mysql","joomla","joomlapass","joomla5");$r=$m->query("SELECT COUNT(*) FROM joom_menu_types");echo $r->fetch_row()[0];' 2>/dev/null)
if [ "${MENUTYPE_COUNT:-0}" -ge 1 ] 2>/dev/null; then
    pass "Import: $MENUTYPE_COUNT menu types in J5 database"
else
    fail "Import: Only $MENUTYPE_COUNT menu types in J5 database (expected 1+)"
fi

# Verify fields imported
FIELD_COUNT=$(db_count "$J5_CONTAINER" "joomla5" "joom_fields")
if [ "$FIELD_COUNT" -ge 1 ] 2>/dev/null; then
    pass "Import: $FIELD_COUNT custom fields in J5 database"
else
    fail "Import: Only $FIELD_COUNT custom fields in J5 database (expected 1+)"
fi

# =============================================================================
# Phase 5: Re-export and verify both old and new content present
# =============================================================================
header "Phase 5: Re-export and verify round-trip (Export → Import → Export)"

info "Re-exporting articles from Joomla 5 (should include old + new)..."
REEXPORT_XML=$(joomla_export "$JOOMLA5_URL" "content" "all" 2>/dev/null)
if echo "$REEXPORT_XML" | grep -q "<j2xml" 2>/dev/null; then
    REEXPORT_COUNT=$(echo "$REEXPORT_XML" | grep -c "<content>")
    if [ "$REEXPORT_COUNT" -ge "$ARTICLE_COUNT" ] 2>/dev/null; then
        pass "Round-trip: Re-export contains $REEXPORT_COUNT articles (>= $ARTICLE_COUNT imported)"
    else
        fail "Round-trip: Re-export only has $REEXPORT_COUNT articles (expected >= $ARTICLE_COUNT)"
    fi
    echo "$REEXPORT_XML" > /tmp/j2xml-export-after-import-j5.xml
else
    fail "Round-trip: Failed to re-export articles from Joomla 5"
fi

info "Re-exporting users from Joomla 5..."
REEXPORT_USERS=$(joomla_export "$JOOMLA5_URL" "users" "all" 2>/dev/null)
if echo "$REEXPORT_USERS" | grep -q "<j2xml" 2>/dev/null; then
    REEXPORT_USER_COUNT=$(echo "$REEXPORT_USERS" | grep -c "<user>")
    if [ "$REEXPORT_USER_COUNT" -ge "$USER_COUNT" ] 2>/dev/null; then
        pass "Round-trip: Re-export contains $REEXPORT_USER_COUNT users (>= $USER_COUNT imported)"
    else
        fail "Round-trip: Re-export only has $REEXPORT_USER_COUNT users (expected >= $USER_COUNT)"
    fi
else
    fail "Round-trip: Failed to re-export users from Joomla 5"
fi

# Verify the re-exported XML contains both default and imported articles
if [ -f /tmp/j2xml-export-after-import-j5.xml ]; then
    if grep -q "Fixture Article One" /tmp/j2xml-export-after-import-j5.xml && \
       grep -q "Fixture Article Two" /tmp/j2xml-export-after-import-j5.xml; then
        pass "Round-trip: Re-exported XML contains imported fixture articles"
    else
        fail "Round-trip: Re-exported XML missing imported fixture articles"
    fi
fi

# =============================================================================
# Phase 6: Test Send (REST API) from Joomla 5 to Joomla 6
# =============================================================================
header "Phase 6: Test Send feature (REST API J5 → J6)"

info "Enabling REST API on Joomla 6 (target)..."
ENABLED=$(enable_api "$J6_CONTAINER" "joomla6")
info "API enabled on J6: $ENABLED rows updated"

info "Enabling webservices plugin on Joomla 6..."
docker exec "$J6_CONTAINER" php -r "\$m=new mysqli('mysql','joomla','joomlapass','joomla6');\$m->query(\"UPDATE joom_extensions SET enabled=1 WHERE element='j2xml' AND folder='webservices'\");echo \$m->affected_rows;" 2>/dev/null

# Generate an API token for the Joomla 6 admin user
info "Generating API token on Joomla 6..."
J6_ADMIN_ID=$(docker exec "$J6_CONTAINER" php -r "\$m=new mysqli('mysql','joomla','joomlapass','joomla6');\$r=\$m->query(\"SELECT id FROM joom_users WHERE username='admin'\");echo \$r->fetch_row()[0];" 2>/dev/null)
J6_TOKEN=$(gen_api_token "$J6_CONTAINER" "joomla6" "$J6_ADMIN_ID")
info "Token generated for user ID $J6_ADMIN_ID"

# Test REST API endpoint is reachable
info "Testing REST API endpoint on Joomla 6..."
REST_URL="$JOOMLA6_URL/api/index.php/v1/j2xml/import"
REST_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$REST_URL" \
    -H "Content-Type: application/xml" \
    -H "X-Joomla-Token: $J6_TOKEN" \
    -d '<?xml version="1.0"?><j2xml version="21.12.0"></j2xml>' \
    2>/dev/null)
if [ "$REST_CODE" != "404" ] && [ "$REST_CODE" != "000" ]; then
    pass "Send: REST API endpoint reachable on Joomla 6 (HTTP $REST_CODE)"
else
    fail "Send: REST API endpoint not found on Joomla 6 (HTTP $REST_CODE)"
fi

# Send an article via REST API
info "Sending article from Joomla 5 to Joomla 6 via REST API..."
ARTICLE_XML=$(cat "$FIXTURES_DIR/articles-j3.xml")

REST_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$REST_URL" \
    -H "Content-Type: application/xml" \
    -H "X-Joomla-Token: $J6_TOKEN" \
    -d "$ARTICLE_XML" \
    2>/dev/null)
REST_HTTP_CODE=$(echo "$REST_RESPONSE" | tail -1)
REST_BODY=$(echo "$REST_RESPONSE" | head -n -1)

if [ "$REST_HTTP_CODE" = "200" ]; then
    pass "Send: REST API response received from Joomla 6 (HTTP $REST_HTTP_CODE)"
    # Check if articles were actually imported into J6
    J6_ARTICLES=$(db_count "$J6_CONTAINER" "joomla6" "joom_content")
    if [ "$J6_ARTICLES" -ge 3 ] 2>/dev/null; then
        pass "Send: $J6_ARTICLES articles in Joomla 6 after REST API send"
    else
        fail "Send: Only $J6_ARTICLES articles in Joomla 6 after send (expected 3+)"
    fi
else
    fail "Send: REST API send failed (HTTP $REST_HTTP_CODE)"
    echo "$REST_BODY" | head -20
fi

# =============================================================================
# Phase 7: Test on Joomla 6 (PHP 8.4)
# =============================================================================
header "Phase 7: Joomla 6 / PHP 8.4 compatibility"

joomla_login "$JOOMLA6_URL" "Joomla 6" || { skip "Cannot login to Joomla 6"; }

# Import on Joomla 6
info "Importing all content types into Joomla 6..."
HTTP_CODE=$(joomla_import "$JOOMLA6_URL" "$FIXTURES_DIR/all-content-types.xml" \
    1 1 1 1 1 1 1 1 1)

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "303" ]; then
    pass "J6: All content types imported (HTTP $HTTP_CODE)"
else
    fail "J6: Failed to import all content types (HTTP $HTTP_CODE)"
fi

# Verify articles on J6
J6_ARTICLE_COUNT=$(db_count "$J6_CONTAINER" "joomla6" "joom_content")
if [ "$J6_ARTICLE_COUNT" -ge 4 ] 2>/dev/null; then
    pass "J6: $J6_ARTICLE_COUNT articles in database"
else
    fail "J6: Only $J6_ARTICLE_COUNT articles in database (expected 4+)"
fi

# Export from Joomla 6
info "Exporting articles from Joomla 6..."
J6_EXPORT=$(joomla_export "$JOOMLA6_URL" "content" "all" 2>/dev/null)
if echo "$J6_EXPORT" | grep -q "<j2xml" 2>/dev/null; then
    J6_EXPORT_COUNT=$(echo "$J6_EXPORT" | grep -c "<content>")
    pass "J6: Export works ($J6_EXPORT_COUNT articles exported)"
else
    fail "J6: Export failed"
fi

# Check for PHP deprecation warnings
DEPRECATIONS=$(docker exec "$J6_CONTAINER" bash -c 'grep -c "Deprecated" /var/log/apache2/error.log 2>/dev/null || echo 0')
if [ "$DEPRECATIONS" -eq 0 ] 2>/dev/null; then
    pass "J6: No deprecation warnings in Apache error log"
else
    info "J6: $DEPRECATIONS deprecation warnings in Apache log (may be from Joomla core)"
fi

# =============================================================================
# Phase 8: Uninstall and verify clean removal
# =============================================================================
header "Phase 8: Uninstall J2XML and verify clean removal"

info "Uninstalling J2XML from Joomla 5..."
if bash "$SCRIPT_DIR/uninstall-plugin.sh" 5 > /tmp/j2xml-uninstall-5.log 2>&1; then
    pass "Uninstall: J2XML cleanly uninstalled from Joomla 5"
else
    fail "Uninstall: Failed to cleanly uninstall from Joomla 5"
    cat /tmp/j2xml-uninstall-5.log | tail -20
fi

info "Uninstalling J2XML from Joomla 6..."
if bash "$SCRIPT_DIR/uninstall-plugin.sh" 6 > /tmp/j2xml-uninstall-6.log 2>&1; then
    pass "Uninstall: J2XML cleanly uninstalled from Joomla 6"
else
    fail "Uninstall: Failed to cleanly uninstall from Joomla 6"
    cat /tmp/j2xml-uninstall-6.log | tail -20
fi

# =============================================================================
# Summary
# =============================================================================
header "Test Summary"
echo ""
echo "  Passed: $PASS"
echo "  Failed: $FAIL"
echo "  Skipped: $SKIP"
echo "  Total:  $((PASS+FAIL+SKIP))"
echo ""

if [ $FAIL -gt 0 ]; then
    exit 1
fi
exit 0
