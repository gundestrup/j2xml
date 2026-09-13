#!/usr/bin/env bash
#
# Run ALL checks: quality tools + integration tests.
# This is the full pre-release validation.
#
# Usage:
#   ./scripts/check-all.sh
#
set -euo pipefail

cd "$(dirname "$0")/.." || exit 1

red='\033[0;31m'
green='\033[0;32m'
nc='\033[0m'

overall_fail=0

echo "========================================"
echo "  J2XML — Full Pre-Release Check"
echo "========================================"
echo ""

# 1. Quality checks
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  PHASE 1: Quality checks"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
if ./scripts/check-quality.sh; then
    printf "${green}✓ Quality checks passed${nc}\n"
else
    printf "${red}✗ Quality checks FAILED${nc}\n"
    overall_fail=1
fi
echo ""

# 2. Integration tests
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  PHASE 2: Integration tests"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
if ./scripts/check-tests.sh; then
    printf "${green}✓ Integration tests passed${nc}\n"
else
    printf "${red}✗ Integration tests FAILED${nc}\n"
    overall_fail=1
fi
echo ""

# Summary
echo "========================================"
if [ $overall_fail -eq 0 ]; then
    printf "${green}  ✓ All checks passed — ready for release${nc}\n"
    exit 0
else
    printf "${red}  ✗ Some checks failed — fix before release${nc}\n"
    exit 1
fi
