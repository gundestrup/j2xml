#!/usr/bin/env bash
#
# Run the integration test suite against Docker Joomla containers.
# Starts the environment, runs tests, and cleans up.
#
# Usage:
#   ./scripts/check-tests.sh                    # MySQL + PostgreSQL
#   ./scripts/check-tests.sh --mysql            # MySQL only (faster)
#   ./scripts/check-tests.sh --coverage         # also collect line coverage
#   ./scripts/check-tests.sh --mysql --coverage
#
# With --coverage, pcov is installed into the Joomla containers and every
# request is recorded. After the suite finishes, the raw dumps are merged
# into coverage-integration.xml (clover format) at the repo root.
#
set -euo pipefail

red='\033[0;31m'
green='\033[0;32m'
yellow='\033[0;33m'
nc='\033[0m'
pass=0; fail=0

ok()   { printf "${green}PASS${nc}  %s\n" "$*"; pass=$((pass + 1)); }
fail() { printf "${red}FAIL${nc}  %s\n" "$*"; fail=$((fail + 1)); }
info() { printf "${yellow}  →${nc}  %s\n" "$*"; }

MYSQL_ONLY=0
COVERAGE=0
for arg in "$@"; do
    case "$arg" in
        --mysql) MYSQL_ONLY=1 ;;
        --coverage) COVERAGE=1 ;;
        *) echo "Unknown option: $arg"; exit 1 ;;
    esac
done

cd "$(dirname "$0")/.." || exit 1

if [[ $COVERAGE -eq 1 ]]; then
    rm -rf build/coverage-raw
fi

# -------------------------------------------------------------------
# 1. MySQL integration tests (Joomla 5 + 6)
# -------------------------------------------------------------------
echo "=== MySQL integration tests (Joomla 5 + 6) ==="
info "Starting Docker containers…"
docker compose -f tests/docker/docker-compose.yml up -d mysql joomla5 joomla6

if [[ $COVERAGE -eq 1 ]]; then
    info "Enabling coverage collection…"
    bash tests/scripts/coverage-enable.sh \
        j2xml-joomla5 http://localhost:8085 \
        j2xml-joomla6 http://localhost:8086
fi

info "Running test suite…"
if bash tests/scripts/run-all-tests.sh; then
    ok "MySQL integration"
else
    fail "MySQL integration"
fi

if [[ $COVERAGE -eq 1 ]]; then
    info "Collecting coverage…"
    bash tests/scripts/coverage-collect.sh \
        coverage-integration.xml j2xml-joomla5 j2xml-joomla6
fi

info "Stopping containers…"
docker compose -f tests/docker/docker-compose.yml down -v

# -------------------------------------------------------------------
# 2. PostgreSQL full integration tests (Joomla 5 + 6)
# -------------------------------------------------------------------
if [[ $MYSQL_ONLY -eq 0 ]]; then
    echo "=== PostgreSQL full integration tests (Joomla 5 + 6) ==="
    info "Starting Docker containers…"
    docker compose -f tests/docker/docker-compose.postgresql.yml up -d

    if [[ $COVERAGE -eq 1 ]]; then
        info "Enabling coverage collection…"
        bash tests/scripts/coverage-enable.sh \
            j2xml-joomla5-pg http://localhost:8185 \
            j2xml-joomla6-pg http://localhost:8186
    fi

    info "Running full feature suite on PostgreSQL…"
    if DB_DRIVER=pgsql \
        DB_PG_CONTAINER=j2xml-postgres \
        J5_CONTAINER=j2xml-joomla5-pg \
        J6_CONTAINER=j2xml-joomla6-pg \
        JOOMLA5_URL=http://localhost:8185 \
        JOOMLA6_URL=http://localhost:8186 \
        bash tests/scripts/run-all-tests.sh; then
        ok "PostgreSQL full integration"
    else
        fail "PostgreSQL full integration"
    fi

    if [[ $COVERAGE -eq 1 ]]; then
        info "Collecting coverage…"
        bash tests/scripts/coverage-collect.sh \
            coverage-integration.xml j2xml-joomla5-pg j2xml-joomla6-pg
    fi

    info "Stopping containers…"
    docker compose -f tests/docker/docker-compose.postgresql.yml down -v
fi

# -------------------------------------------------------------------
# Summary
# -------------------------------------------------------------------
echo ""
echo "=== Summary ==="
printf "${green}Passed: %d${nc}\n" "$pass"
if [[ $fail -gt 0 ]]; then
    printf "${red}Failed: %d${nc}\n" "$fail"
    exit 1
else
    printf "${green}All tests passed.${nc}\n"
    exit 0
fi
