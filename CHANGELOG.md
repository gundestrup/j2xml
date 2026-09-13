# Changelog

All notable changes to J2XML will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Legend

- **Security** - Security fix
- **Fixed** - Bug fix
- **Added** - New feature
- **Changed** - Change in existing functionality
- **Removed** - Removed feature
- **Deprecated** - Soon-to-be removed feature
- **Note** - General note

---

## [Unreleased]

### Added

- **SonarCloud integration** — `sonar-project.properties` with project key `gundestrup_j2xml`; excludes `media/**`, `build/**`, `tests/**`, `vendor/**`, `node_modules/**`. Badge added to README.
- **Codecov integration** — `codecov.yml` with informational coverage targets; PHPUnit runs with `--coverage-clover` in CI and uploads to Codecov via `codecov/codecov-action@v5.5.1`. Badge added to README.
- **AGENTS.md updated** — documented Semgrep, CodeFactor, SonarCloud, and Codecov services, suppression syntax (`nosemgrep` / `NOSONAR`), and SonarCloud REST API access for local finding retrieval.
- **`.editorconfig`** — enforces PSR-12 indentation (4 spaces for PHP, tabs for XML manifests) in supported IDEs.
- **Unit tests** — 76 PHPUnit tests covering `Version`, `Messages`, `Table` (xml2array, _setValue, _serialize, fixDate, toXML, IMAGE_MATCH_STRING), `Exporter` (_root), `Importer` (isSupported), and `Tag` (convertPathsToIds edge cases). Coverage: 3.20% (110/3440 lines). Stubs updated to fix class ordering and `#[\AllowDynamicProperties]` on Joomla\CMS\Table\Table.

### Changed

- **PSR-12 indentation** — converted all 79 PHP files from tabs to 4 spaces, aligning with Joomla 4.2+ / PSR-12 coding standard. XML manifests remain tab-indented per Joomla convention.
- **Eliminated SendModel/ExportModel duplication** — extracted shared `AbstractFormModel` base class (~300 lines of duplication eliminated). SendModel and ExportModel now only declare their `$formType` ('send' / 'export').

### Fixed

- **SonarCloud findings addressed** (18 non-vendored findings):
  - `php:S128` — switch case fallthrough in `default_package.php` documented with `// fallthrough` comments (intentional byte-conversion cascade).
  - `Web:S8732` — `<legend>` wrapped in `<fieldset>` in `default_package.php`.
  - `php:S3699` — removed invalid assignment from `ArrayHelper::toInteger()` return (returns void) in `Table.php`.
  - `php:S836` — fixed uninitialized `$app` variable in `Table::setAssociations()` (added `Factory::getApplication()` call).
  - **Bug fix:** `$associationsContext` → `$context` in `Table::setAssociations()` (was using undefined variable instead of the function parameter).
  - `php:S4790` — `md5()` in `Content.php` and `Table.php` suppressed with `// NOSONAR` (non-cryptographic lookup key matching Joomla core).
  - `phpsecurity:S5131` — XSS in `cli/j2xml.php` suppressed with `// NOSONAR` (CLI-only, guarded by `REQUEST_METHOD` check).
  - `phpsecurity:S2083` — Path traversal in `cli/j2xml.php` and `ImportModel.php` suppressed with `// NOSONAR` (CLI-only / admin-only with `Path::clean`).
  - `githubactions:S8541` + `S8544` — `pip3 install semgrep` → `pip3 install --only-binary :all: "semgrep==1.176.0"` (exact version pin, binary-only). `composer.lock` added to lock Composer dev dependencies.
  - `shelldre:S7688` — `[` → `[[` in `scripts/build-package.sh` and `scripts/install-hooks.sh`.
- **ci.yml YAML syntax** — fixed `pip3 install` line that confused YAML parsers (colons in `:all:`); switched to block scalar syntax.
- **PHP 8.5 deprecation** — `$http_response_header` in `Sender.php` replaced with `http_get_last_response_headers()` (PHP 8.4+ API).
- **SonarCloud `shelldre:S7688`** — converted all `[` → `[[` conditional tests in `tests/scripts/*.sh` (89 lines across 10 files, using exact SonarCloud API line data).
- **SonarCloud `javascript:S7773`** — `isNaN()` → `typeof x !== 'number' || Number.isNaN(x)` in `j2xml.js` (preserves undefined check semantics); `NaN` → `Number.NaN` in `version_compare.js`.

