#!/usr/bin/env bash
#
# Build J2XML package zip from source.
#
# Produces build/pkg_j2xml.zip containing:
#   com_j2xml.zip, lib_eshiol_J2xml.zip, plg_system_j2xml.zip,
#   plg_webservices_j2xml.zip
#
# Usage: scripts/build-package.sh [output_dir]
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
OUTPUT_DIR="${1:-$ROOT_DIR/build}"

VERSION="4.0.0"
DATE="$(date +%Y-%m-%d)"

# --- helpers ----------------------------------------------------------------

# Substitute __DEPLOY_VERSION__ and __DEPLOY_DATE__ in a file (in-place)
substitute_placeholders() {
    local file="$1"
    sed -i '' "s/__DEPLOY_VERSION__/$VERSION/g" "$file"
    sed -i '' "s/__DEPLOY_DATE__/$DATE/g" "$file"
}

# Copy a directory tree, excluding .DS_Store and .git
copy_dir() {
    local src="$1"
    local dst="$2"
    mkdir -p "$dst"
    if [ -d "$src" ]; then
        cp -R "$src"/* "$dst/" 2>/dev/null || true
        find "$dst" -name '.DS_Store' -delete
    fi
}

# Build a zip from a staging directory
make_zip() {
    local staging="$1"
    local zipname="$2"
    local zipfile="$OUTPUT_DIR/$zipname"
    rm -f "$zipfile"
    (cd "$staging" && zip -r -q "$zipfile" .)
    echo "  Built: $zipname"
}

# --- main -------------------------------------------------------------------

echo "Building J2XML package v$VERSION ($DATE)"
echo "Output: $OUTPUT_DIR"
mkdir -p "$OUTPUT_DIR"
TMPDIR="$(mktemp -d)"
trap 'rm -rf "$TMPDIR"' EXIT

# --- com_j2xml.zip -----------------------------------------------------------
echo "Building com_j2xml.zip..."
STAGING="$TMPDIR/com_j2xml"
mkdir -p "$STAGING"
# Manifest at root
cp "$ROOT_DIR/administrator/components/com_j2xml/j2xml.xml" "$STAGING/j2xml.xml"
cp "$ROOT_DIR/administrator/components/com_j2xml/script.php" "$STAGING/script.php"
# Site files (folder="site")
mkdir -p "$STAGING/site"
cp "$ROOT_DIR/components/com_j2xml/j2xml.php" "$STAGING/site/j2xml.php"
copy_dir "$ROOT_DIR/components/com_j2xml/controllers" "$STAGING/site/controllers"
# Admin files (folder="admin")
mkdir -p "$STAGING/admin"
cp "$ROOT_DIR/administrator/components/com_j2xml/access.xml" "$STAGING/admin/access.xml"
cp "$ROOT_DIR/administrator/components/com_j2xml/config.xml" "$STAGING/admin/config.xml"
copy_dir "$ROOT_DIR/administrator/components/com_j2xml/forms" "$STAGING/admin/forms"
copy_dir "$ROOT_DIR/administrator/components/com_j2xml/layouts" "$STAGING/admin/layouts"
copy_dir "$ROOT_DIR/administrator/components/com_j2xml/sql" "$STAGING/admin/sql"
copy_dir "$ROOT_DIR/administrator/components/com_j2xml/views" "$STAGING/admin/views"
copy_dir "$ROOT_DIR/administrator/components/com_j2xml/services" "$STAGING/admin/services"
copy_dir "$ROOT_DIR/administrator/components/com_j2xml/src" "$STAGING/admin/src"
# API files (folder="api")
mkdir -p "$STAGING/api"
copy_dir "$ROOT_DIR/api/components/com_j2xml/src" "$STAGING/api/src"
# Admin language (folder="admin/language")
mkdir -p "$STAGING/admin/language/en-GB"
cp "$ROOT_DIR/administrator/language/en-GB/en-GB.com_j2xml.ini" "$STAGING/admin/language/en-GB/"
cp "$ROOT_DIR/administrator/language/en-GB/en-GB.com_j2xml.sys.ini" "$STAGING/admin/language/en-GB/"
# Media (folder="media")
mkdir -p "$STAGING/media/js" "$STAGING/media/css"
copy_dir "$ROOT_DIR/media/com_j2xml/js" "$STAGING/media/js"
copy_dir "$ROOT_DIR/media/com_j2xml/css" "$STAGING/media/css"
cp "$ROOT_DIR/media/com_j2xml/joomla.asset.json" "$STAGING/media/joomla.asset.json"
substitute_placeholders "$STAGING/media/joomla.asset.json"
# Substitute placeholders
substitute_placeholders "$STAGING/j2xml.xml"
# Substitute __DEPLOY_VERSION__ in JS files
find "$STAGING/media/js" -type f -name '*.js' | while read -r f; do
    substitute_placeholders "$f"
done
make_zip "$STAGING" "com_j2xml.zip"

# --- lib_eshiol_J2xml.zip ---------------------------------------------------
echo "Building lib_eshiol_J2xml.zip..."
STAGING="$TMPDIR/lib_eshiol_J2xml"
mkdir -p "$STAGING"
# Manifest at root
cp "$ROOT_DIR/administrator/manifests/libraries/eshiol/j2xml.xml" "$STAGING/j2xml.xml"
# Library files (from libraries/eshiol/J2xml/)
cp "$ROOT_DIR/libraries/eshiol/J2xml/Exporter.php" "$STAGING/"
cp "$ROOT_DIR/libraries/eshiol/J2xml/Importer.php" "$STAGING/"
cp "$ROOT_DIR/libraries/eshiol/J2xml/Messages.php" "$STAGING/"
cp "$ROOT_DIR/libraries/eshiol/J2xml/Sender.php" "$STAGING/"
cp "$ROOT_DIR/libraries/eshiol/J2xml/Version.php" "$STAGING/"
cp "$ROOT_DIR/libraries/eshiol/J2xml/classmap.php" "$STAGING/"
copy_dir "$ROOT_DIR/libraries/eshiol/J2xml/Table" "$STAGING/Table"
# Language (folder="language")
mkdir -p "$STAGING/language/en-GB"
cp "$ROOT_DIR/language/en-GB/en-GB.lib_j2xml.ini" "$STAGING/language/en-GB/"
cp "$ROOT_DIR/language/en-GB/en-GB.lib_j2xml.sys.ini" "$STAGING/language/en-GB/"
# Media (folder="media")
mkdir -p "$STAGING/media/js"
copy_dir "$ROOT_DIR/media/lib_eshiol_j2xml/js" "$STAGING/media/js"
cp "$ROOT_DIR/media/lib_eshiol_j2xml/joomla.asset.json" "$STAGING/media/joomla.asset.json"
substitute_placeholders "$STAGING/media/joomla.asset.json"
# Substitute placeholders
substitute_placeholders "$STAGING/j2xml.xml"
# Substitute __DEPLOY_VERSION__ in JS files
find "$STAGING/media/js" -type f -name '*.js' | while read -r f; do
    substitute_placeholders "$f"
done
make_zip "$STAGING" "lib_eshiol_J2xml.zip"

# --- plg_system_j2xml.zip ---------------------------------------------------
echo "Building plg_system_j2xml.zip..."
STAGING="$TMPDIR/plg_system_j2xml"
mkdir -p "$STAGING"
# Manifest at root
cp "$ROOT_DIR/plugins/system/j2xml/j2xml.xml" "$STAGING/j2xml.xml"
# Plugin files
cp "$ROOT_DIR/plugins/system/j2xml/j2xml.php" "$STAGING/"
cp "$ROOT_DIR/plugins/system/j2xml/install.mysql.sql" "$STAGING/"
cp "$ROOT_DIR/plugins/system/j2xml/install.postgresql.sql" "$STAGING/"
cp "$ROOT_DIR/plugins/system/j2xml/install.sqlazure.sql" "$STAGING/"
copy_dir "$ROOT_DIR/plugins/system/j2xml/layouts" "$STAGING/layouts"
# Language (folder="language")
mkdir -p "$STAGING/language/en-GB"
cp "$ROOT_DIR/administrator/language/en-GB/en-GB.plg_system_j2xml.ini" "$STAGING/language/en-GB/"
cp "$ROOT_DIR/administrator/language/en-GB/en-GB.plg_system_j2xml.sys.ini" "$STAGING/language/en-GB/"
# Substitute placeholders
substitute_placeholders "$STAGING/j2xml.xml"
make_zip "$STAGING" "plg_system_j2xml.zip"

# --- plg_webservices_j2xml.zip ----------------------------------------------
echo "Building plg_webservices_j2xml.zip..."
STAGING="$TMPDIR/plg_webservices_j2xml"
mkdir -p "$STAGING"
# Manifest at root
cp "$ROOT_DIR/plugins/webservices/j2xml/j2xml.xml" "$STAGING/j2xml.xml"
# Plugin files
cp "$ROOT_DIR/plugins/webservices/j2xml/j2xml.php" "$STAGING/"
cp "$ROOT_DIR/plugins/webservices/j2xml/index.html" "$STAGING/"
# Language (folder="language")
mkdir -p "$STAGING/language/en-GB"
cp "$ROOT_DIR/administrator/language/en-GB/en-GB.plg_webservices_j2xml.ini" "$STAGING/language/en-GB/"
cp "$ROOT_DIR/administrator/language/en-GB/en-GB.plg_webservices_j2xml.sys.ini" "$STAGING/language/en-GB/"
# Substitute placeholders
substitute_placeholders "$STAGING/j2xml.xml"
make_zip "$STAGING" "plg_webservices_j2xml.zip"

# --- pkg_j2xml.zip ----------------------------------------------------------
echo "Building pkg_j2xml.zip..."
STAGING="$TMPDIR/pkg_j2xml"
mkdir -p "$STAGING"
# Manifest at root
cp "$ROOT_DIR/administrator/manifests/packages/pkg_j2xml.xml" "$STAGING/pkg_j2xml.xml"
# Sub-zips
cp "$OUTPUT_DIR/com_j2xml.zip" "$STAGING/"
cp "$OUTPUT_DIR/lib_eshiol_J2xml.zip" "$STAGING/"
cp "$OUTPUT_DIR/plg_system_j2xml.zip" "$STAGING/"
cp "$OUTPUT_DIR/plg_webservices_j2xml.zip" "$STAGING/"
# Package language
mkdir -p "$STAGING/language/en-GB"
cp "$ROOT_DIR/language/en-GB/en-GB.pkg_j2xml.sys.ini" "$STAGING/language/en-GB/"
# Substitute placeholders
substitute_placeholders "$STAGING/pkg_j2xml.xml"
make_zip "$STAGING" "pkg_j2xml.zip"

echo ""
echo "Done. Package: $OUTPUT_DIR/pkg_j2xml.zip"
echo "Sub-packages:"
ls -la "$OUTPUT_DIR"/*.zip
