#!/usr/bin/env bash
# Exercise release-tag and monotonic update-stream decisions without GitHub.

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
SCRIPT="$ROOT/scripts/check-update-release.sh"
fail() { echo "FAIL: $1" >&2; exit 1; }

valid="$(bash "$SCRIPT" v4.5.5 4.5.5 4.5.6 4.5.4)" || fail "valid release was rejected"
[[ "$valid" = $'match=true\nversion=4.5.5' ]] || fail "valid release output was unexpected: $valid"

equal="$(bash "$SCRIPT" v4.5.5 4.5.5 4.5.5 4.5.5)" || fail "idempotent release was rejected"
[[ "$equal" = $'match=true\nversion=4.5.5' ]] || fail "idempotent output was unexpected: $equal"

older="$(bash "$SCRIPT" v4.5.5 4.5.5 4.5.6 4.5.6)" || fail "older release should be skipped"
[[ "$older" = "match=false" ]] || fail "older release was not skipped"

prerelease="$(bash "$SCRIPT" v4.5.5-rc1 4.5.5-rc1 4.5.6 4.5.4)" || fail "prerelease should be skipped"
[[ "$prerelease" = "match=false" ]] || fail "prerelease was not skipped"

if bash "$SCRIPT" v4.5.5 4.5.4 4.5.6 4.5.4 >/dev/null 2>&1; then
    fail "tag/VERSION mismatch was accepted"
fi
if bash "$SCRIPT" v4.5.6 4.5.6 4.5.5 4.5.4 >/dev/null 2>&1; then
    fail "release newer than main VERSION was accepted"
fi

echo "test-check-update-release: PASS"