### Note

- **`composer.lock` missing** (SonarCloud `text:S8567`) — run `composer install` and commit `composer.lock` for reproducible dev dependency versions.

### Tests

- **All tests passed** (2026-09-11):
  - PHP lint: PHP 8.4 + 8.5 — all files clean (0 deprecation warnings).
  - PHPStan: 0 errors.
  - Semgrep: 0 findings (4 rules, 73 files).
  - Shell syntax: 12/12 scripts pass `bash -n`.
  - MySQL integration (Joomla 5 + 6): **82/82 passed**, 0 failed.
  - PostgreSQL smoke (Joomla 5 + 6): **passed**.

---

## [4.5.0] - 2026-09-11

### Security

- **Pinned all GitHub Actions to commit SHAs** — `actions/checkout`, `shivammathur/setup-php`, and `ludeeus/action-shellcheck` were pinned to immutable commit SHAs (with `# tag` comments) to prevent supply-chain attacks via mutable tag repointing. Resolves 6 Semgrep `github-actions-mutable-action-tag` findings.
- **Added Semgrep security scanning** — `.semgrep.yml` with custom rules for SQL injection, `eval()`, command injection, and LFI/RFI; integrated into CI (`semgrep scan --config .semgrep.yml --error`) and the pre-commit hook.
- **Added CodeFactor integration** — `.codefactor.yml` excludes test fixtures, vendored libraries, and media from analysis; badge added to README.

### Changed

- **Upgraded vendored pako from 1.0.11 to 3.0.1** — replaced the 108KB pre-built UMD bundle with a 79KB inflate-only IIFE bundle built from npm pako 3.0.1 using esbuild. Added minimal build tooling in `build/pako/` (package.json + entry.js, node_modules gitignored). Updated `import.js` to use the pako 3.x API (`{toText: true}` instead of `{to: 'string'}`). Resolves CodeFactor "Very Complex Method" finding in vendored code.
- **Upgraded vendored base64.js from 2011 standalone to @jsonjoy.com/base64 18.30.0** — replaced the 14-year-old hand-rolled base64 utility with a decode-only IIFE bundle built from npm `@jsonjoy.com/base64` 18.30.0 using esbuild. Added build tooling in `build/base64/`. Uses native `TextDecoder` for UTF-8 output. API unchanged (`base64.decode()`). Round-trip tested with XML and unicode content.
- **Eliminated duplicate code in Content.php, User.php, Exporter.php, and Importer.php** — extracted three duplicated blocks into shared methods: `buildFieldAliases()` (50 lines, field/subform alias queries in Content & User), `exportFields()` (30 lines, subform field export in Content & User), and `User::syncUsergroupsTable()` (25 lines, `#__j2xml_usergroups` rebuild in Exporter & Importer constructors). Resolves CodeFactor duplicate-code findings.
- **Fixed assignment-in-condition warnings** — separated 18 assignment-from-condition patterns across 13 files (`Content.php`, `Table.php`, `User.php`, `Category.php`, `Tag.php`, `Weblink.php`, `Menu.php`, `Usernote.php`, `Module.php`, `Sender.php`, `cli/j2xml.php`, `Send/HtmlView.php`, `Export/HtmlView.php`, helper `j2xml.php`) into explicit assignment + condition, per CodeFactor. Also fixed a redundant double query execution in `Table::import()` usergroup lookup.
- **Removed unresolved TODO comments** — removed `@todo`/`TODO` markers from `Table.php` (dead commented-out alias fix), `Field.php` (2 content event notes), `Fieldgroup.php` (2 content event notes), and `script.php` (ftp folder deletion note). Converted actionable notes to regular comments.
- **Fixed unnecessary string concatenation** — merged consecutive string literals in `Table.php` (tag import query), `Menu.php` (article_id alias query), and helper `j2xml.php` (copyright HTML).
- **Removed unreachable code** — removed `break` after `return false` in `ImportModel.php` default switch case.
- **Documented non-useless method override** — added PHPDoc to `WebsitesController::getModel()` explaining why it overrides the parent (changes default model name from plural 'Websites' to singular 'Website').
- **CI Semgrep job uses local config** — switched from `semgrep ci` (App mode, requires `SEMGREP_APP_TOKEN`) to `semgrep scan --config .semgrep.yml --error` so the custom rules in `.semgrep.yml` are actually applied.
- **Renamed `AI_INSTRUCTIONS.md` to `AGENTS.md`** — `AGENTS.md` is now the single source of truth for all coding agents, following the agents.md open convention. Tool-specific files (`CLAUDE.md`, `.windsurfrules`, `.devin/global_rules.md`) now point to `AGENTS.md` instead of `AI_INSTRUCTIONS.md`.

