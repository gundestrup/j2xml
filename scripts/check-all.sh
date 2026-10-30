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
    printf '%b✓ Quality checks passed%b\n' "$green" "$nc"
else
    printf '%b✗ Quality checks FAILED%b\n' "$red" "$nc"
    overall_fail=1
fi
echo ""

# 2. Integration tests
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  PHASE 2: Integration tests"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
if ./scripts/check-tests.sh; then
    printf '%b✓ Integration tests passed%b\n' "$green" "$nc"
else
    printf '%b✗ Integration tests FAILED%b\n' "$red" "$nc"
    overall_fail=1
fi
echo ""

# Summary
echo "========================================"
if [[ $overall_fail -eq 0 ]]; then
    printf '%b  ✓ All checks passed — ready for release%b\n' "$green" "$nc"
    exit 0
else
    printf '%b  ✗ Some checks failed — fix before release%b\n' "$red" "$nc"
    exit 1
fi
