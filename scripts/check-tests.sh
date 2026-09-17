#!/usr/bin/env bash
# Copyright (C) 2026 Svend Gundestrup. All Rights Reserved.
#
# Run the integration test suite against Docker Joomla containers.
# Starts the environment, runs tests, and cleans up.
#
# Usage:
#   ./scripts/check-tests.sh                    # MySQL + PostgreSQL
#   ./scripts/check-tests.sh --mysql            # MySQL only (faster)
#   ./scripts/check-tests.sh --postgresql       # PostgreSQL only (faster)
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
POSTGRESQL_ONLY=0
COVERAGE=0
for arg in "$@"; do
    case "$arg" in
        --mysql) MYSQL_ONLY=1 ;;
        --postgresql) POSTGRESQL_ONLY=1 ;;
        --coverage) COVERAGE=1 ;;
        *) echo "Unknown option: $arg"; exit 1 ;;
    esac
done

if [[ $MYSQL_ONLY -eq 1 && $POSTGRESQL_ONLY -eq 1 ]]; then
    echo "--mysql and --postgresql are mutually exclusive"
    exit 1
fi

cd "$(dirname "$0")/.." || exit 1

# Keep the two Compose files in separate projects. They intentionally reuse
# service names (joomla5/joomla6), and sharing a project lets Compose reuse a
# PostgreSQL container for the MySQL run.
MYSQL_COMPOSE=(docker compose --project-name j2xml-mysql -f tests/docker/docker-compose.yml)
POSTGRES_COMPOSE=(docker compose --project-name j2xml-postgresql -f tests/docker/docker-compose.postgresql.yml)

remove_wrong_project_containers() {
    local expected_project="$1"
    shift
    local container project

    for container in "$@"; do
        if docker ps -a --format '{{.Names}}' | grep -qx "$container"; then
            project=$(docker inspect -f '{{index .Config.Labels "com.docker.compose.project"}}' "$container" 2>/dev/null || true)
            if [[ "$project" != "$expected_project" ]]; then
                info "Removing stale $container container from project '${project:-none}'…"
                docker rm -f "$container" >/dev/null
            fi
        fi
    done
}

if [[ $COVERAGE -eq 1 ]]; then
    rm -rf build/coverage-raw
fi

# -------------------------------------------------------------------
# 1. MySQL integration tests (Joomla 5 + 6)
# -------------------------------------------------------------------
if [[ $POSTGRESQL_ONLY -eq 0 ]]; then
    echo "=== MySQL integration tests (Joomla 5 + 6) ==="
    info "Starting Docker containers…"
    remove_wrong_project_containers "j2xml-mysql" \
        j2xml-mysql j2xml-joomla5 j2xml-joomla6
    "${MYSQL_COMPOSE[@]}" up -d mysql joomla5 joomla6

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
    "${MYSQL_COMPOSE[@]}" down -v
fi

# -------------------------------------------------------------------
# 2. PostgreSQL full integration tests (Joomla 5 + 6)
# -------------------------------------------------------------------
if [[ $MYSQL_ONLY -eq 0 ]]; then
    echo "=== PostgreSQL full integration tests (Joomla 5 + 6) ==="
    info "Starting Docker containers…"
    remove_wrong_project_containers "j2xml-postgresql" \
        j2xml-postgres j2xml-joomla5-pg j2xml-joomla6-pg
    "${POSTGRES_COMPOSE[@]}" up -d

    # Stale containers can carry a configuration.php from a previous run with
    # the wrong DB driver (compose up -d reuses containers). Verify before
    # spending ten minutes on false failures.
    for c in j2xml-joomla5-pg j2xml-joomla6-pg; do
        if docker ps --format '{{.Names}}' | grep -qx "$c"; then
            dbtype=$(docker exec "$c" grep dbtype /var/www/html/configuration.php 2>/dev/null | grep -o "'[a-z]*'" | tr -d "'" || true)
            if [[ -n "$dbtype" && "$dbtype" != "pgsql" && "$dbtype" != "postgresql" ]]; then
                info "Stale $c has dbtype=$dbtype — recreating PG stack…"
                "${POSTGRES_COMPOSE[@]}" down -v
                "${POSTGRES_COMPOSE[@]}" up -d
                break
            fi
        fi
    done

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
    "${POSTGRES_COMPOSE[@]}" down -v
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
