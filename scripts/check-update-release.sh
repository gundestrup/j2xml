#!/usr/bin/env bash
# Decide whether a published release may advance the Joomla update stream.
# Arguments: release-tag tag-version main-version current-stream-version
# Output on stdout is suitable for appending to GITHUB_OUTPUT.

set -euo pipefail

TAG="${1:?usage: $0 <release-tag> <tag-version> <main-version> <stream-version>}"
TAG_VERSION="${2:?usage: $0 <release-tag> <tag-version> <main-version> <stream-version>}"
MAIN_VERSION="${3:?usage: $0 <release-tag> <tag-version> <main-version> <stream-version>}"
STREAM_VERSION="${4:?usage: $0 <release-tag> <tag-version> <main-version> <stream-version>}"

if [[ ! "$TAG" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "match=false"
    echo "Ignoring non-stable release tag: $TAG" >&2
    exit 0
fi

RELEASE_VERSION="${TAG#v}"
for version in "$TAG_VERSION" "$MAIN_VERSION" "$STREAM_VERSION"; do
    [[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || {
        echo "Invalid semantic version: $version" >&2
        exit 1
    }
done

if [[ "$TAG_VERSION" != "$RELEASE_VERSION" ]]; then
    echo "Release tag $TAG contains VERSION $TAG_VERSION" >&2
    exit 1
fi

LATEST_MAIN="$(printf '%s\n%s\n' "$RELEASE_VERSION" "$MAIN_VERSION" | sort -V | tail -1)"
if [[ "$LATEST_MAIN" != "$MAIN_VERSION" ]]; then
    echo "Release $TAG is newer than main VERSION $MAIN_VERSION" >&2
    exit 1
fi

LATEST_STREAM="$(printf '%s\n%s\n' "$RELEASE_VERSION" "$STREAM_VERSION" | sort -V | tail -1)"
if [[ "$LATEST_STREAM" != "$RELEASE_VERSION" ]]; then
    echo "match=false"
    echo "Update stream is already newer ($STREAM_VERSION); ignoring $TAG" >&2
    exit 0
fi

echo "match=true"
echo "version=$RELEASE_VERSION"
