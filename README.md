# J2XML

[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/gundestrup/j2xml)
[![PHP](https://img.shields.io/badge/PHP-8.4%20%26%208.5-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Joomla](https://img.shields.io/badge/Joomla!-5%20%26%206-5091CD?logo=joomla&logoColor=white)](https://www.joomla.org/)
[![Version](https://img.shields.io/badge/version-3.10.233-blue.svg)](./CHANGELOG.md)
[![License: GPL v3](https://img.shields.io/badge/License-GPL_v3-blue.svg)](./LICENSE)
[![Changelog](https://img.shields.io/badge/CHANGELOG-Updated-brightgreen.svg)](./CHANGELOG.md)
[![GitHub issues](https://img.shields.io/github/issues/gundestrup/j2xml?logo=github)](https://github.com/gundestrup/j2xml/issues)
[![GitHub stars](https://img.shields.io/github/stars/gundestrup/j2xml?logo=github)](https://github.com/gundestrup/j2xml/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/gundestrup/j2xml?logo=github)](https://github.com/gundestrup/j2xml/forks)
[![Last commit](https://img.shields.io/github/last-commit/gundestrup/j2xml?logo=github)](https://github.com/gundestrup/j2xml/commits)

> Export, import, and share Joomla! content as XML between sites.

Fork of [eshiol/j2xml](https://github.com/eshiol/j2xml), modernised for
**Joomla! 5 and 6** with **PHP 8.4**.

---

## What it does

J2XML is a Joomla! extension package that lets you move site content between
Joomla! instances using a portable XML format. It can:

- **Export** articles, categories, users, menus, modules, contacts, weblinks,
  fields, tags, view levels, user notes, and images to an XML file.
- **Import** the same XML into another Joomla! site, creating or updating
  records as needed.
- **Send** content directly to a remote Joomla! site over XML-RPC.

The original project by [Helios Ciancio](https://www.eshiol.it) targets
Joomla! 3.x and 4.x. This fork updates it for Joomla! 5 and 6 and PHP 8.4,
fixing deprecations and the import failures reported on Joomla 5.x
([eshiol/j2xml#72](https://github.com/eshiol/j2xml/issues/72),
[#71](https://github.com/eshiol/j2xml/issues/71),
[#70](https://github.com/eshiol/j2xml/issues/70)).

## Package contents

| Type     | Id                  | Purpose                                                  |
|----------|---------------------|----------------------------------------------------------|
| Component| `com_j2xml`         | Admin UI for export / import / send                      |
| Library  | `eshiol/J2xml`      | Core `Exporter`, `Importer`, `Sender`, `Table\*` classes |
| Library  | `eshiol/phpxmlrpc`  | Vendored XML-RPC client/server (v4.11.5)                 |
| Plugin   | `plg_system_j2xml`  | System plugin: content prep, layouts, compat shims       |
| Plugin   | `plg_system_basicauth` | HTTP Basic Auth for XML-RPC endpoints                 |
| CLI      | `cli/j2xml.php`     | Command-line exporter                                    |

## Requirements

| Software   | Version  |
|------------|----------|
| PHP        | 8.4 or 8.5 (8.3 is Joomla 6's minimum, but this fork targets 8.4+) |
| Joomla!    | 5.x or 6.x |
| MySQL      | 8.0.13+ (or MariaDB 10.4+) |
| PostgreSQL | 12.0+    |

**Required PHP extensions:** `json`, `simplexml`, `dom`, `zlib`, `gd`,
`mbstring`, and a MySQL or PostgreSQL PDO driver.

## Installation

1. Download the latest `pkg_j2xml.zip` from
   [releases](https://github.com/gundestrup/j2xml/releases).
2. In Joomla! admin go to **System → Install → Extensions**.
3. Upload the package zip. Joomla installs all five extensions together.

## CLI usage

```bash
# Export to an XML file (run from the Joomla site root)
php cli/j2xml.php -f /tmp/export.xml
```

> CLI import is not yet implemented — see
> [eshiol/j2xml#68](https://github.com/eshiol/j2xml/issues/68).

## Development

There is no build toolchain — the repo ships the PHP source directly.
Releases are produced by substituting `__DEPLOY_VERSION__` /
`__DEPLOY_DATE__` placeholders in manifests and zipping each extension.

### Local setup

```bash
# PHP 8.4 and 8.5 (macOS / Homebrew)
brew install php@8.4 php@8.5 phpstan

# Install the pre-commit hook (lint + PHPStan on every commit)
./scripts/install-hooks.sh

# Lint a file with both PHP versions
/opt/homebrew/opt/php@8.4/bin/php -l libraries/eshiol/J2xml/Exporter.php
/opt/homebrew/opt/php@8.5/bin/php -l libraries/eshiol/J2xml/Exporter.php

# Static analysis
phpstan analyse libraries/eshiol/J2xml --memory-limit=512M
```

### Contributing

AI coding assistants working on this repo should read
[`AI_INSTRUCTIONS.md`](./AI_INSTRUCTIONS.md) first — it is the single source
of truth for project conventions, layout, and constraints.

## Testing

Integration tests run in Docker against live Joomla 5 and 6 instances with
PHP 8.4 and MySQL 8.0. The test suite verifies the three import bugs fixed
in this fork:

- **Issue #72** — Import no longer returns HTTP 500 on Joomla 5.2+
- **Issue #71** — Articles import correctly from J3 XML format to J5
- **Issue #70** — Users import correctly on J5
- **Joomla 6 / PHP 8.4** — Import works on Joomla 6 with PHP 8.4

### Prerequisites

- Docker Desktop (or Docker Engine + Docker Compose)
- `curl` (pre-installed on macOS / most Linux distros)

### Running the tests

```bash
# Start Joomla 5 (port 8085) and Joomla 6 (port 8086) containers
cd tests/docker
docker compose up -d

# Wait for Joomla to finish installing, then run all tests
cd ../..
bash tests/scripts/run-all-tests.sh
```

The script will:

1. Wait for both Joomla instances to come up
2. Install the J2XML plugin into each via symlinks + DB registration
3. Log in to each admin panel and import the test XML fixtures
4. Verify the expected number of articles/users in the database
5. Print a summary of pass/fail results

### Test output

```
  Passed: 8
  Failed: 0
  Skipped: 0
  Total:  8
```

### Stopping the test environment

```bash
cd tests/docker
docker compose down -v   # -v removes the database volumes too
```

### Test fixtures

XML fixtures live in `tests/fixtures/` and use the J3-era format
(`version="21.12.0"` in the `<j2xml>` root element):

- `articles-j3.xml` — 3 articles with special characters, CDATA, unicode
- `users-j3.xml` — 3 users with multiple group assignments
- `categories-j3.xml` — Test categories

## License

Copyright (C) 2010–2026 Helios Ciancio. Licensed under
[GNU/GPL v3](./LICENSE) — see the [full license text](./LICENSE).

## Changelog

See [`CHANGELOG.md`](./CHANGELOG.md) for the full release history, including
the PHP 8.4/8.5 fixes and phpxmlrpc 4.11.5 upgrade in the current unreleased
version.

## Links

- **Upstream:** <https://github.com/eshiol/j2xml>
- **Original author:** <https://www.eshiol.it>
- **DeepWiki:** <https://deepwiki.com/gundestrup/j2xml>
- **Issues:** <https://github.com/gundestrup/j2xml/issues>
