#!/bin/bash
# =============================================================================
# Update-server end-to-end test with a synthetic "next release".
#
# Simulates a release + Joomla update flow without touching GitHub:
#   1. Read the installed pkg_j2xml version (package "A" — whatever the test
#      environment already installed, e.g. suite Phase 2's build).
#   2. Build package "B" at A+patch, generate update.xml for it, and serve
#      both from the Joomla container's own docroot
#      (http://localhost/tmp/update-test/), after repointing the registered
#      update site at the local URL.
#   3. Authenticate to the Joomla administrator and run the real Extension
#      Updates find/update controller against that local stream.
#   4. Assert same/lower/platform/PHP-incompatible updates are not offered,
#      bad hashes are rejected, and the valid package installs with all child
#      extensions intact.
#
# Why B = installed+patch rather than a fixed version: Joomla does not
# support downgrade installs, so pinning A and forcing a reinstall is
# fragile — deriving B from the installed version makes the test
# re-runnable on the same environment.
#
# Usage: test-update-server.sh <5|6>
# Requires a running Joomla test container WITH J2XML already installed
# (scripts/check-tests.sh / run-all-tests.sh Phase 2 provides this).
# =============================================================================

set -euo pipefail

JV="${1:?Usage: test-update-server.sh <5|6>}"

if [[ "$JV" = "5" ]]; then
    CONTAINER="${J2XML_CONTAINER:-j2xml-joomla5}"
    JOOMLA_URL="${J2XML_URL:-http://localhost:8085}"
elif [[ "$JV" = "6" ]]; then
    CONTAINER="${J2XML_CONTAINER:-j2xml-joomla6}"
    JOOMLA_URL="${J2XML_URL:-http://localhost:8086}"
else
    echo "FAIL: invalid version: $JV"
    exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
LOCAL_BASE="http://localhost/tmp/update-test"
DOCROOT_DIR="/var/www/html/tmp/update-test"

TMPD="$(mktemp -d)"
ORIG_VERSION="$(tr -d '[:space:]' <"$ROOT/VERSION")"
SITE_ID=""
ORIGINAL_LOCATION=""
ORIGINAL_ENABLED=""
ORIGINAL_CHECKED=""

restore_version() { printf '%s\n' "$ORIG_VERSION" >"$ROOT/VERSION"; }

cleanup() {
    restore_version
    if [[ -n "$SITE_ID" && -n "$ORIGINAL_LOCATION" ]]; then
        echo "UPDATE #__update_sites SET location='${ORIGINAL_LOCATION}', enabled=${ORIGINAL_ENABLED}, last_check_timestamp=${ORIGINAL_CHECKED} WHERE update_site_id=${SITE_ID}" \
            | docker exec -i "$CONTAINER" php /tmp/j2xml-db-query.php exec >/dev/null 2>&1 || true
        if [[ -f "$TMPD/update-sites.json" ]]; then
            while IFS=$'\t' read -r id enabled; do
                echo "UPDATE #__update_sites SET enabled=${enabled} WHERE update_site_id=${id}" \
                    | docker exec -i "$CONTAINER" php /tmp/j2xml-db-query.php exec >/dev/null 2>&1 || true
            done < <(python3 -c 'import json,sys; [print(str(x["update_site_id"]) + "\t" + str(x["enabled"])) for x in json.load(open(sys.argv[1]))]' "$TMPD/update-sites.json")
        fi
    fi
    rm -rf "$TMPD"
}
trap cleanup EXIT

fail() { echo "FAIL: $1" >&2; exit 1; }

COOKIE_FILE="$TMPD/admin-cookies.txt"
ADMIN_RESPONSE=""

admin_login() {
    local page token code
    page="$(curl -fsS -c "$COOKIE_FILE" "$JOOMLA_URL/administrator/index.php")" \
        || fail "could not load the administrator login page"
    token="$(printf '%s' "$page" | sed -n 's/.*name="\([a-f0-9]\{32\}\)" value="1".*/\1/p' | head -1)"
    [[ -n "$token" ]] || fail "could not find administrator login token"
    code="$(curl -sS -c "$COOKIE_FILE" -b "$COOKIE_FILE" -L -o "$TMPD/admin-login.html" -w '%{http_code}' \
        "$JOOMLA_URL/administrator/index.php" \
        -d "username=${J2XML_ADMIN_USER:-admin}&passwd=${J2XML_ADMIN_PASS:-AdminAdmin123!}&option=com_login&task=login&${token}=1")" \
        || fail "administrator login request failed"
    [[ "$code" = "200" ]] || fail "administrator login returned HTTP $code"
    grep -q 'name="passwd"' "$TMPD/admin-login.html" && fail "administrator login did not establish a session"
    return 0
}

