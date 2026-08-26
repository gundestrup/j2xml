# AGENTS.md — J2XML

> **Single source of truth for all coding agents working on this project.**
>
> Tool-specific files (CLAUDE.md, .windsurfrules, .devin/global_rules.md) reference this document.

---

## 1. Project Overview

**J2XML** is a Joomla! extension package that exports and imports site content
as XML, and can push/pull that content between Joomla! instances over XML-RPC.

- **Author / maintainer:** Helios Ciancio — <https://www.eshiol.it>
- **License:** GNU/GPL v3 (<http://www.gnu.org/licenses/gpl-3.0.html>)
- **Target platforms:** Joomla! **5 and 6** with **PHP 8.4 and 8.5**.
  (The upstream `eshiol/j2xml` targets Joomla 3.x/4.x; this fork drops
  older PHP/Joomla support to focus on modern versions.)
- **Package name (Joomla):** `pkg_j2xml` (currently versioned as **3.9**).
- **Language:** PHP (no runtime JS build pipeline; Composer is used for
  development-only PHPUnit/PHPStan tooling, while runtime dependencies remain
  vendored as Joomla libraries).

The package is composed of several Joomla extensions that are bundled together
by `administrator/manifests/packages/pkg_j2xml.xml`:

| Type     | Id           | Path                                       | Purpose                                            |
|----------|--------------|--------------------------------------------|----------------------------------------------------|
| Component| `com_j2xml`  | `administrator/components/com_j2xml/`, `components/com_j2xml/` | Admin UI for export/import/send; site entry point |
| Library  | `j2xml`      | `libraries/eshiol/J2xml/`                  | Core `Exporter`, `Importer`, `Sender`, `Messages`, `Version`, `Table\*` |
| Library  | `phpxmlrpc`  | `libraries/eshiol/phpxmlrpc/`              | Vendored XML-RPC client/server (v4.11.5) used by Sender; `Log/Logger/XmlrpcLogger.php` is J2XML-specific |
| Plugin   | `j2xml` (system) | `plugins/system/j2xml/`                | System plugin: content preparation, layouts, Joomla 3/4/5 compatibility shims |
| Plugin   | `basicauth` (system) | `plugins/system/basicauth/`        | HTTP Basic Auth for XML-RPC endpoints              |
| CLI      | `j2xml`      | `cli/j2xml.php`                            | Command-line exporter (run with `php cli/j2xml.php -f file.xml`) |

> **Note:** `libraries/eshiol/J2xmlpro/` and the related Pro manifests / language
> files are intentionally gitignored — this repository is the open-source
> **J2XML** (not J2XML Pro). Do not commit Pro-only code here.

---

## 2. Repository Layout

```
.
├── administrator/
│   ├── components/com_j2xml/   # Admin backend: controllers, models, views, sql, forms, script.php
│   ├── language/               # (gitignored locales live elsewhere)
│   └── manifests/              # Joomla install manifests per extension + pkg_j2xml.xml
├── components/com_j2xml/       # Site-side entry point + helpers/controllers
├── cli/j2xml.php               # Standalone CLI exporter
├── language/en-GB/             # Site language files (en-GB)
├── libraries/eshiol/
│   ├── J2xml/                  # Core library (Exporter, Importer, Sender, Table/*, Version)
│   └── phpxmlrpc/              # Vendored XML-RPC library
├── media/                      # Joomla media folders (com_j2xml, lib_eshiol_j2xml, lib_eshiol_phpxmlrpc)
├── plugins/system/
│   ├── j2xml/                  # System plugin (j2xml.php, layouts/{joomla,joomla4}, src/)
│   └── basicauth/              # Basic-auth system plugin
├── .github/                    # Issue templates, PR template, CI workflows (ci.yml)
├── AGENTS.md                   # THIS FILE — single source of truth for AI tools
├── CLAUDE.md                   # Pointer → AGENTS.md (Claude Code)
└── .windsurfrules              # Pointer → AGENTS.md (Windsurf)
```

### Key source files to know

- `libraries/eshiol/J2xml/Exporter.php` — turns Joomla rows (content, categories,
  users, menus, modules, contacts, weblinks, fields, tags, viewlevels,
  usernotes, images) into J2XML XML.
- `libraries/eshiol/J2xml/Importer.php` — parses J2XML XML and inserts/updates
  rows in the target Joomla instance.
- `libraries/eshiol/J2xml/Sender.php` — pushes content to remote Joomla sites
  via XML-RPC (uses `libraries/eshiol/phpxmlrpc`).
- `libraries/eshiol/J2xml/Table/*.php` — per-entity table wrappers used by
  Exporter/Importer (`Content`, `Category`, `User`, `Menu`, `Menutype`,
  `Module`, `Contact`, `Weblink`, `Field`, `Fieldgroup`, `Tag`, `Viewlevel`,
  `Usernote`, `Image`).
- `plugins/system/j2xml/j2xml.php` — system plugin entry; hooks Joomla events
  (`onContentPrepareData`, `onAfterRender`, etc.) and applies the compatibility
  shims under `plugins/system/j2xml/src/`.
- `plugins/system/j2xml/src/J2xml/Helper/Joomla.php` — `makeAlias()` helper that
  evals an aliased copy of a Joomla class so J2XML can override behaviour
  without forking core.
- `plugins/system/j2xml/layouts/{joomla,joomla4}/` — layout overrides split by
  Joomla major version.
- `administrator/components/com_j2xml/script.php` — package/component
  install/uninstall/update script.
- `administrator/components/com_j2xml/sql/` — install + update SQL for MySQL
  and PostgreSQL (and `updates/{mysql,postgresql}/` for incremental versions).

---

## 3. Coding Conventions

### PHP style

- **Namespacing:** the library uses `eshiol\J2xml` (lowercase `xml`) and
  `eshiol\J2xml\Table`, `eshiol\J2xml\Helper`. Plugin helpers use
  `eshiol\J2xml\Helper`. Match the existing casing exactly — PHP namespacing
  is case-insensitive at runtime but the codebase is consistent on this style.
- **Joomla framework access:** every entry file starts with
  `defined('_JEXEC') or die('Restricted access.');` (or `die()` in CLI).
  Preserve this guard in any new PHP file that is loaded by Joomla.
- **Class loading:** use the existing Joomla namespace registration and
  namespaced classes. Do **not** introduce Composer autoloading for runtime
  extension code; Composer is development-only in this repository.
- **Logging:** use `\Joomla\CMS\Log\Log::add(new \Joomla\CMS\Log\LogEntry(...))`
  with the `plg_system_j2xml` / `com_j2xml` category. The debug logger writes
  to `eshiol.log.php` by default (configurable via component/plugin params).
- **License header:** every PHP file begins with the standard
  `@package` / `@copyright` / `@license` GPL-3.0 block. Keep it on new files.
- **Version placeholders:** manifests and PHP headers use
  `__DEPLOY_VERSION__` and `__DEPLOY_DATE__` — these are replaced at release
  time. Do not hard-code version numbers in manifests.
- **Compatibility:** this fork targets **Joomla 5 and 6** with **PHP 8.4
  and 8.5**. Joomla 3.x/4.x and PHP < 8.4 are no longer supported. When a
  Joomla API differs between J5 and J6, prefer the shim pattern in
  `plugins/system/j2xml/src/` (e.g. `Joomla::makeAlias()`) or branch on
  `JVERSION`.
- **PHP 8.4/8.5 readiness:** avoid dynamic properties on classes (recent
  commits fixed this in `Importer`); avoid `utf8_encode()` (replaced in
  XML-RPC code); avoid deprecated `each()`, `create_function()`,
  `mb_strtolower()` on null, implicit nullable types, etc. Run the
  pre-commit hook (see §4) to catch these before commit.

### XML / manifests

- Indent with **tabs**, not spaces (matches existing manifests).
- Keep `method="upgrade"` on extensions so users can install over an existing
  copy without uninstalling.
- Keep the `<files folder="…">` mapping accurate — the `folder` attribute is
  the path **inside the build package**, not the repo path.

### SQL

- Provide **MySQL** and **PostgreSQL** variants for every schema change
  (`install.mysql.utf8.sql` + `install.postgresql.utf8.sql`, and a matching
  numbered file under `sql/updates/{mysql,postgresql}/`).
- Use `utf8` charset declarations and `#__` prefix for table names.

### Language files

- All user-facing strings go through `JText::_('…')` and have entries in
  `language/en-GB/en-GB.*.ini` (site) and
  `administrator/language/en-GB/en-GB.*.ini` (admin, when present).
- Do not commit `it-IT` or `sl-SI` translations — those folders are
  gitignored and maintained elsewhere.

---

## 4. Build & Release

There is **no build script** in this repository.
Releases are produced externally (eshiol.it tooling) which:

1. Substitutes `__DEPLOY_VERSION__` and `__DEPLOY_DATE__` in manifests/headers.
2. Zips each extension into `com_j2xml.zip`, `lib_eshiol_J2xml.zip`,
   `plg_system_j2xml.zip`, `lib_eshiol_phpxmlrpc.zip`,
   `plg_system_basicauth.zip`, and bundles them into `pkg_j2xml.zip`.

**CI** runs on every push and pull request via GitHub Actions
(`.github/workflows/ci.yml`) with three jobs:

- **php-quality** (PHP 8.4 + 8.5 matrix): Composer validate, PHP lint,
  PHPStan, PHPUnit, ShellCheck (warning+), XML validation with `xmllint`.
- **mysql-integration**: Docker Compose Joomla 5 + 6 with MySQL 8.0;
  runs `tests/scripts/run-all-tests.sh`.
- **postgresql-integration**: Docker Compose Joomla 5 + 6 with PostgreSQL 16;
  runs `tests/scripts/run-postgresql-smoke.sh`.

For local checks, use the **pre-commit hook** (below).

### Pre-commit hook

A git pre-commit hook is included at `scripts/git-hooks/pre-commit` and
installed via `scripts/install-hooks.sh`. It runs on every `git commit`:

1. **PHP lint** with PHP 8.4 and 8.5 (all available binaries).
2. **PHPStan** static analysis (level 0, `--memory-limit=512M`). When a
   committed `phpstan.neon` config exists, PHPStan runs against the
   config's `paths` (not just staged files) so that the baseline and
   `scanFiles` are applied correctly. PHPStan's result cache keeps
   re-runs fast.
3. **PHPUnit** if a `phpunit.xml` exists (placeholder — no test suite yet).

**Install (once after cloning):**

```bash
./scripts/install-hooks.sh
```

**Bypass temporarily:**

```bash
git commit --no-verify
```

### PHPStan configuration

PHPStan is configured via `phpstan.neon` at the repo root. Because J2XML
depends on the Joomla CMS framework (`Joomla\CMS\*`) which is not
installed via Composer, PHPStan cannot resolve those symbols on its own.
Instead of pulling in the full Joomla CMS as a dev dependency, we declare
the subset of Joomla classes, functions, and constants in a single scan
file (`stubs/joomla.php`) referenced via the `scanFiles` config key.

> **Key:** use `scanFiles` (not `stubFiles`) — `stubFiles` only override
> PHPDocs, while `scanFiles` declare symbols for discovery.
> See <https://phpstan.org/user-guide/discovering-symbols>

A baseline (`phpstan-baseline.neon`) suppresses known pre-existing issues
so the report focuses on new/changed code. To regenerate the baseline
after fixing existing issues:

```bash
phpstan analyse --generate-baseline=phpstan-baseline.neon
```

To run PHPStan manually:

```bash
phpstan analyse --no-progress --memory-limit=1G
```

The hook is version-controlled in `scripts/git-hooks/` and symlinked into
`.git/hooks/` by the install script. Edit the source file, not the symlink.

---

## 5. Testing

Integration tests run in **Docker** against official Joomla 5 and 6 images.
The primary suite uses MySQL; `tests/scripts/run-postgresql-smoke.sh` also
exercises installation, import, and export against PostgreSQL. The test suite
(`tests/scripts/run-all-tests.sh`) verifies the three import bugs fixed in this
fork:

- **Issue #72** — Import no longer returns HTTP 500 on Joomla 5.2+
- **Issue #71** — Articles import correctly from J3 XML format to J5
- **Issue #70** — Users import correctly on J5
- **Joomla 6 / PHP 8.4** — Import works on Joomla 6 with PHP 8.4

### Prerequisites

- Docker Desktop (or Docker Engine + Docker Compose)
- `curl` (pre-installed on macOS / most Linux distros)
- Composer for PHPUnit/PHPStan unit and static checks

### Running the tests

```bash
# Start Joomla 5 (port 8085) and Joomla 6 (port 8086) containers
cd tests/docker
docker compose up -d

# Wait for Joomla to finish installing, then run all tests
cd ../..
bash tests/scripts/run-all-tests.sh

# PostgreSQL smoke matrix
cd tests/docker
docker compose -f docker-compose.postgresql.yml up -d
cd ../..
bash tests/scripts/run-postgresql-smoke.sh

# Unit and static checks
composer install
vendor/bin/phpunit --configuration phpunit.xml.dist
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
```

The script will:

1. Wait for both Joomla instances to come up
2. Install the J2XML plugin into each via symlinks + DB registration
3. Log in to each admin panel and import the test XML fixtures
4. Verify the expected number of articles/users in the database
5. Print a summary of pass/fail results

### Test output

```
  Passed: 79
  Failed: 0
  Skipped: 0
  Total:  79
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

### Manual testing

When making changes beyond the automated tests:

- Manually install the package on a clean **Joomla 5 and 6** instance
  (PHP 8.4+).
- Exercise the affected path through the **com_j2xml admin UI**
  (export → import → send via the Joomla REST API) and through `cli/j2xml.php`.
- Check the Joomla debug log (`eshiol.log.php`) for new
  `JLogEntry` warnings.
- Verify both **MySQL** and **PostgreSQL** if you touched SQL.
- Run `./scripts/install-hooks.sh` to ensure the pre-commit hook is active.

If you add a feature, consider whether it needs a new SQL update file under
`sql/updates/{mysql,postgresql}/` with the next version number.

---

## 6. Git & PR Conventions

- **Commit messages:** short imperative summary line. Recent history uses
  plain summaries like `Joomla 5.0.0 PHP 8.2 Export` or
  `PHP 8.2: utf8_encode() is deprecated - XMLRPC`. Match that style; do not
  add `Generated with Devin` / `Co-Authored-By` trailers unless the user
  asks for them.
- **Branches:** feature branches are named like `fix-content-construct-j5`,
  `php82-utf8_encode-xmlrpc`, `haltonerror-xmlrpc-protocol-disabled`.
  Use a short kebab-case name describing the change.
- **PRs:** use the template at `.github/PULL_REQUEST_TEMPLATE.md`
  (Summary / Testing Instructions / Expected / Actual / Documentation
  Changes). Reference the issue number at the top.
- **Do not push** unless explicitly asked. **Do not** rewrite history or
  force-push.
- **Never** commit secrets, API keys, or the gitignored Pro-only files
  (`libraries/eshiol/J2xmlpro`, `administrator/manifests/libraries/j2xmlpro.xml`,
  `language/*/lib_j2xmlpro.*`).

---

## 7. Common Tasks

### Add a new exportable entity type

1. Add `libraries/eshiol/J2xml/Table/YourThing.php` extending
   `eshiol\J2xml\Table\Table`.
2. Register the import in `Importer.php` (parse + insert/update logic) and
   the export in `Exporter.php` (query + XML serialization).
3. Add the SQL install + update files for MySQL **and** PostgreSQL if a new
   table or column is needed.
4. Add language strings in `language/en-GB/` and admin language where relevant.
5. Update the relevant manifest `<files>` list and the package manifest in
   `administrator/manifests/packages/pkg_j2xml.xml` only if a new extension
   is introduced (rare).

### Fix a Joomla-version compatibility bug

1. Reproduce on the affected Joomla major (3 / 4 / 5).
2. Prefer fixing it inside `plugins/system/j2xml/src/` (shim) or by branching
   on `JVERSION` rather than forking Joomla core.
3. If `makeAlias()` is used, log the original/alias class names at DEBUG
   level as the existing code does.
4. Test on **Joomla 5 and 6** (PHP 8.4+) before opening the PR.

### Change the XML schema

- The XML format is the **J2XML format** — backwards compatibility matters
  because users import XML produced by older versions (and vice versa).
- Bump the version reported by `libraries/eshiol/J2xml/Version.php` and add
  any new element handling in both `Exporter` and `Importer`.
- Document the schema change in the PR description.

---

## 8. Things to Avoid

- Do **not** introduce Composer autoloading, npm, webpack, or a runtime build
  toolchain. Composer development dependencies for tests and static analysis
  are supported.
- Do **not** remove the `defined('_JEXEC') or die(...)` guards.
- Do **not** replace `JLoader::import` / `jimport` with PSR-4 autoloading
  unless doing a coordinated refactor across the whole library.
- Do **not** commit `it-IT` / `sl-SI` language folders or any
  `J2xmlpro` / `lib_j2xmlpro` artefacts.
- Do **not** hard-code version numbers or dates — use the
  `__DEPLOY_VERSION__` / `__DEPLOY_DATE__` placeholders.
- Do **not** add `Generated with Devin` / `Co-Authored-By` lines to commits
  unless the user asks.
- Do **not** add emojis to source files, commit messages, or docs.

---

## 9. Useful Commands (for AI agents)

```bash
# Inspect the package manifest (defines what ships)
cat administrator/manifests/packages/pkg_j2xml.xml

# Run the CLI exporter against a local Joomla install (requires a bootstrapped site)
php cli/j2xml.php -f /tmp/export.xml

# Find every place a Joomla entity type is handled
grep -rn "eshiol\\\\J2xml\\\\Table\\\\" libraries/eshiol/J2xml/

# List SQL update versions to pick the next number
ls administrator/components/com_j2xml/sql/updates/mysql/

# Install the pre-commit hook (once after cloning)
./scripts/install-hooks.sh

# Lint a single file with both PHP versions
/opt/homebrew/opt/php@8.4/bin/php -l libraries/eshiol/J2xml/Exporter.php
/opt/homebrew/opt/php@8.5/bin/php -l libraries/eshiol/J2xml/Exporter.php

# Run PHPStan on the whole codebase (uses phpstan.neon + stubs + baseline)
phpstan analyse --no-progress --memory-limit=1G

# Run the Docker-based integration test suite (Joomla 5 + 6, PHP 8.4)
cd tests/docker && docker compose up -d && cd ../..
bash tests/scripts/run-all-tests.sh
```

There is no `make`, npm, or runtime build command. Composer is used for
PHPUnit and PHPStan development tooling. The pre-commit hook handles linting +
static analysis automatically.
For integration testing, see §5 (Docker-based test suite).

---

## 10. Upstream Issues (eshiol/j2xml)

The original project at <https://github.com/eshiol/j2xml/issues> has **9 open
issues** (as of Aug 2026). The most relevant ones for the Joomla 5/6 + PHP 8.4
modernisation effort:

| #   | Title                                                        | Relevance |
|-----|--------------------------------------------------------------|-----------|
| 72  | Unable to use on Joomla! 5.2                                 | **High** — import fails with HTTP 500 on J5.2+; partially addressed by PHP 8.4 fixes (E_STRICT, utf8_encode) |
| 71  | Unable to import Articles on Joomla 5.1.0 (from J3.10.11)   | **High** — cross-version import broken; likely schema/API drift in J5 |
| 70  | Unable to import users                                       | **High** — user import broken on J5; likely the same root cause as #71 |
| 68  | Please create a CLI for import and export                    | Medium — feature request; `cli/j2xml.php` exists for export but not import |
| 56  | Error while importing articles from J3 into J4               | Medium — older J3→J4 import failure, may already be fixed but worth verifying |
| 53  | Registration on website not responding — where is latest version? | Low — meta/maintenance question |
| 40  | Import/export user groups                                    | Medium — feature gap; user groups not handled by current Table\User |
| 6   | HTTP Error 500 when exporting large amounts of data          | Medium — memory/timeout on bulk export; relevant for J5/6 robustness |
| 5   | Useless menu item                                            | Low — UX complaint |

**PHP 8.4/8.5 fixes applied (2026-07):**

- `E_STRICT` constant removed (fatal on PHP 8.4) — 2 files
- `utf8_encode()` replaced with `mb_convert_encoding()` (removed in PHP 8.3) — 5 files
- Non-canonical casts `(boolean)`/`(integer)`/`(double)` → `(bool)`/`(int)`/`(float)` — 4 files
- `case;` → `case:` syntax — 1 file
- phpxmlrpc vendored library updated from 4.10.1 → **4.11.5** (latest stable, Nov 2025)

**Joomla 5/6 compatibility note:** All `J*` legacy class aliases (`JFactory`,
`JLog`, `JText`, etc.) have been **migrated to fully-qualified namespaced
classes** (`\Joomla\CMS\Factory`, `\Joomla\CMS\Log\Log`,
`\Joomla\CMS\Language\Text`, etc.) throughout the J2XML codebase. This was
necessary because Joomla 6's `compat6` plugin does not register all aliases
early enough during component/plugin loading, causing "Class not found" fatal
errors. The migration covers:

- `JFactory` → `\Joomla\CMS\Factory`
- `JLog` / `JLogEntry` → `\Joomla\CMS\Log\Log` / `\Joomla\CMS\Log\LogEntry`
- `JText` → `\Joomla\CMS\Language\Text`
- `JComponentHelper` → `\Joomla\CMS\Component\ComponentHelper`
- `JPluginHelper` → `\Joomla\CMS\Plugin\PluginHelper`
- `JRoute` → `\Joomla\CMS\Router\Route`
- `JFile` / `JFolder` → `\Joomla\CMS\Filesystem\File` / `Folder`
- `JHtml` → `\Joomla\CMS\HTML\HTMLHelper`
- `JTable*` → `\Joomla\CMS\Table\*`
- `JController*` → `\Joomla\CMS\MVC\Controller\*`
- `JModel*` → `\Joomla\CMS\MVC\Model\*`
- `JViewLegacy` → `\Joomla\CMS\MVC\View\HtmlView`
- `JToolBarHelper` → `\Joomla\CMS\Toolbar\ToolbarHelper`
- `JSession` → `\Joomla\CMS\Session\Session`
- `JRegistry` → `\Joomla\Registry\Registry`
- `JVersion` → `\Joomla\CMS\Version`
- `JUri` → `\Joomla\CMS\Uri\Uri`
- `JClientHelper` → `\Joomla\CMS\Client\ClientHelper`
- `JFilterOutput` → `\Joomla\CMS\Filter\OutputFilter`
- `JUserHelper` → `\Joomla\CMS\User\UserHelper`
- `JArrayHelper` → `\Joomla\Utilities\ArrayHelper`
- `JDate` → `\Joomla\CMS\Date\Date`
- `JError::raiseWarning` → `$app->enqueueMessage(..., 'warning')`
- `JHelperTags` → `\Joomla\CMS\Helper\TagsHelper`
- `JLanguageAssociations` → `\Joomla\CMS\Language\Associations`
- `JLanguageMultilang` → `\Joomla\CMS\Language\Multilang`
- `JApplicationCli` → `\Joomla\CMS\Application\CliApplication`
- `JResponse` → `\Joomla\CMS\Application\WebApplication`
- `JFilesystemHelper` → `\Joomla\CMS\Filesystem\FilesystemHelper`
- `JInstallerHelper` → `\Joomla\CMS\Installer\InstallerHelper`

**CSRF token handling on PHP 8.4:** PHP 8.4's multipart form parser can
emit "File Upload Mime headers garbled" warnings that cause `$_POST` to be
incomplete (missing the CSRF token field). The test script sends the token
via the `X-CSRF-Token` HTTP header as a fallback, which Joomla's
`Session::checkToken()` checks before the POST field.

**Remaining priority:** #72, #71, #70 are now **fixed and tested** on both
Joomla 5 and Joomla 6 with PHP 8.4.

---

## 11. DeepWiki

DeepWiki (<https://deepwiki.com>) generates an AI-powered wiki from the GitHub
repo and exposes it via MCP tools, so AI agents can query the codebase's
architecture, dependencies, and entity relationships without re-reading every
file.

Two things are configured, both required per DeepWiki best practice:

### 11.1 README badge (indexing + auto-refresh)

`README.md` contains a DeepWiki badge:

```markdown
[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/gundestrup/j2xml)
```

DeepWiki **auto-refreshes the wiki** when it detects the badge in the repo's
README. Without the badge, the wiki may not be indexed or kept up to date.
The badge links to <https://deepwiki.com/gundestrup/j2xml>.

### 11.2 MCP server (agent queries)

`.devin/mcp_config.json` registers the DeepWiki MCP server so Devin CLI can
query the indexed wiki at runtime:

```json
{
  "mcpServers": {
    "deepwiki": {
      "url": "https://mcp.deepwiki.com/mcp",
      "transport": "http"
    }
  }
}
```

This exposes three tools to the agent: `ask_question`, `read_wiki_structure`,
and `read_wiki_contents`. No auth is required for public repos.

### 11.3 Badge vs MCP — why both

| | README badge | `.devin/mcp_config.json` |
| --- | --- | --- |
| **Purpose** | Index + auto-refresh the wiki | Let agents query the wiki |
| **Read by** | DeepWiki's crawler | Devin CLI / Claude Code / Cursor |
| **Without it** | Wiki goes stale or unindexed | Agents can't query the wiki |

They are complementary — keep both. If you fork to a new repo, update the
badge URL in `README.md` to point to the new `owner/repo`.

To verify the MCP server is connected:

```bash
devin mcp list
devin mcp get deepwiki
```

---

## 12. Pointer Files (how AI tools find this document)

The following files in the repo root are intentionally tiny and only redirect
here. **Keep them as pointers**; edit `AGENTS.md` for any content change.

| File               | Read by                                      | Contents                          |
|--------------------|----------------------------------------------|-----------------------------------|
| `CLAUDE.md`        | Claude Code                                  | `See AGENTS.md.`                  |
| `.windsurfrules`   | Windsurf                                     | `See AGENTS.md.`                  |
| `.devin/global_rules.md` | Devin CLI (`.devin/` rules)            | `See AGENTS.md.`                  |

If you add support for another AI tool, add a one-line pointer file here and
keep the actual guidance in `AGENTS.md`.
