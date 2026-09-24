#!/usr/bin/env bash
#
# Run all static quality checks: PHP lint, PHPStan, Semgrep, ShellCheck,
# Markdownlint, XML validation, PHPUnit (with coverage).
#
# Usage:
#   ./scripts/check-quality.sh          # run all checks
#   ./scripts/check-quality.sh --quick  # skip PHPUnit coverage (faster)
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

QUICK=0
[[ "${1:-}" = "--quick" ]] && QUICK=1

# -------------------------------------------------------------------
# 1. PHP lint (all PHP files, all available 8.4+ binaries)
# -------------------------------------------------------------------
echo "=== PHP lint ==="
php_binaries=()
for candidate in \
    /opt/homebrew/opt/php@8.4/bin/php \
    /opt/homebrew/opt/php@8.5/bin/php \
    /usr/local/opt/php@8.4/bin/php \
    /usr/local/opt/php@8.5/bin/php
do
    [[ -x "$candidate" ]] && php_binaries+=("$candidate")
done
path_php=$(command -v php 2>/dev/null || true)
[[ -n "$path_php" ]] && php_binaries+=("$path_php")

lint_errors=0
for php_bin in "${php_binaries[@]}"; do
    ver=$("$php_bin" -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')
    info "PHP $ver"
    file_errors=0
    while IFS= read -r f; do
        [[ -f "$f" ]] || continue
        if ! output=$("$php_bin" -l "$f" 2>&1); then
            printf "${red}    FAIL: %s${nc}\n" "$f"
            printf "    %s\n" "$output"
            file_errors=$((file_errors + 1))
            lint_errors=$((lint_errors + 1))
        fi
    done < <(git ls-files '*.php')
    if [[ $file_errors -eq 0 ]]; then
        info "PHP $ver — clean"
    fi
done
if [[ $lint_errors -eq 0 ]]; then
    ok "PHP lint"
else
    fail "PHP lint ($lint_errors errors)"
fi

# -------------------------------------------------------------------
# 2. PHPStan
# -------------------------------------------------------------------
echo "=== PHPStan ==="
phpstan_bin=""
if command -v phpstan &>/dev/null; then
    phpstan_bin="phpstan"
elif [[ -x vendor/bin/phpstan ]]; then
    phpstan_bin="vendor/bin/phpstan"
fi

if [[ -n "$phpstan_bin" ]]; then
    if $phpstan_bin analyse --no-progress --memory-limit=1G --error-format=table 2>&1; then
        ok "PHPStan"
    else
        fail "PHPStan"
    fi
else
    fail "PHPStan — not found (composer install)"
fi

# -------------------------------------------------------------------
# 3. Semgrep
# -------------------------------------------------------------------
echo "=== Semgrep ==="
if command -v semgrep &>/dev/null; then
    if semgrep scan --config .semgrep.yml --error libraries/ plugins/ components/ administrator/ cli/ 2>&1 | tail -5; then
        ok "Semgrep"
    else
        fail "Semgrep"
    fi
else
    info "semgrep not found — skipping (pip3 install semgrep)"
fi

# -------------------------------------------------------------------
# 4. ShellCheck
# -------------------------------------------------------------------
echo "=== ShellCheck ==="
if command -v shellcheck &>/dev/null; then
    shellcheck_errors=0
    while IFS= read -r f; do
        if ! shellcheck --severity=warning "$f" 2>&1; then
            shellcheck_errors=$((shellcheck_errors + 1))
        fi
    done < <(git ls-files '*.sh')
    if [[ $shellcheck_errors -eq 0 ]]; then
        ok "ShellCheck"
    else
        fail "ShellCheck ($shellcheck_errors errors)"
    fi
else
    info "shellcheck not found — skipping (brew install shellcheck)"
fi

# -------------------------------------------------------------------
# 5. Markdownlint
# -------------------------------------------------------------------
echo "=== Markdownlint ==="
if command -v npx &>/dev/null; then
    if npx --yes markdownlint-cli2@0.23.2; then
        ok "Markdownlint"
    else
        fail "Markdownlint"
    fi
else
    info "npx not found — skipping (CI runs markdownlint-cli2)"
fi

# -------------------------------------------------------------------
# 6. XML validation
# -------------------------------------------------------------------
echo "=== XML validation ==="
xml_errors=0
while IFS= read -r f; do
    # This fixture is intentionally malformed to exercise import rejection.
    [[ "$f" = "tests/fixtures/malformed.xml" ]] && continue
    if ! xmllint --noout "$f" 2>/dev/null; then
        printf "${red}    FAIL: %s${nc}\n" "$f"
        xml_errors=$((xml_errors + 1))
    fi
done < <(git ls-files '*.xml')
if [[ $xml_errors -eq 0 ]]; then
    ok "XML validation"
else
    fail "XML validation ($xml_errors errors)"
fi

# -------------------------------------------------------------------
# 7. PHPUnit
# -------------------------------------------------------------------
echo "=== PHPUnit ==="
if [[ -x vendor/bin/phpunit ]]; then
    if [[ $QUICK -eq 1 ]]; then
        info "Skipping coverage (--quick mode)"
        phpunit_out=$(vendor/bin/phpunit --configuration phpunit.xml.dist --no-coverage 2>&1)
    else
        phpunit_out=$(vendor/bin/phpunit --configuration phpunit.xml.dist --coverage-clover coverage.xml 2>&1)
    fi
    echo "$phpunit_out" | tail -10
    if echo "$phpunit_out" | grep -qE "^OK \(|^OK,"; then
        ok "PHPUnit"
    else
        fail "PHPUnit"
    fi
else
    fail "PHPUnit — not found (composer install)"
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
    printf "${green}All checks passed.${nc}\n"
    exit 0
fi
