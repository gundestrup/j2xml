#!/usr/bin/env bash
# Copyright (C) 2026 Svend Gundestrup. All Rights Reserved.
# =============================================================================
# J2XML browser UI test runner (Playwright)
#
# Prepares the Docker Joomla instances (J2XML install, webservices plugin,
# API tokens) and runs the Playwright suite in tests/ui against both
# Joomla 5 and Joomla 6.
#
# Prerequisites:
#   - Docker containers running (tests/docker/docker-compose.yml)
#   - node + npm on the host (Playwright runs on the host, not in Docker)
#
# Usage:
#   bash tests/scripts/run-ui-tests.sh [playwright args...]
#
# Env overrides:
#   JOOMLA5_URL / JOOMLA6_URL   instance base URLs (default :8085 / :8086)
#   J5_CONTAINER / J6_CONTAINER container names (default j2xml-joomla5/6)
#   J2XML_ADMIN_USER / J2XML_ADMIN_PASS  admin credentials
#   J2XML_UI_SKIP_INSTALL=1     assume J2XML is already installed
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
UI_DIR="$ROOT_DIR/tests/ui"

JOOMLA5_URL="${JOOMLA5_URL:-http://localhost:8085}"
JOOMLA6_URL="${JOOMLA6_URL:-http://localhost:8086}"
J5_CONTAINER="${J5_CONTAINER:-j2xml-joomla5}"
J6_CONTAINER="${J6_CONTAINER:-j2xml-joomla6}"
ADMIN_USER="${J2XML_ADMIN_USER:-admin}"
ADMIN_PASS="${J2XML_ADMIN_PASS:-AdminAdmin123!}"
DB_HELPER=/tmp/j2xml-db-query.php

info() { echo "[ui-tests] $*"; }
die()  { echo "[ui-tests] FAIL: $*" >&2; exit 1; }

# --- 1. Prerequisites ---------------------------------------------------------
command -v node >/dev/null || die "node not found — install Node.js to run the UI tests"
command -v npm  >/dev/null || die "npm not found — install Node.js to run the UI tests"
command -v docker >/dev/null || die "docker not found"
command -v curl  >/dev/null || die "curl not found"

# --- 2. Wait for Joomla -------------------------------------------------------
wait_for_joomla() {
    local url="$1" name="$2"
    for _ in $(seq 1 60); do
        if curl -sf "$url/administrator/index.php" 2>/dev/null | grep -q 'name="[a-f0-9]\{32\}"'; then
            info "$name is up at $url"
            return 0
        fi
        sleep 2
    done
    die "$name did not become ready at $url"
}

wait_for_joomla "$JOOMLA5_URL" "Joomla 5"
wait_for_joomla "$JOOMLA6_URL" "Joomla 6"

configure_ui_cors() {
    local container="$1" origin="$2"
    docker exec -i -e "J2XML_CORS_ORIGIN=$origin" "$container" php <<'PHP'
<?php
$configFile = '/var/www/html/configuration.php';
$config = file_get_contents($configFile);
$parts = parse_url((string) getenv('J2XML_CORS_ORIGIN'));
if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
    fwrite(STDERR, "Invalid test CORS origin.\n");
    exit(1);
}
$origin = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
$settings = [
    'cors' => 'true',
    'cors_allow_origin' => var_export($origin, true),
    'cors_allow_headers' => var_export('Content-Type,X-Joomla-Token', true),
    'cors_allow_methods' => var_export('', true),
];
foreach ($settings as $name => $value) {
    $pattern = '/^[ \\t]*public \\$' . preg_quote($name, '/') . '\\s*=.*;[ \\t]*$/m';
    $line = '    public $' . $name . ' = ' . $value . ';';
    if (preg_match($pattern, $config)) {
        $config = preg_replace($pattern, $line, $config, 1);
        continue;
    }
    $config = preg_replace('/\n}\s*$/', "\n" . $line . "\n}\n", $config, 1, $count);
    if ($count !== 1) {
        fwrite(STDERR, "Could not update Joomla CORS settings.\n");
        exit(1);
    }
}
if (file_put_contents($configFile, $config) === false) {
    fwrite(STDERR, "Could not write Joomla configuration.\n");
    exit(1);
}
echo "CORS configured for {$origin}.\n";
PHP
}

configure_ui_cors "$J5_CONTAINER" "$JOOMLA6_URL"
configure_ui_cors "$J6_CONTAINER" "$JOOMLA5_URL"