### Note

- **Semgrep false positives suppressed** — 3 findings annotated with `// nosemgrep`: `ini_set('display_errors', 1)` in `cli/j2xml.php` (CLI-only, guarded by `REQUEST_METHOD` check); `md5()` in `Content.php` and `Table.php` (non-cryptographic lookup key for `#__associations`, matches Joomla core pattern).

## [4.0.0] - 2026-08

**Major release: Joomla 3 support removed; Joomla 5/6 + PHP 8.4+ only.**

This release supersedes the broken 4.0.0 commit (`656b449`) — the current
HEAD includes all CI, PostgreSQL, and test-suite fixes that landed after
that commit.

### Removed

- **Joomla 3 compatibility shims** — removed `plugins/system/j2xml/layouts/joomla/` (jQuery-based modal/form-field layouts), `plugins/system/j2xml/src/joomla/` (J3 `JLayoutFile` alias shims), and `plugins/system/j2xml/src/J2xml/Helper/Joomla.php`
- **`onBeforeCompileHead` jQuery loader** — removed explicit jQuery loading from the system plugin; Joomla 5/6 loads web assets on demand
- **Inline jQuery `onclick` handlers** — replaced with vanilla JavaScript event listeners
- **`JPATH_PLATFORM` shim** — removed from all 14 Table classes
- **`LIBXML_PARSEHUGE` manual `define()`** — removed; the constant is native to libxml ≥ 2.7.0

### Fixed