admin_post() {
    local task="$1" update_id="${2:-}" page token code
    echo "[update-server] Administrator task: ${task}"
    if [[ "$task" = "update.find" ]]; then
        echo "UPDATE #__update_sites SET last_check_timestamp=0 WHERE update_site_id=${SITE_ID}" \
            | docker exec -i "$CONTAINER" php /tmp/j2xml-db-query.php exec >/dev/null
    fi
    page="$(curl -fsS -b "$COOKIE_FILE" -c "$COOKIE_FILE" \
        "$JOOMLA_URL/administrator/index.php?option=com_installer&view=update")" \
        || fail "could not load Joomla Extension Updates page"
    token="$(printf '%s' "$page" | sed -n 's/.*"csrf.token":[[:space:]]*"\([a-f0-9]\{32\}\)".*/\1/p' | head -1)"
    [[ -n "$token" ]] || fail "could not find Extension Updates CSRF token"
    local -a form=(--data-urlencode "task=${task}" --data-urlencode "${token}=1")
    [[ -z "$update_id" ]] || form+=(--data-urlencode "cid[]=${update_id}")
    ADMIN_RESPONSE="$TMPD/admin-${task//./-}.html"
    code="$(curl -sS -c "$COOKIE_FILE" -b "$COOKIE_FILE" -L -o "$ADMIN_RESPONSE" -w '%{http_code}' \
        -H "X-CSRF-Token: $token" \
        "$JOOMLA_URL/administrator/index.php?option=com_installer&task=${task}" "${form[@]}")" \
        || fail "administrator task ${task} request failed"
    [[ "$code" = "200" ]] || fail "administrator task ${task} returned HTTP $code"
}

pending_update_id() {
    local version="$1"
    echo "SELECT update_id FROM #__updates WHERE extension_id=(SELECT extension_id FROM #__extensions WHERE element='pkg_j2xml' AND type='package') AND update_site_id=${SITE_ID} AND version='${version}' LIMIT 1" \
        | docker exec -i "$CONTAINER" php /tmp/j2xml-db-query.php scalar
}

assert_no_update() {
    local version="$1" description="$2" update_id
    admin_post update.find
    update_id="$(pending_update_id "$version")"
    [[ -z "$update_id" ]] || fail "$description unexpectedly offered update $update_id"
    echo "[update-server] PASS: $description was not offered"
}

write_stream_variant() {
    local output="$1" version="$2" platform="$3" php_min="$4" bad_hash="${5:-false}"
    python3 - "$TMPD/base.xml" "$output" "$version" "$platform" "$php_min" "$bad_hash" "$LOCAL_BASE" <<'PY'
import sys
import xml.etree.ElementTree as ET

source, output, version, platform, php_min, bad_hash, base_url = sys.argv[1:]
tree = ET.parse(source)
update = tree.getroot().find("update")
update.find("version").text = version
update.find("targetplatform").set("version", platform)
update.find("php_minimum").text = php_min
update.find("downloads/downloadurl").text = f"{base_url}/pkg_j2xml.zip"
update.find("infourl").text = base_url
if bad_hash == "true":
    for name, length in (("sha256", 64), ("sha384", 96), ("sha512", 128)):
        update.find(name).text = "0" * length
tree.write(output, encoding="UTF-8", xml_declaration=True)
PY
}

stage_stream() {
    docker cp "$1" "$CONTAINER:$DOCROOT_DIR/update.xml" >/dev/null
    echo "UPDATE #__update_sites SET location='${LOCAL_BASE}/update.xml', enabled=1 WHERE update_site_id=${SITE_ID}" \
        | docker exec -i "$CONTAINER" php /tmp/j2xml-db-query.php exec >/dev/null
}