# --- 3. Ensure the package under test is installed ---------------------------
if [[ "${J2XML_UI_SKIP_INSTALL:-0}" != "1" ]]; then
    info "Building current J2XML package..."
    bash "$ROOT_DIR/scripts/build-package.sh" "$ROOT_DIR/build"

    for v in 5 6; do
        url_var="JOOMLA${v}_URL"; url="${!url_var}"
        container="$J5_CONTAINER"; [[ "$v" == "6" ]] && container="$J6_CONTAINER"
        info "Installing J2XML on Joomla $v..."
        J2XML_CONTAINER="$container" J2XML_URL="$url" \
            bash "$SCRIPT_DIR/install-plugin.sh" "$v"
    done
fi

# --- 4. Enable webservices plugin + API tokens --------------------------------
for container in "$J5_CONTAINER" "$J6_CONTAINER"; do
    docker cp "$SCRIPT_DIR/db-query.php" "$container:$DB_HELPER" >/dev/null
    echo "UPDATE #__extensions SET enabled=1 WHERE element='j2xml' AND folder='webservices'" \
        | docker exec -i "$container" php "$DB_HELPER" exec >/dev/null
    echo "UPDATE #__extensions SET enabled=0 WHERE type='plugin' AND element='stats' AND folder='system'" \
        | docker exec -i "$container" php "$DB_HELPER" exec >/dev/null
done

gen_token() {
    local container="$1" uid
    uid=$(echo "SELECT id FROM #__users WHERE username='${ADMIN_USER}'" \
        | docker exec -i "$container" php "$DB_HELPER" scalar)
    [[ -n "$uid" ]] || die "admin user not found in $container"
    docker exec "$container" php "$DB_HELPER" token "$uid"
}

J2XML_TOKEN_J5=$(gen_token "$J5_CONTAINER")
J2XML_TOKEN_J6=$(gen_token "$J6_CONTAINER")
info "API tokens generated for both instances"

# --- 5. Playwright dependencies ----------------------------------------------
cd "$UI_DIR"
EXPECTED_NODE_MAJOR="$(tr -d '[:space:]' < "$ROOT_DIR/.nvmrc")"
NODE_MAJOR="$(node -p 'Number(process.versions.node.split(".")[0])')"
if [[ "$NODE_MAJOR" != "$EXPECTED_NODE_MAJOR" ]]; then
    die "UI tests require Node $EXPECTED_NODE_MAJOR (see .nvmrc), matching CI; run 'nvm install && nvm use' from the repository root"
fi
info "Using Node $(node --version), matching CI"
info "Installing Playwright dependencies from the lockfile..."
npm ci --no-audit --no-fund
if ! npx playwright --version >/dev/null 2>&1; then
    die "Playwright is not usable in $UI_DIR"
fi
# Skip the browser download when Playwright will drive an installed Chrome
# (the config auto-detects; override with J2XML_UI_BROWSER=chrome|chromium).
USE_CHROME=0
if [[ "${J2XML_UI_BROWSER:-}" == "chrome" ]]; then
    USE_CHROME=1
elif [[ "${J2XML_UI_BROWSER:-}" != "chromium" && "${CI:-}" != "true" ]]; then
    if [[ -d "/Applications/Google Chrome.app" ]] \
        || command -v google-chrome >/dev/null 2>&1 \
        || command -v google-chrome-stable >/dev/null 2>&1; then
        USE_CHROME=1
    fi
fi

if [[ $USE_CHROME -eq 1 ]]; then
    info "Using installed Google Chrome (no browser download needed)"
else
    info "Ensuring Chromium browser is installed..."
    if [[ "${CI:-}" == "true" ]]; then
        npx playwright install --with-deps chromium
    else
        npx playwright install chromium
    fi
fi

# --- 6. Run the suite ---------------------------------------------------------
info "Running Playwright UI tests (Joomla 5: $JOOMLA5_URL, Joomla 6: $JOOMLA6_URL)"
JOOMLA5_URL="$JOOMLA5_URL" \
JOOMLA6_URL="$JOOMLA6_URL" \
J2XML_TOKEN_J5="$J2XML_TOKEN_J5" \
J2XML_TOKEN_J6="$J2XML_TOKEN_J6" \
J2XML_ADMIN_USER="$ADMIN_USER" \
J2XML_ADMIN_PASS="$ADMIN_PASS" \
    npx playwright test "$@"