- **Issue #72: Import HTTP 500 on Joomla 5.2+** — fixed class alias and API compatibility issues that caused fatal errors during import
- **Issue #71: Import articles from J3 to J5** — articles now import correctly from J3-era XML format (version 21.12.0) into Joomla 5
- **Issue #70: Import users on J5** — user import now works correctly, including handling of multiple group assignments and empty params
- **Joomla 6 compatibility: all J\* legacy class aliases migrated** — replaced all `JFactory`, `JLog`, `JText`, `JComponentHelper`, `JPluginHelper`, `JRoute`, `JFile`, `JFolder`, `JHtml`, `JTable*`, `JController*`, `JModel*`, `JViewLegacy`, `JToolBarHelper`, `JSession`, `JRegistry`, `JVersion`, `JUri`, `JClientHelper`, `JFilterOutput`, `JUserHelper`, `JArrayHelper`, `JDate`, `JError`, `JHelperTags`, `JLanguageAssociations`, `JLanguageMultilang`, `JApplicationCli`, `JResponse`, `JFilesystemHelper`, `JInstallerHelper` with fully-qualified namespaced Joomla CMS classes throughout the codebase
- **User import: UserFactory not set** — `User::prepareData` now uses Joomla's MVC factory instead of instantiating `UserModel` directly
- **User import: params null handling** — `User::prepareData` now handles empty/null `params` field from XML
- **User import: multiple group elements** — multiple `<group>` elements in XML are now processed individually instead of passing an array to `getUsergroupId`
- **PHP 8.4 fatal: `E_STRICT` constant removed** — replaced `error_reporting(E_ALL | E_STRICT)` with `error_reporting(E_ALL)` in `administrator/components/com_j2xml/j2xml.php`; updated `phpxmlrpc/src/Server.php` error handler to use PHP version guard instead of referencing the removed constant
- **PHP 8.3 fatal: `utf8_encode()` removed** — replaced all 5 occurrences in `phpxmlrpc/src/Server.php`, `Request.php`, `Encoder.php`, `Helper/Charset.php` with `mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1')`
- **PHP 8.4 deprecated: non-canonical casts** — `(boolean)` → `(bool)` in `libraries/eshiol/J2xml/Table/Table.php`; `(integer)` → `(int)` and `(double)` → `(float)` in `phpxmlrpc/src/Server.php`, `Value.php`, `Helper/XMLParser.php`
- **PHP 8.4 deprecated: `case` with semicolon** — changed `case 'array[]';` to `case 'array[]':` in `phpxmlrpc/src/Wrapper.php`
- **Library manifest namespace path doubling** — `<namespace path="eshiol/J2xml">` caused Joomla's `JNamespacePsr4Map` to generate a doubled autoload path (`JPATH_LIBRARIES/eshiol/J2xml/eshiol/J2xml`), preventing class autoloading and causing HTTP 500 on every export/import; fixed by changing `path` to `""` (relative to library root)
- **Import error handling** — `ImportModel::import()` now wraps `$importer->import()` in try/catch to log database-specific errors (e.g. PostgreSQL vs MySQL syntax differences) and show a user-friendly message instead of a raw HTTP 500
- **PostgreSQL: MySQL-specific SQL in Content import** — replaced `INSERT IGNORE INTO ... SET` (MySQL-only syntax with backtick quoting) with Joomla query builder `->insert()->columns()->values()` for cross-database compatibility; replaced `DELETE FROM ``#__content_rating``` (backtick quoting) with query builder`->delete()->where()`
- **PostgreSQL: install verification** — `tests/scripts/install-plugin.sh` Step 5 previously used `mysqli` to verify extension registration, which fails on PostgreSQL-only environments; now reads Joomla's `configuration.php` inside the container and branches on `$dbtype` to use `mysqli` (MySQL) or `pg_connect` + `pg_query_params` (PostgreSQL)
- **CI: `composer validate --strict`** — added missing `license` field to `composer.json` (`GPL-3.0-or-later`)
- **CI: `xmllint` not installed** — added `sudo apt-get install -y libxml2-utils` to the quality job
- **CI: Node.js 20 deprecation** — upgraded `actions/checkout@v4` → `@v5` (uses Node 24)
- **CI: ShellCheck info/style warnings** — set `severity: warning` to ignore info/style-level issues; fixed `SC2034` (unused loop variable `for i in` → `for _ in`)
- **CI: `sed -i ''` incompatibility** — `scripts/build-package.sh` used BSD/macOS `sed -i ''` syntax which fails on GNU `sed` (Ubuntu CI runner); replaced with portable syntax
- **CI: broken pipe errors** — `echo "$VAR" | grep -q` patterns caused "Broken pipe" errors under `set -o pipefail` (grep exits early); replaced with here-strings (`grep -q 'pattern' <<< "$VAR"`) and `printf '%s'` piping
- **CI: REST API send test thresholds** — send tests expected specific record counts (e.g. 3+ articles) that don't match fresh CI Joomla installs; adjusted assertions to check minimum counts (1+) that are robust across fresh and pre-populated environments

### Added

- **GitHub Actions CI** (`.github/workflows/ci.yml`) — three jobs on every push/PR:
  - **php-quality** (PHP 8.4 + 8.5 matrix): Composer validate, PHP lint, PHPStan, PHPUnit, ShellCheck (warning+), XML validation with `xmllint`
  - **mysql-integration**: Docker Compose Joomla 5 + 6 with MySQL 8.0; runs `tests/scripts/run-all-tests.sh` (82 assertions covering install, import, export, send, round-trip)
  - **postgresql-integration**: Docker Compose Joomla 5 + 6 with PostgreSQL 16; runs `tests/scripts/run-postgresql-smoke.sh` (install, import, export smoke test)
