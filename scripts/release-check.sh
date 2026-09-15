#!/usr/bin/env bash
# Validate release metadata and, unless --metadata-only is supplied, build and
# validate the release archives. This script never creates tags, commits, or
# pushes anything.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
VERSION_FILE="$ROOT_DIR/VERSION"
METADATA_ONLY=0

if [[ "${1:-}" = "--metadata-only" ]]; then
    METADATA_ONLY=1
elif [[ $# -gt 0 ]]; then
    echo "Usage: $0 [--metadata-only]" >&2
    exit 1
fi

if [[ ! -f "$VERSION_FILE" ]]; then
    echo "Missing VERSION file: $VERSION_FILE" >&2
    exit 1
fi

VERSION="$(tr -d '[:space:]' < "$VERSION_FILE")"
TAG="v$VERSION"

if [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Invalid semantic version in VERSION: $VERSION" >&2
    exit 1
fi

if ! awk -v version="$VERSION" '
    $0 ~ "^## \\[" version "\\]" { found = 1; exit }
    END { exit(found ? 0 : 1) }
' "$ROOT_DIR/CHANGELOG.md"; then
    echo "CHANGELOG.md has no release heading for $VERSION" >&2
    exit 1
fi

for manifest in \
    "$ROOT_DIR/administrator/components/com_j2xml/j2xml.xml" \
    "$ROOT_DIR/administrator/manifests/libraries/eshiol/j2xml.xml" \
    "$ROOT_DIR/administrator/manifests/packages/pkg_j2xml.xml" \
    "$ROOT_DIR/plugins/system/j2xml/j2xml.xml" \
    "$ROOT_DIR/plugins/webservices/j2xml/j2xml.xml" \
    "$ROOT_DIR/administrator/manifests/files/cli_j2xml.xml"
do
    if ! grep -Fq '__DEPLOY_VERSION__' "$manifest"; then
        echo "Manifest lacks __DEPLOY_VERSION__ placeholder: $manifest" >&2
        exit 1
    fi
done

if [[ "$METADATA_ONLY" -eq 0 ]]; then
    if git rev-parse --verify --quiet "refs/tags/$TAG" >/dev/null; then
        echo "Tag already exists locally: $TAG" >&2
        exit 1
    fi

    if git remote get-url origin >/dev/null 2>&1 \
        && git ls-remote --exit-code --quiet origin "refs/tags/$TAG" >/dev/null 2>&1; then
        echo "Tag already exists on origin: $TAG" >&2
        exit 1
    fi
fi

if [[ "$METADATA_ONLY" -eq 1 ]]; then
    echo "Release metadata is valid for $VERSION."
    exit 0
fi

if ! command -v unzip >/dev/null 2>&1; then
    echo "unzip is required to validate release archives" >&2
    exit 1
fi

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

"$ROOT_DIR/scripts/build-package.sh" "$TMP_DIR"

manifest_version() {
    local archive="$1"
    local manifest="$2"

    unzip -p "$archive" "$manifest" \
        | php -r '$xml = simplexml_load_string(stream_get_contents(STDIN)); echo $xml ? (string) $xml->version : "";'
}

for archive in \
    "$TMP_DIR/com_j2xml.zip" \
    "$TMP_DIR/lib_eshiol_J2xml.zip" \
    "$TMP_DIR/plg_system_j2xml.zip" \
    "$TMP_DIR/plg_webservices_j2xml.zip" \
    "$TMP_DIR/pkg_j2xml.zip"
do
    if [[ ! -s "$archive" ]]; then
        echo "Missing release archive: $archive" >&2
        exit 1
    fi

done

for archive in \
    "$TMP_DIR/com_j2xml.zip" \
    "$TMP_DIR/lib_eshiol_J2xml.zip" \
    "$TMP_DIR/plg_system_j2xml.zip" \
    "$TMP_DIR/plg_webservices_j2xml.zip" \
    "$TMP_DIR/pkg_j2xml.zip"
do
    if [[ "$archive" = *"pkg_j2xml.zip" ]]; then
        archive_version="$(manifest_version "$archive" pkg_j2xml.xml)"
    else
        archive_version="$(manifest_version "$archive" j2xml.xml)"
    fi
    if [[ "$archive_version" != "$VERSION" ]]; then
        echo "Version mismatch in $(basename "$archive"): $archive_version != $VERSION" >&2
        exit 1
    fi
done

unzip -t "$TMP_DIR/pkg_j2xml.zip" >/dev/null
echo "Release $VERSION metadata and package archives are valid."
