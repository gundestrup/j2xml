# J2XML

[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/gundestrup/j2xml)
[![PHP](https://img.shields.io/badge/PHP-8.4%20%26%208.5-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Joomla](https://img.shields.io/badge/Joomla!-5%20%26%206-5091CD?logo=joomla&logoColor=white)](https://www.joomla.org/)
[![Version](https://img.shields.io/badge/version-4.5.2-blue.svg)](./CHANGELOG.md)
[![License: GPL v3](https://img.shields.io/badge/License-GPL_v3-blue.svg)](./LICENSE)
[![Changelog](https://img.shields.io/badge/CHANGELOG-Updated-brightgreen.svg)](./CHANGELOG.md)
[![GitHub issues](https://img.shields.io/github/issues/gundestrup/j2xml?logo=github)](https://github.com/gundestrup/j2xml/issues)
[![GitHub stars](https://img.shields.io/github/stars/gundestrup/j2xml?logo=github)](https://github.com/gundestrup/j2xml/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/gundestrup/j2xml?logo=github)](https://github.com/gundestrup/j2xml/forks)
[![Last commit](https://img.shields.io/github/last-commit/gundestrup/j2xml?logo=github)](https://github.com/gundestrup/j2xml/commits)
[![Semgrep](https://img.shields.io/badge/Semgrep-Pro-brightgreen?logo=semgrep&logoColor=white)](./.semgrep.yml)
[![CodeFactor](https://www.codefactor.io/repository/github/gundestrup/j2xml/badge)](https://www.codefactor.io/repository/github/gundestrup/j2xml)
[![SonarCloud](https://sonarcloud.io/api/project_badges/measure?project=gundestrup_j2xml&metric=alert_status)](https://sonarcloud.io/dashboard?id=gundestrup_j2xml)
[![codecov](https://codecov.io/gh/gundestrup/j2xml/graph/badge.svg?token=J2XML_CODECOV)](https://codecov.io/gh/gundestrup/j2xml)

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
- **Send** exported content to another Joomla! site through Joomla's
  Webservices REST API using token authentication.

The original project by [Helios Ciancio](https://www.eshiol.it) targets
Joomla! 3.x and 4.x. This fork updates it for Joomla! 5 and 6 and PHP 8.4,
fixing deprecations and the import failures reported on Joomla 5.x
([eshiol/j2xml#72](https://github.com/eshiol/j2xml/issues/72),
[#71](https://github.com/eshiol/j2xml/issues/71),
[#70](https://github.com/eshiol/j2xml/issues/70)).

## Package contents

- **Component** `com_j2xml` — Administrator UI for export / import / send.
- **Library** `eshiol/J2xml` — Core `Exporter`, `Importer`, `Version`, and `Table\\*` classes.
- **Plugin** `plg_system_j2xml` — System plugin for content preparation and UI integration.
- **Plugin** `plg_webservices_j2xml` — Joomla Webservices REST import endpoint.
- **CLI** `cli/j2xml.php` — Separate command-line importer.

The CLI importer is maintained in the repository separately from the
`pkg_j2xml.zip` package. J2XML does not require third-party extensions for
normal content migration.
The optional [Attachments for J2XML](https://www.eshiol.it/joomla/j2xml/attachments-for-j2xml.html)
connector integrates with the separate [Attachments component](https://github.com/jmcameron/attachments)
to transfer attachments. That connector is not bundled with J2XML and must be
maintained separately for Joomla 5/6 and current PHP versions.

## Requirements

- **PHP:** 8.4 or 8.5 (8.3 is Joomla 6's minimum, but this fork targets 8.4+).
- **Joomla!:** 5.x or 6.x.
- **MySQL:** 8.0.13+ (or MariaDB 10.4+).
- **PostgreSQL:** 12.0+.

**Required PHP extensions:** `json`, `simplexml`, `dom`, `zlib`, `gd`,
`mbstring`, and a MySQL or PostgreSQL PDO driver.

## Installation

1. Download the latest `pkg_j2xml.zip` from
   [releases](https://github.com/gundestrup/j2xml/releases).
2. In Joomla! admin go to **System → Install → Extensions**.
3. Upload the package zip. Joomla installs the component, library, system
   plugin, and Webservices plugin.

## CLI usage

The repository also contains a command-line importer. Run it from the Joomla
site root with a J2XML file:

```bash
php cli/j2xml.php -f /tmp/import.xml
```

## Development

`VERSION` is the single source of truth for the release version. Use
`scripts/release-check.sh` to validate the VERSION file, changelog, manifests,
and release archives. Use `scripts/build-package.sh` directly when only a
package build is needed. The build substitutes `__DEPLOY_VERSION__` /
`__DEPLOY_DATE__` placeholders and creates the component, library, plugin, and
package archives.

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
[`AGENTS.md`](./AGENTS.md) first — it is the single source
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
# Quick: quality checks only (PHP lint, PHPStan, Semgrep, ShellCheck, XML, PHPUnit)
./scripts/check-quality.sh

# Full: integration tests against Docker Joomla 5 + 6
./scripts/check-tests.sh

# Everything: quality + tests (pre-release validation)
./scripts/check-all.sh
```

Or run the integration tests manually:

```bash
cd tests/docker
docker compose up -d
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

The current MySQL and PostgreSQL integration suites each cover Joomla 5 and
Joomla 6 with 97 assertions, including PHP warning/deprecation checks:

```text
  Passed: 97
  Failed: 0
  Skipped: 0
  Total:  97
```

The PHPUnit suite contains **69 tests and 128 assertions**. Run it without
coverage locally when no PCOV/Xdebug driver is installed:

```bash
vendor/bin/phpunit --configuration phpunit.xml.dist --no-coverage
```

### Integration coverage

The latest MySQL and PostgreSQL integration runs recorded **2,723 of 3,714 executable lines** across
64 active J2XML files: **73.32% line coverage**. This is coverage of the
integration suite only, not a statement that 73.32% of every production branch
has been tested. The merge script emits files observed in PCOV dumps; active
files never loaded by the integration suite are not added as zero-coverage
files, so this is an observed-line metric and can overstate whole-repository
coverage.

The remaining uncovered lines are concentrated in defensive and optional paths:

- `libraries/eshiol/J2xml/Table/Table.php` — resolver fallbacks, error paths,
  association handling, and database-specific branches.
- `libraries/eshiol/J2xml/Table/Content.php` — update/overwrite combinations,
  rating/front-page preservation, ID remapping, and workflow failures.
- `libraries/eshiol/J2xml/Exporter.php` — response compression/header branches,
  optional related-entity exports, and empty/error cases.
- `administrator/components/com_j2xml/src/Model/ImportModel.php` — upload,
  URL/folder input, validation, and option/error branches.
- `cli/j2xml.php`, installer lifecycle code, and version fallback paths.

Dead files removed during cleanup no longer contribute to this denominator. The
coverage report is therefore measuring the remaining active code, not counting
removed legacy code as uncovered. The merge includes the administrator, Webservices API, library, system-plugin,
and CLI paths. CI uploads PHPUnit coverage with the `unittests` flag and Docker
coverage with the `integration` flag; Codecov may show a different combined
percentage depending on which reports have arrived.

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
the PHP 8.4/8.5 compatibility fixes and REST-based transfer cleanup.

## Links

- **Upstream:** <https://github.com/eshiol/j2xml>
- **Original author:** <https://www.eshiol.it>
- **DeepWiki:** <https://deepwiki.com/gundestrup/j2xml>
- **Issues:** <https://github.com/gundestrup/j2xml/issues>