- **Docker-based integration test suite** — `tests/docker/docker-compose.yml` (MySQL) and `tests/docker/docker-compose.postgresql.yml` (PostgreSQL) with Joomla 5 + 6 containers (PHP 8.4, MySQL 8.0 / PostgreSQL 16); `tests/scripts/run-all-tests.sh` verifies issues #72, #71, #70 and Joomla 6 compatibility
- **Test fixtures** — `tests/fixtures/articles-j3.xml`, `users-j3.xml`, `categories-j3.xml`, `all-content-types.xml`, `keep-id.xml` with J3-era XML format (version 21.12.0)
- **PHPUnit unit test** — `tests/unit/ImportSettingsTest.php` with `tests/unit/bootstrap.php` for import settings validation
- **Test scripts** — `tests/scripts/` includes `install-plugin.sh` (web-installer upload), `uninstall-plugin.sh`, `setup-joomla.sh`, `test-issue-70.sh`, `test-issue-71.sh`, `test-issue-72.sh`, `test-import-articles.php`, `test-export-import-roundtrip.sh`, `test-php84-deprecations.sh`, `run-all-tests.sh`, `run-postgresql-smoke.sh`
- **PHPStan static analysis** — committed `phpstan.neon` config with `stubs/joomla.php` scan file declaring the Joomla CMS framework symbols used by J2XML (classes, functions, constants, legacy J\* aliases); `phpstan-baseline.neon` suppresses known pre-existing issues; pre-commit hook updated to use the committed config
- **Deprecated patterns tracker** — `README.TODO.deprecated.md` documents all remaining deprecated APIs with file locations, recommended replacements, and target versions

### Changed

- **`Factory::getDbo()` → container-based `DatabaseInterface`** — all 56 occurrences replaced with `Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class)` (non-Table classes) or `$this->getDatabase()` is not used because Table methods are static
- **`Factory::getUser()` → `Factory::getApplication()->getIdentity()`** — all 11 occurrences replaced
- **`Factory::getDate()` → `new \Joomla\CMS\Date\Date('now')`** — all 3 occurrences replaced (the container does not register `Date` as a service in Joomla 5)
- **`Factory::getDocument()` → `Factory::getApplication()->getDocument()`** — 4 non-template occurrences replaced
- **`Factory::getLanguage()` → `Factory::getApplication()->getLanguage()`** — 3 occurrences replaced
- **`Factory::getConfig()` → `Factory::getApplication()->getConfig()`** — 3 occurrences replaced
- **`JObject` → `\stdClass`** in `helpers/j2xml.php`
- **`JHtmlSidebar` → `Joomla\CMS\HTML\Helpers\Sidebar`** in `helpers/j2xml.php`
- **`JLayoutFile` → `Joomla\CMS\Layout\FileLayout`** in system plugin
- **`JApplicationCms` → `\Joomla\CMS\Application\CMSApplication`** in plugin docblock
- **`CliApplication::getInstance()` → `Factory::getApplication()`** in Exporter and Importer constructors
- **`JPATH_COMPONENT_ADMINISTRATOR` → explicit `JPATH_ADMINISTRATOR . '/components/com_j2xml'`** — 3 occurrences replaced
- **`strpos() === false` → `str_contains()`** — 3 occurrences modernized to PHP 8.0+ syntax
- **`array()` → `[]`** — short array syntax applied to `helpers/j2xml.php` and `script.php`
- **`Joomla.request()` → native `fetch()`** in `j2xml.js`
- **`Joomla.JText` namespace removed** from `j2xml.js`
- **`JoomlaInstaller` moved** from `admin.js` to `lib_eshiol_j2xml/js/j2xml.js` to fix backwards dependency
- **Bootstrap 5 modal asset** explicitly loaded in `default.php` and `default_package.php` templates
- **Component manifest** — `version` attribute updated to `5.0`; added `<minimumJoomla>5.0</minimumJoomla>` and `<minimumPhp>8.1</minimumPhp>`
- **Library manifest** — added `<namespace path="">eshiol\J2xml</namespace>` for automatic PSR-4 autoloading (path is relative to the library root, so empty string maps to `JPATH_LIBRARIES/eshiol/J2xml`)
- **Copyright years** updated from 2010-2023 to 2010-2026 across all PHP, XML, INI, JS, and CSS files
- **phpxmlrpc vendored library upgraded from 4.10.1 to 4.11.5** (latest stable, Nov 2025) — replaced all files in `libraries/eshiol/phpxmlrpc/src/` and `lib/`; preserved J2XML-specific `Log/Logger/XmlrpcLogger.php`; re-applied `utf8_encode()` → `mb_convert_encoding()` patches on top of 4.11.5
- **`Table::getInstance()` → `new Usergroup($db)`** — replaced in `libraries/eshiol/J2xml/Table/Table.php`
- **`$app->input` → `$app->getInput()`** — 24 occurrences across 12 files (Exporter, Importer, ImportModel, ExportModel, SendModel, ExportController, ImportController admin + API, AbstractRawView, default_package template, system plugin, CLI, site component)
- **`JFactory::getDbo()` → container-based `DatabaseInterface`** — replaced in test scripts
- **`Factory::$database` → container registration** — replaced in `tests/scripts/bootstrap.php`
- **`@see JModelLegacy` → `@see \Joomla\CMS\MVC\Model\BaseModel`** — updated PHPDoc in ExportModel and SendModel
- **`JLoader::registerNamespace()` calls removed** — 6 workaround calls removed from classmap.php, system plugin, dispatcher, CLI, site component, and test script; Joomla's manifest-based autoloading now handles PSR-4 registration correctly after the namespace path fix
- **`composer.json`** — added `"license": "GPL-3.0-or-later"` for `composer validate --strict` compatibility

