#!/usr/bin/env bash
# Functional test for scripts/build-update-xml.sh — runs the generator on a
# synthetic zip and verifies the produced update.xml. Run locally or in CI;
# does not require Joomla or Docker.

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

# Synthetic release asset: a real zip so the output is a faithful artifact.
echo "dummy package payload" >"$TMP/payload.txt"
(cd "$TMP" && zip -q pkg_j2xml.zip payload.txt)

OUT="$TMP/update.xml"
bash "$REPO_ROOT/scripts/build-update-xml.sh" "$TMP/pkg_j2xml.zip" "$OUT" >/dev/null

errors=0
fail() { echo "FAIL: $1" >&2; errors=$((errors + 1)); }
field() { xmllint --xpath "string($1)" "$OUT" 2>/dev/null; }

xmllint --noout "$OUT" 2>/dev/null || fail "output is not well-formed XML"

version="$(tr -d '[:space:]' <"$REPO_ROOT/VERSION")"
[[ "$(field /updates/update/version)" = "$version" ]] || fail "version not interpolated from VERSION"
[[ "$(field /updates/update/element)" = "pkg_j2xml" ]] || fail "element wrong"
[[ "$(field /updates/update/type)" = "package" ]] || fail "type wrong"
[[ "$(field /updates/update/client)" = "site" ]] || fail "client must be site — pkg extensions install with client_id=0"
[[ "$(field /updates/update/downloads/downloadurl)" = "https://github.com/gundestrup/j2xml/releases/download/v${version}/pkg_j2xml.zip" ]] || fail "downloadurl wrong"
[[ "$(field /updates/update/sha256)" = "$(shasum -a 256 "$TMP/pkg_j2xml.zip" | awk '{print $1}')" ]] || fail "sha256 does not match input zip"
[[ "$(field /updates/update/sha384)" = "$(shasum -a 384 "$TMP/pkg_j2xml.zip" | awk '{print $1}')" ]] || fail "sha384 does not match input zip"
[[ "$(field /updates/update/sha512)" = "$(shasum -a 512 "$TMP/pkg_j2xml.zip" | awk '{print $1}')" ]] || fail "sha512 does not match input zip"

# The release workflow generates a stream from the published tag while
# checking out main, whose VERSION may already be ahead.
next_version="$(awk -F. -v OFS=. '{$3+=1; print}' "$REPO_ROOT/VERSION")"
OVERRIDE_OUT="$TMP/update-override.xml"
bash "$REPO_ROOT/scripts/build-update-xml.sh" "$TMP/pkg_j2xml.zip" "$OVERRIDE_OUT" "$next_version" >/dev/null
[[ "$(xmllint --xpath 'string(/updates/update/version)' "$OVERRIDE_OUT")" = "$next_version" ]] || fail "explicit release version was ignored"
[[ "$(xmllint --xpath 'string(/updates/update/downloads/downloadurl)' "$OVERRIDE_OUT")" = "https://github.com/gundestrup/j2xml/releases/download/v${next_version}/pkg_j2xml.zip" ]] || fail "explicit release version not used in download URL"

# Usage error paths must fail, not silently write a file.
if bash "$REPO_ROOT/scripts/build-update-xml.sh" "$TMP/nope.zip" "$TMP/o2.xml" 2>/dev/null; then
    fail "missing zip did not error"
fi
[[ ! -f "$TMP/o2.xml" ]] || fail "output written despite missing zip"

if [[ $errors -gt 0 ]]; then
    echo "test-build-update-xml: $errors failure(s)" >&2
    exit 1
fi
echo "test-build-update-xml: PASS"
