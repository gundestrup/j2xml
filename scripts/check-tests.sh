#!/usr/bin/env bash
#
# Run the integration test suite against Docker Joomla containers.
# Starts the environment, runs tests, and cleans up.
#
# Usage:
#   ./scripts/check-tests.sh          # MySQL + PostgreSQL
#   ./scripts/check-tests.sh --mysql  # MySQL only (faster)
#
set -euo pipefail

red='\033[0;31m'
green='\033[0;32m'
yellow='\033[0;33m'
nc='\033[0m'
pass=0; fail=0

ok()   { printf "${green}PASS${nc}  %s\n" "$*"; ((pass++)); }
fail() { printf "${red}FAIL${nc}  %s\n" "$*"; ((fail++)); }
info() { printf "${yellow}  →${nc}  %s\n" "$*"; }

MYSQL_ONLY=0
[[ "${1:-}" = "--mysql" ]] && MYSQL_ONLY=1

cd "$(dirname "$0")/.." || exit 1

# -------------------------------------------------------------------
# 1. MySQL integration tests (Joomla 5 + 6)
# -------------------------------------------------------------------
echo "=== MySQL integration tests (Joomla 5 + 6) ==="
info "Starting Docker containers…"
docker compose -f tests/docker/docker-compose.yml up -d mysql joomla5 joomla6

info "Running test suite (82 assertions)…"
if bash tests/scripts/run-all-tests.sh; then
    ok "MySQL integration"
else
    fail "MySQL integration"
fi

info "Stopping containers…"
docker compose -f tests/docker/docker-compose.yml down -v

# -------------------------------------------------------------------
# 2. PostgreSQL smoke tests (Joomla 5 + 6)
# -------------------------------------------------------------------
if [[ $MYSQL_ONLY -eq 0 ]]; then
    echo "=== PostgreSQL smoke tests (Joomla 5 + 6) ==="
    info "Starting Docker containers…"
    docker compose -f tests/docker/docker-compose.postgresql.yml up -d

    info "Running smoke tests…"
    if bash tests/scripts/run-postgresql-smoke.sh; then
        ok "PostgreSQL smoke"
    else
        fail "PostgreSQL smoke"
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