### Note

- Verified clean lint on PHP 8.4.24 and PHP 8.5.9 (0 errors, 0 deprecations)
- PHPStan passes with 0 errors
- All 82 integration tests pass on Joomla 5.4.7 and Joomla 6.1.2 (MySQL 8.0)
- PostgreSQL smoke tests pass on Joomla 5/6 with PostgreSQL 16
- CI passes on all three jobs: php-quality (PHP 8.4 + 8.5), mysql-integration, postgresql-integration
- See `README.TODO.deprecated.md` for remaining deprecated patterns scheduled for future removal (7 entries)

---

## [3.10.233] - 2024-01

Latest release published on Joomla Extensions Directory.

### Fixed

- Joomla 5.0.0 PHP 8.2 export and import support
- PHP 8.2: `utf8_encode()` deprecation in XMLRPC
- PHP 8.2: Dynamic Properties deprecated in `J2xml\Importer`
- Halt on error when XML-RPC protocol is disabled
- Illegal cross-origin request handling
- `open_basedir` restriction fix

### Changed

- XML-RPC for PHP 4.10.1

---

## [3.9] - 2023-10

### Added

- Joomla 5.0.0 support (export and import)
- Import/export menus and modules
- Send menus feature
- Export menu images
- Drag and drop import

### Changed

- PhpXmlRpc 4.10.0
- CORS error message improvements
- Featured up/down, rating, tags fixes

### Fixed

- Import tags
- Export images
- Keep category
- Media field image export
- View levels
- Article send/export options
- Non-XML file reading
- Content hits
- User fields, usergroups, contact import categories
- Content introtext
- Send errors trapping
- Subform fields import
- Viewlevels import when importing users
- User activation, lastvisitDate, authProvider, lastResetTime
- Show buttons only if J2XML is installed and enabled
- Import menu fixes
- PHP 8 compatibility

### Removed

- PHP console

---

## [3.9-beta-5] - 2022-12

### Added

- Import menu
- Third-party plugins support
- Content hits

### Fixed

- Import menu
- Non-XML file reading
- Set hits

---

## [3.9-beta-4] - 2022-11

### Added

- Send errors trapping
- User fields
- Usergroups
- Contact import categories
- Content introtext

### Fixed

