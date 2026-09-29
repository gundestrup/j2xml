#!/usr/bin/env bash
# Regenerate the Joomla update-server stream (update.xml) for the current
# VERSION. The file is committed at the repository root and served to Joomla
# sites via raw.githubusercontent.com, so it must only describe a published
# release whose asset exists.
#
# Usage: scripts/build-update-xml.sh <pkg_j2xml.zip> [output-file] [version]
#
# The ZIP must be the exact bytes published as the release asset: hashes are
# embedded in update.xml and verified by Joomla on update.

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ZIP="${1:?usage: $0 <pkg_j2xml.zip> [output-file] [version]}"
OUT="${2:-$REPO_ROOT/update.xml}"
VERSION="${3:-$(tr -d '[:space:]' <"$REPO_ROOT/VERSION")}"
ASSET_URL="https://github.com/gundestrup/j2xml/releases/download/v${VERSION}/pkg_j2xml.zip"
INFO_URL="https://github.com/gundestrup/j2xml/releases/tag/v${VERSION}"

[[ -f "$ZIP" ]] || { echo "ZIP not found: $ZIP" >&2; exit 1; }
[[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "Bad VERSION: $VERSION" >&2; exit 1; }

sha() { shasum -a "$1" "$ZIP" | awk '{print $1}'; }
SHA256="$(sha 256)"
SHA384="$(sha 384)"
SHA512="$(sha 512)"

cat >"$OUT" <<EOF
<?xml version="1.0" encoding="UTF-8"?>
<updates>
	<update>
		<name>J2XML</name>
		<description>J2XML for Joomla! 5.x &amp; 6.x — export, import and send content as XML.</description>
		<element>pkg_j2xml</element>
		<type>package</type>
		<client>site</client>
		<version>${VERSION}</version>
		<infourl title="J2XML ${VERSION} release notes">${INFO_URL}</infourl>
		<downloads>
			<downloadurl type="full" format="zip">${ASSET_URL}</downloadurl>
		</downloads>
		<tags>
			<tag>stable</tag>
		</tags>
		<maintainer>Svend Gundestrup</maintainer>
		<maintainerurl>https://github.com/gundestrup/j2xml</maintainerurl>
		<targetplatform name="joomla" version="[56]\..*"/>
		<supported_databases mysql="8.0.13" mariadb="10.4" postgresql="12"/>
		<php_minimum>8.4</php_minimum>
		<sha256>${SHA256}</sha256>
		<sha384>${SHA384}</sha384>
		<sha512>${SHA512}</sha512>
	</update>
</updates>
EOF

echo "Wrote $OUT for ${VERSION} (sha256 ${SHA256:0:16}…)"