# --- Helpers and clean staging ---------------------------------------------
docker cp "$SCRIPT_DIR/bootstrap.php" "$CONTAINER:/tmp/j2xml-bootstrap.php"
docker cp "$SCRIPT_DIR/update-test-run.php" "$CONTAINER:/tmp/j2xml-update-test.php"
docker cp "$SCRIPT_DIR/db-query.php" "$CONTAINER:/tmp/j2xml-db-query.php"

INSTALLED="$(docker exec "$CONTAINER" php /tmp/j2xml-update-test.php version)"
[[ -n "$INSTALLED" ]] || fail "pkg_j2xml is not installed — run the suite's install phase first"
HIGHEST_INSTALLED="$(docker exec "$CONTAINER" php /tmp/j2xml-update-test.php versions | sort -V | tail -1)"
NEW_VERSION="$(echo "$HIGHEST_INSTALLED" | awk -F. -v OFS=. '{$3+=1; print}')"

echo "[update-server] Joomla $JV ($CONTAINER): synthetic update $INSTALLED -> $NEW_VERSION (newer than all child extensions)"

# --- Update site must exist (proves the manifest registered it) --------------
SITE_ID=$(echo "SELECT update_site_id FROM #__update_sites WHERE name='J2XML Updates'" \
    | docker exec -i "$CONTAINER" php /tmp/j2xml-db-query.php scalar)
[[ -n "$SITE_ID" && "$SITE_ID" != "0" ]] || fail "no update site registered — manifest <updateservers> did not take effect"

# --- Build package B + update stream -----------------------------------------
printf '%s\n' "$NEW_VERSION" >"$ROOT/VERSION"
echo "[update-server] Building package B ($NEW_VERSION)..."
bash "$ROOT/scripts/build-package.sh" "$TMPD/b" >/dev/null || fail "build B failed"
bash "$ROOT/scripts/build-update-xml.sh" "$TMPD/b/pkg_j2xml.zip" "$TMPD/base.xml" "$NEW_VERSION" >/dev/null \
    || fail "update.xml generation failed"
restore_version

SITE_STATE="$(echo "SELECT update_site_id, enabled FROM #__update_sites" \
    | docker exec -i "$CONTAINER" php /tmp/j2xml-db-query.php json)"
printf '%s' "$SITE_STATE" >"$TMPD/update-sites.json"
SITE_ROW="$(echo "SELECT update_site_id, location, enabled, last_check_timestamp FROM #__update_sites WHERE name='J2XML Updates'" \
    | docker exec -i "$CONTAINER" php /tmp/j2xml-db-query.php json)"
SITE_ID="$(python3 -c 'import json,sys; print(json.load(sys.stdin)[0]["update_site_id"])' <<<"$SITE_ROW")"
ORIGINAL_LOCATION="$(python3 -c 'import json,sys; print(json.load(sys.stdin)[0]["location"])' <<<"$SITE_ROW")"
ORIGINAL_ENABLED="$(python3 -c 'import json,sys; print(json.load(sys.stdin)[0]["enabled"])' <<<"$SITE_ROW")"
ORIGINAL_CHECKED="$(python3 -c 'import json,sys; print(json.load(sys.stdin)[0]["last_check_timestamp"])' <<<"$SITE_ROW")"
[[ -n "$SITE_ID" && "$SITE_ID" != "0" ]] || fail "no update site registered by the package manifest"

# Limit Joomla's global Find Updates action to the J2XML stream; restore every
# update site's original enabled state and J2XML URL in the EXIT trap.
echo "UPDATE #__update_sites SET enabled=0 WHERE update_site_id <> ${SITE_ID}" \
    | docker exec -i "$CONTAINER" php /tmp/j2xml-db-query.php exec >/dev/null

echo "[update-server] Staging synthetic package and update stream..."
docker exec "$CONTAINER" bash -c "rm -rf /var/www/html/tmp/install_* /var/www/html/tmp/pkg_j2xml.zip"
docker exec "$CONTAINER" mkdir -p "$DOCROOT_DIR"
docker cp "$TMPD/b/pkg_j2xml.zip" "$CONTAINER:$DOCROOT_DIR/pkg_j2xml.zip"
write_stream_variant "$TMPD/valid.xml" "$NEW_VERSION" '[56]\..*' 8.1
stage_stream "$TMPD/valid.xml"
echo "[update-server] Logging into Joomla administrator..."
admin_login