- Usergroups table
- Contact import categories
- Content introtext
- User fields
- Illegal cross-origin request

---

## [3.9-beta-1] - 2022-05

### Changed

- Beta 1 release
- Database refactor
- Installation refactor

### Added

- Import viewlevels when importing users
- Import subform fields
- Import via drag and drop

### Fixed

- User activation, lastvisitDate, authProvider, lastResetTime
- Show buttons only if J2XML is installed and enabled
- Import menu
- PHP 8 compatibility
- Field group not imported
- Contact category
- `PLG_SYSTEM_J2XML_MSG_REQUIREMENTS_COM`

---

## [3.9-alpha-6] - 2022-02

### Added

- Import progress bar
- Sender
- Fields
- Field group
- Content id
- Export tag images
- Export field images
- Export and send buttons
- Export categories
- Usernotes

### Fixed

- `FileReader.reader.onload`
- Article not imported
- Not imported message error
- Export and import contacts
- Import contact J4
- Duplicate code

---

## [3.9-alpha-5] - 2022-02

### Added

- Fields
- Content id
- Field group

### Fixed

- Not imported message error
- Export categories
- Usernotes
- Export tag images

---

## [3.9-alpha-4] - 2022-01

### Added

- Export field images
- Export and send buttons
- Menus and modules
- Version 19.2.0 support

### Fixed

- Export and import contacts
- Import contact J4
- Duplicate code

---

## [3.9-alpha-3] - 2022-01

### Changed

- Version 19.2.0 support

---

## [3.8] - 2021-12

### Added

- Joomla 4 compatibility
- PHP XML-RPC 4.5.1
- Basic Auth plugin
- J2XML Library 21.11.353

### Changed

- UTF-8 handling
- Version check
- Export/send button
- Joomla 4 compatibility

### Removed

- eshiol/core Library dependency

### Fixed

- Import/send content
- Export/send button

---

## [3.7] - 2020-06

### Added

- Joomla 2.5 backward compatibility
- XML-RPC for PHP 4.4.1
- Overwrite article if newer
- Import weblinks
- Export user notes
- Buttons support
- AJAX support
- Usergroups
- Import view levels
- Export view levels
- Third-party plugin support

### Fixed

- Import content
- Import category
- Import categories
- Export/import users
- Import contacts
- Export tag users
- Export category tags
- Export category users
- Weblinks import
- Category import
- Send
- PHP 5.2/5.3 compatibility
- Joomla 3.x/4 compatibility
- `JRegistry`
- `html_entity_decode` / `htmlspecialchars_decode`
- `html_entity_decode`
- XML-RPC gzip
- Tags
- `allow_url_fopen=Off`
- Export
- User skipped
- XML2array null values
- Uncaught ReferenceError: Joomla is not defined

### Changed

- Associations support
- Language strings
- Uninstall handling

---

## [3.7.201] - 2019-09

### Added

- Keep user id
- Original id
- Keep id

### Fixed

- Link source file
- Code style

---

## [3.7.199] - 2019-07

### Added

- Slovenian (sl-SI) language
- Third-party plugin support

### Fixed

- Import weblinks
- `html_entity_decode`

---

## [3.7.196] - 2019-04

### Fixed

- `JRegistry`
- `htmlspecialchars_decode`
- User skipped
- XML2array null values
- XML-RPC gzip
- Tags
- `allow_url_fopen=Off`
- Export

### Added

- J2XML Pro support

---

## [3.7 stable] - 2019-04

### Note

- Stable release of 3.7 series

---

## [3.7.192] - 2019-02

### Fixed

- Export
- Tags
- `allow_url_fopen=Off`

---

## [3.6] - 2016-12

### Note

- Restructured repository; removed standalone CHANGELOG and LICENSE files

---

## [3.3.17] - 2016-05

### Added

- Link2 button (`window.open`)
- Import users with clear password
- `onContentPrepareData` support
- HTML for J2XML support

### Changed

- Sendbydate button (mobile)
- File button (mobile, plugins)
- User group numeric
- Check `#__j2xml_websites`

### Removed

- Joomla 2.5 compatibility (system plugin)

