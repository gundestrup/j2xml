#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
FIXTURE="$ROOT_DIR/tests/fixtures/articles-j3.xml"
COOKIE_DIR="${TMPDIR:-/tmp}/j2xml-pg-cookies"
mkdir -p "$COOKIE_DIR"

wait_for_url() {
	local url="$1"
	for _ in $(seq 1 60); do
		if curl -sf -o /dev/null "$url/"; then return 0; fi
		sleep 2
	done
	return 1
}

login() {
	local url="$1"
	local cookie="$2"
	local page token code
	page=$(curl -s -c "$cookie" "$url/administrator/index.php")
	token=$(echo "$page" | sed -n 's/.*name="\([a-f0-9]\{32\}\)" value="1".*/\1/p' | head -1)
	[ -n "$token" ]
	code=$(curl -s -c "$cookie" -b "$cookie" -L -o /dev/null -w '%{http_code}' \
		-X POST "$url/administrator/index.php" \
		-d "username=admin&passwd=AdminAdmin123!&option=com_login&task=login&${token}=1")
	[ "$code" = 200 ]
}

import_fixture() {
	local url="$1"
	local cookie="$2"
	local page token code
	page=$(curl -s -c "$cookie" -b "$cookie" "$url/administrator/index.php?option=com_j2xml&view=import")
	token=$(echo "$page" | grep -o '"csrf\.token"[[:space:]]*:[[:space:]]*"[a-f0-9]\{32\}"' | sed 's/.*"\([a-f0-9]\{32\}\)"/\1/' | head -1)
	[ -n "$token" ]
	code=$(curl -s -L -c "$cookie" -b "$cookie" -o /dev/null -w '%{http_code}' \
		-X POST "$url/administrator/index.php?option=com_j2xml&task=import.import" \
		-H "X-CSRF-Token: $token" \
		-F "task=import.import" -F "${token}=1" -F installtype=upload \
		-F jform[import_content]=1 -F jform[import_categories]=1 -F jform[import_users]=0 \
		-F jform[import_tags]=0 -F jform[import_menus]=0 -F jform[import_modules]=0 \
		-F jform[import_contacts]=0 -F jform[import_fields]=0 -F jform[import_viewlevels]=0 \
		-F jform[import_images]=0 -F "install_package=@$FIXTURE")
	[ "$code" = 200 ] || [ "$code" = 303 ]
}

export_articles() {
	local url="$1"
	local cookie="$2"
	local db="$3"
	local prefix="$4"
	local page token ids code
	ids=$(docker exec j2xml-postgres psql -U joomla -d "$db" -Atc "SELECT string_agg(id::text, chr(44)) FROM \"${prefix}content\"")
	page=$(curl -s -c "$cookie" -b "$cookie" "$url/administrator/index.php?option=com_j2xml&view=export&layout=content")
	token=$(echo "$page" | grep -o '"csrf\.token"[[:space:]]*:[[:space:]]*"[a-f0-9]\{32\}"' | sed 's/.*"\([a-f0-9]\{32\}\)"/\1/' | head -1)
	[ -n "$token" ]
	code=$(curl -s -L -c "$cookie" -b "$cookie" -o /tmp/j2xml-pg-export.xml -w '%{http_code}' \
		-X POST "$url/administrator/index.php?option=com_j2xml&task=content.display&format=raw" \
		-H "X-CSRF-Token: $token" -F "task=content.display" -F "${token}=1" \
		-F "jform[cid]=$ids" -F jform[export_compression]=0 -F jform[export_categories]=1 \
		-F jform[export_fields]=0 -F jform[export_images]=0 -F jform[export_tags]=0)
	[ "$code" = 200 ]
	grep -q '<j2xml' /tmp/j2xml-pg-export.xml
	grep -q '<content>' /tmp/j2xml-pg-export.xml
}

wait_for_url "http://localhost:8185"
wait_for_url "http://localhost:8186"
bash "$ROOT_DIR/scripts/build-package.sh" >/tmp/j2xml-pg-build.log 2>&1
J2XML_CONTAINER=j2xml-joomla5-pg J2XML_URL=http://localhost:8185 J2XML_DB=joomla5 \
	bash "$ROOT_DIR/tests/scripts/install-plugin.sh" 5 >/tmp/j2xml-pg-install5.log 2>&1
J2XML_CONTAINER=j2xml-joomla6-pg J2XML_URL=http://localhost:8186 J2XML_DB=joomla6 \
	bash "$ROOT_DIR/tests/scripts/install-plugin.sh" 6 >/tmp/j2xml-pg-install6.log 2>&1

login http://localhost:8185 "$COOKIE_DIR/j5"
login http://localhost:8186 "$COOKIE_DIR/j6"
import_fixture http://localhost:8185 "$COOKIE_DIR/j5"
import_fixture http://localhost:8186 "$COOKIE_DIR/j6"
J5_PREFIX=$(docker exec j2xml-joomla5-pg php -r 'require "/var/www/html/configuration.php"; $c=new JConfig; echo $c->dbprefix;')
J6_PREFIX=$(docker exec j2xml-joomla6-pg php -r 'require "/var/www/html/configuration.php"; $c=new JConfig; echo $c->dbprefix;')
export_articles http://localhost:8185 "$COOKIE_DIR/j5" joomla5 "$J5_PREFIX"
export_articles http://localhost:8186 "$COOKIE_DIR/j6" joomla6 "$J6_PREFIX"
printf '%s\n' 'PostgreSQL Joomla 5/6 smoke tests passed.'