assert_no_update "$INSTALLED" "same-version update"
write_stream_variant "$TMPD/lower.xml" 0.0.1 '[56]\..*' 8.1
stage_stream "$TMPD/lower.xml"
assert_no_update 0.0.1 "lower-version update"
write_stream_variant "$TMPD/platform.xml" "$NEW_VERSION" '99\..*' 8.1
stage_stream "$TMPD/platform.xml"
assert_no_update "$NEW_VERSION" "unsupported Joomla platform update"
write_stream_variant "$TMPD/php.xml" "$NEW_VERSION" '[56]\..*' 99
stage_stream "$TMPD/php.xml"
assert_no_update "$NEW_VERSION" "unsupported PHP update"

# The valid stream must be discoverable and installable through Joomla's
# authenticated com_installer controller, not a CLI Installer shortcut.
stage_stream "$TMPD/valid.xml"
admin_post update.find
UPDATE_ID="$(pending_update_id "$NEW_VERSION")"
[[ -n "$UPDATE_ID" ]] || fail "valid synthetic package was not discovered by the administrator updater"
admin_post update.update "$UPDATE_ID"
INSTALLED_AFTER="$(docker exec "$CONTAINER" php /tmp/j2xml-update-test.php version)"
[[ "$INSTALLED_AFTER" = "$NEW_VERSION" ]] || fail "admin update installed '$INSTALLED_AFTER', expected '$NEW_VERSION'"
CHILD_COUNT="$(echo "SELECT COUNT(*) FROM #__extensions WHERE (element='com_j2xml' AND type='component') OR (element='eshiol/J2xml' AND type='library') OR (element='j2xml' AND type='plugin' AND folder IN ('system','webservices'))" \
    | docker exec -i "$CONTAINER" php /tmp/j2xml-db-query.php scalar)"
[[ "$CHILD_COUNT" = "4" ]] || fail "package update left $CHILD_COUNT of 4 child extensions registered"
PENDING_AFTER="$(pending_update_id "$NEW_VERSION")"
[[ -z "$PENDING_AFTER" ]] || fail "Joomla did not consume the successful update record $PENDING_AFTER"
echo "[update-server] PASS: Joomla admin discovered and installed $NEW_VERSION; all package children remain registered"

stage_stream "$TMPD/valid.xml"
assert_no_update "$NEW_VERSION" "already-installed version"

# Use a second synthetic version to verify the real admin updater rejects a
# bad digest without disturbing the successfully installed version.
CHECKSUM_VERSION="$(echo "$NEW_VERSION" | awk -F. -v OFS=. '{$3+=1; print}')"
printf '%s\n' "$CHECKSUM_VERSION" >"$ROOT/VERSION"
echo "[update-server] Building checksum-test package ($CHECKSUM_VERSION)..."
bash "$ROOT/scripts/build-package.sh" "$TMPD/c" >/dev/null || fail "checksum-test package build failed"
bash "$ROOT/scripts/build-update-xml.sh" "$TMPD/c/pkg_j2xml.zip" "$TMPD/base.xml" "$CHECKSUM_VERSION" >/dev/null \
    || fail "checksum-test update.xml generation failed"
restore_version
docker cp "$TMPD/c/pkg_j2xml.zip" "$CONTAINER:$DOCROOT_DIR/pkg_j2xml.zip"
write_stream_variant "$TMPD/bad-hash.xml" "$CHECKSUM_VERSION" '[56]\..*' 8.1 true
stage_stream "$TMPD/bad-hash.xml"
admin_post update.find
UPDATE_ID="$(pending_update_id "$CHECKSUM_VERSION")"
[[ -n "$UPDATE_ID" ]] || fail "administrator Find Updates did not discover checksum-test package"
admin_post update.update "$UPDATE_ID"
if ! grep -Eq 'alert-danger|alert-error' "$ADMIN_RESPONSE"; then
    fail "Joomla did not report the tampered checksum as an update error"
fi
INSTALLED_AFTER_BAD_HASH="$(docker exec "$CONTAINER" php /tmp/j2xml-update-test.php version)"
[[ "$INSTALLED_AFTER_BAD_HASH" = "$NEW_VERSION" ]] || fail "tampered package changed installed version to $INSTALLED_AFTER_BAD_HASH"
echo "[update-server] PASS: tampered checksum rejected; installed version unchanged"