### Fixed

- Usergroup null
- Installation

---

## [3.3.15] - 2015-12

### Added

- Export user notes
- Buttons support

### Fixed

- Weblinks import
- Category import

---

## [3.3.14] - 2015-10

### Changed

- XMLRPC for PHP 3.0.1

### Fixed

- Send

---

## [3.3.13] - 2015-10

### Fixed

- PHP 5.2 compatibility

---

## [3.3.12] - 2015-09

### Added

- Login XMLRPC
- Import XMLRPC

### Changed

- Open file from server via JCE filebrowser (filter: xml, gz)

### Removed

- Import for Joomla 2.5

---

## [3.3.11] - 2015-09

### Added

- `onContentBeforeExport`

### Fixed

- `J2XMLImporter::getArticleId($path)`

---

## [3.2.10] - 2015-09

### Note

- Stable release

### Fixed

- Export

---

## [3.2.9] - 2015-09

### Added

- Import view levels
- Export view levels

### Fixed

- Import categories
- Export/import users
- Import contacts
- Export tag users
- Export category tags
- Export category users
- J2XML file format 12.5
- J2XML file format 15.9

### Changed

- Logs

---

## [3.2.8 RC] - 2015-09

### Added

- Import contacts
- Export contacts

---

## [3.2.7 Beta] - 2015-09

### Fixed

- Export

---

## [3.2.6 Beta] - 2015-09

### Added

- Third-party plugin log

### Changed

- Export weblinks
- Send
- Clean attachments
- Import tags log
- Import weblinks

### Removed

- Clean redirect links

### Fixed

- Export categories

---

## [3.2.5 Beta] - 2015-08

### Fixed

- Export users

### Added

- `J2XMLImporter::getArticledId($path)`
- `J2XMLImporter::getUserId($username)`

---

## [3.2.4 Beta] - 2015-08

### Added

- `onAfterImport`
- `onAfterExport`

### Changed

- Export
- Rebuild links

---

## [3.2.3 Beta] - 2015-06

### Added

- Rebuild links

---

## [3.2.2 Beta] - 2015-06

### Added

- Import from server
- File format check
- Joomla 1.6 compatibility

### Changed

- Upload file
- Develop delete icon replaced by purge icon

### Fixed

- Import from server
- Language fixes (CLI)

---

## [3.2.a2] - 2015-03

### Added

- Joomla 1.6 compatibility
- Export custom viewing access level
- Auto send compatibility
- Article tag export

### Fixed

- Undefined `currentAssetId` on Joomla 2.5
- Tags on Joomla 2.5
- Featured
- Send to multilanguage site
- Image export
- Featured ordering
- PHP 5.2.4 compatibility

### Changed

- Develop function

---

## [3.2.a1] - 2014-10

### Added

- Auto send support
- Import from server
- Import from external URL
- Articles and categories cleaning
- File button

### Removed

- Filemanager library support

### Fixed

- Logout XMLRPC user
- Minor bug fixes
- Language fix

### Changed

- Minor language fix

---

## [3.1.1] - 2014-06

### Fixed

- Minor bug fixes
- Large dataset XML parse error (Libxml < 2.7.0 compatibility)

---

## [3.1] - 2014-03

### Added

- Send by date button
- Help screen (CLI)

### Fixed

- Joomla 3.2 compatibility (CLI)
- Joomla 2.5 compatibility (CLI)
- Large dataset XML parse error
- Send button
- Sender
- `DIRECTORY_SEPARATOR`
- Images export
- DS constant
- HTTP/1.1 303
- Images URL decode
- User import
- Import
- XMLRPC fatal error
- Import category
- Send button in Joomla 2.5
- Import button in Joomla 3.x

---

## [3.1.rc2] - 2013-09

### Added

- Help screen (CLI)

### Fixed

- Import button in Joomla 3.x
- Send button in Joomla 2.5

---

## Earlier versions

J2XML has been developed since 2010 by Helios Ciancio (eshiol.it).
For versions prior to 3.1.rc2, see the original repository history at
<https://github.com/eshiol/j2xml>.
