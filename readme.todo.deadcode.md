# Dead-code audit and cleanup plan

**Status:** Implemented after review

**Date:** 2026-09-13

**Scope:** Joomla 5/6 and PHP 8.4/8.5 code paths in the current J2XML repository.

The cleanup described in this document was approved and implemented. The audit remains as a record of the findings, compatibility decisions, and verification plan.

## Summary

The current extension uses Joomla's namespaced MVC, service-provider, Webservices API, token authentication, and Web Asset APIs. Several files still belong to the removed legacy controller/XML-RPC architecture.

The findings fall into three groups:

1. **Confirmed orphaned/dead files** — no current runtime callers, no current package role, or clearly broken legacy references.
2. **Legacy public library API** — unused inside this repository but potentially callable by third-party extensions, so removal needs an explicit compatibility decision.
3. **Safe internal cleanup** — dead assignments and commented-out code that can be removed without changing behavior.

## 1. Confirmed orphaned or obsolete files

### 1.1 Legacy administrator helpers

Files:

- `administrator/components/com_j2xml/helpers/content.php`
- `administrator/components/com_j2xml/helpers/j2xml.php`

Evidence:

- There are no repository callers for `J2xmlHelper`.
- The current administrator component manifest does not include the `helpers` directory. The administrator files are assembled from `forms`, `layouts`, `sql`, `views`, `services`, and `src` in `administrator/components/com_j2xml/j2xml.xml`.
- `helpers/content.php` only requires Joomla's old `com_content/helpers/content.php`, which is legacy code and is not part of the current Joomla 5/6 architecture.
- `helpers/j2xml.php` defines `getActions()`, `updateReset()`, `copyright()`, `addSubmenu()`, and `stripInvalidXml()`, but none has a current production caller.
- The current `DefaultHtmlView` uses Joomla's built-in `Joomla\\CMS\\Helper\\ContentHelper::getActions('com_j2xml')` instead:
  - `administrator/components/com_j2xml/src/View/DefaultHtmlView.php:18-23`
  - `administrator/components/com_j2xml/src/View/DefaultHtmlView.php:62-72`

Recommendation:

- Delete both helper files.
- No manifest change should be necessary because they are already excluded from the current component package.

### 1.2 Empty legacy classmap

File:

- `libraries/eshiol/J2xml/classmap.php`

Evidence:

- The file contains only the PHP header and `_JEXEC` guard; it does not register classes or define executable behavior.
- Joomla's library manifest now registers the `eshiol\\J2xml` namespace through PSR-4:
  - `administrator/manifests/libraries/eshiol/j2xml.xml:5`
- The file is retained only by stale package references:
  - `administrator/manifests/libraries/eshiol/j2xml.xml:20`
  - `scripts/build-package.sh:119`

Recommendation:

- Delete `libraries/eshiol/J2xml/classmap.php`.
- Remove its `<filename>` entry from the library manifest.
- Remove its `cp` line from `scripts/build-package.sh`.

### 1.3 Broken legacy site entry point

File:

- `components/com_j2xml/j2xml.php`

Evidence:

- The file implements the old controller-dispatch model and searches for:
  - `components/com_j2xml/controller.php`
  - `components/com_j2xml/controllers/*.php`
- Those controller files no longer exist. The current component uses the namespaced Joomla service provider and MVC dispatcher:
  - `administrator/components/com_j2xml/services/provider.php:44-57`
  - `administrator/components/com_j2xml/src/Extension/J2xmlComponent.php`
- The old site entry point would throw `Invalid Controller` for normal requests because its expected controller files are absent:
  - `components/com_j2xml/j2xml.php:65-112`
- It is still copied into packages by:
  - `administrator/components/com_j2xml/j2xml.xml:34-36`
  - `scripts/build-package.sh:72-75`
- The current test at `tests/scripts/run-all-tests.sh:835-842` only checks that curl does not return `000`; it does not confirm successful Joomla component dispatch.

Recommendation:

- Remove the site `<files folder="site">` section from the component manifest.
- Remove the site-entry copy commands from `scripts/build-package.sh`.
- Delete `components/com_j2xml/j2xml.php`.
- Remove or replace the weak site-entry test. The active functionality is the administrator UI and the Webservices API, not the obsolete site controller entry point.

Review question:

- Confirm that no external installation still depends on the old frontend component URL before deleting this file. The current repository contains no working frontend controllers for it.

### 1.4 Backup manifest

File:

- `plugins/webservices/j2xml/j2xml.xml.bak`

Evidence:

- It is a tracked backup artifact with no runtime or packaging references.

Recommendation:

- Delete it.

### 1.5 Obsolete standalone import test

File:

- `tests/scripts/test-import-articles.php`

Evidence:

- It is not called by the current integration test harness.
- It uses hard-coded absolute fixture paths such as `/fixtures/articles-j3.xml` and `/fixtures/categories-j3.xml`:
  - `tests/scripts/test-import-articles.php:22-40`
- The active Docker integration suite already tests Joomla 5 and Joomla 6 imports using the repository fixtures and real HTTP execution.

Recommendation:

- Delete this standalone script, or rewrite it to use the current Docker/harness conventions if a standalone diagnostic is still desired.

## 2. Legacy public library API requiring a compatibility decision

### 2.1 `Sender.php`

File:

- `libraries/eshiol/J2xml/Sender.php`

Evidence:

- No repository code instantiates `Sender` or calls `Sender::send()`.
- The active send flow is browser-side JavaScript:
  - `media/lib_eshiol_j2xml/js/j2xml.js:287-301`
- The current flow exports JSON from the Joomla administrator endpoint and sends it to the remote Joomla Webservices API using `X-Joomla-Token`.
- `Sender.php` remains included in the library manifest and package builder:
  - `administrator/manifests/libraries/eshiol/j2xml.xml:17-18`
  - `scripts/build-package.sh:117`

Decision:

- `Sender.php` was removed as part of the approved breaking cleanup.
- Its manifest and build references were removed.
- The supported transfer path is the Joomla Webservices REST API.
- Optional integrations such as Attachments for J2XML must use an updated
  connector and the preserved J2XML event hooks.

### 2.2 `Messages.php`

File:

- `libraries/eshiol/J2xml/Messages.php`

Evidence:

- Runtime use is limited to `Sender.php`.
- The remaining repository consumer is `tests/unit/MessagesTest.php`.
- Its mapping contains XML-RPC-era message entries, including `LIB_J2XML_MSG_XMLRPC_NOT_SUPPORTED` and `LIB_J2XML_MSG_XMLRPC_DISABLED`.

Decision:

- `Messages.php` was removed together with `Sender.php`.
- Its manifest/build references and `tests/unit/MessagesTest.php` were removed
  together.

## 3. Safe internal cleanup candidates

### 3.1 Unused event-result assignments

The events themselves must remain because plugins may rely on them, but the return values are not consumed.

Candidates:

- `libraries/eshiol/J2xml/Exporter.php`
  - Nine `$results = $this->app->triggerEvent('onJ2xmlAfterExport', ...)` assignments.
  - Example: lines `193-202`.
- `libraries/eshiol/J2xml/Table/Content.php`
  - `$results = Factory::getApplication()->triggerEvent('onJ2xmlBeforeExportContent', ...)`.
  - Example: lines `563-570`.
- `libraries/eshiol/J2xml/Importer.php`
  - `$results = $this->app->triggerEvent('onContentAfterImport', ...)`.
  - Example: lines `223-234`.

Safe replacement:

```php
$this->app->triggerEvent('event.name', $arguments);
```

This preserves event dispatch and removes only the unused local variable.

Do not remove the events themselves.

### 3.2 Commented-out legacy code

Candidates for later cleanup:

- `libraries/eshiol/J2xml/Exporter.php:102` — commented `Version::$DOCTYPE` append.
- `libraries/eshiol/J2xml/Table/Category.php:238` — commented `onContentBeforeSave` event.
- `plugins/system/j2xml/j2xml.php:249-251` — commented plugin import and `onLoadJS` trigger.

These comments are not executable and can be removed after confirming they are not historical documentation that the maintainer wants to retain.

## 4. Code that is active and should not be removed

The following items initially look unused because they are invoked dynamically or by Joomla's lifecycle:

### Export methods

`Exporter` methods such as `content()`, `categories()`, `users()`, `menus()`, `modules()`, `viewlevels()`, and `usernotes()` are dynamically dispatched:

- `administrator/components/com_j2xml/src/Controller/ExportController.php:95-103`

The controller calls `$j2xml->$exportMethod(...)`, so ordinary text search for `Exporter::content()` is insufficient.

### Joomla plugin events

`plgSystemJ2xml::onAfterDispatch()` is invoked by Joomla's system-plugin event lifecycle and must not be removed because no direct PHP caller exists:

- `plugins/system/j2xml/j2xml.php:99-177`

### Installer lifecycle methods

Methods such as `install()`, `uninstall()`, `preflight()`, `postflight()`, and `update()` in `script.php` are invoked dynamically by Joomla's extension installer. They should not be classified as dead based only on repository call searches.

### Send UI classes

The following remain active even though `Sender.php` is unused:

- `administrator/components/com_j2xml/src/Model/SendModel.php`
- `administrator/components/com_j2xml/src/View/Send/HtmlView.php`
- `administrator/components/com_j2xml/views/send/tmpl/default.php`
- `media/lib_eshiol_j2xml/js/j2xml.js`

They render the send modal and initiate the current browser-to-Webservices-API flow.

### Table resolver methods

The following are actively used by import paths and must remain:

- `Table::getArticleId()`
- `Table::getUserId()`
- `Table::getUsergroupId()`
- `Table::getAccessId()`
- `Table::getCategoryId()`
- `Table::getTagId()`
- `Table::getMenuId()`
- `Table::getContactId()`
- `Table::getWeblinkId()`

They resolve XML names and paths to Joomla IDs and are covered by the name-reference integration fixture.

## 5. Documentation drift found during the audit (resolved)

The audit found stale XML-RPC and package-content references. These current-documentation issues were resolved:

- `AGENTS.md` and `README.md` now describe Joomla Webservices REST transfer and token authentication.
- Removed `eshiol/phpxmlrpc`, Basic Auth, `Sender`, and `Messages` from current package descriptions.
- Documented `cli/j2xml.php` as an importer.
- Historical XML-RPC entries remain in `CHANGELOG.md` as historical record.
- Documented Attachments for J2XML as an optional external integration requiring
  its own Joomla 5/6-compatible connector.

## 6. Implementation and verification sequence

The cleanup sequence was completed:

1. Removed the two administrator helper files.
2. Removed `classmap.php` and its manifest/build references.
3. Removed the broken frontend entry point and its manifest/build references.
4. Removed the Webservices manifest backup.
5. Removed the obsolete standalone import diagnostic test.
6. Removed unused event-result assignments while retaining event dispatch.
7. Removed `Sender.php`, `Messages.php`, and their unit/package references.
8. Updated README, AGENTS, CHANGELOG, and this audit document.

Remaining verification:

- PHP lint for all changed PHP files.
- PHPUnit, PHPStan, Semgrep, ShellCheck, and XML validation.
- MySQL Joomla 5/6 integration tests.
- PostgreSQL smoke tests.
- Package build and manifest inspection.
- Generated package inspection to ensure no orphaned legacy files remain.

## 7. Coverage analysis

The integration coverage report currently contains:

- **2,723 covered executable lines**
- **3,714 executable lines in the report**
- **73.32% line coverage**
- **64 active J2XML files**

Removing obsolete files does not automatically increase the percentage. Those
files are removed from both the numerator and denominator. The percentage now
represents coverage of the remaining active code. In addition, the merger only
emits files observed in PCOV dumps; active files never loaded by integration
are not added as zero-coverage files. The integration percentage is therefore
an observed-line metric and can overstate whole-repository coverage.

The largest uncovered areas are:

| File | Covered | Total | Uncovered | Main reason |
|---|---:|---:|---:|---|
| `libraries/eshiol/J2xml/Table/Table.php` | 332 | 483 | 151 | Resolver fallbacks, association handling, database branches, and error paths |
| `libraries/eshiol/J2xml/Table/Content.php` | 328 | 458 | 130 | Overwrite combinations, ratings/front-page preservation, ID remapping, workflow failures |
| `libraries/eshiol/J2xml/Exporter.php` | 141 | 213 | 72 | Compression/header branches, optional related exports, empty/error cases |
| `administrator/components/com_j2xml/src/Model/ImportModel.php` | 119 | 193 | 74 | Upload, URL/folder input, validation, option combinations, and failure paths |
| `cli/j2xml.php` | 80 | 118 | 38 | CLI error/option paths and content workflow compatibility |
| `administrator/components/com_j2xml/script.php` | 10 | 49 | 39 | Installer/update/uninstall lifecycle branches |
| `libraries/eshiol/J2xml/Version.php` | 2 | 19 | 17 | Unit tests cover these methods, but the integration report is HTTP/CLI-only |

The Webservices API controller is also included in the current report at
65/86 covered lines; the coverage merger now explicitly includes
`api/components/com_j2xml/`.

These files account for most of the 991 uncovered lines. The normal
integration suite intentionally focuses on production import/export behavior,
so it does not execute every defensive branch, installer lifecycle, malformed
upload source, database-driver alternative, or CLI failure path.

Codecov can show a different combined percentage because CI uploads separate
reports using the `unittests` and `integration` flags. The integration report
contains only the files and executable lines observed by PCOV in Joomla
containers. PHPUnit coverage can add coverage for pure library methods, while a
missing or delayed upload can make the dashboard temporarily appear lower.

The highest-value future coverage work would be:

1. Add focused unit tests for `Table.php` resolver fallback/error branches.
2. Exercise all `ImportModel` upload, URL, folder, validation, and option paths.
3. Add exporter tests for compression, empty selections, and optional child data.
4. Add installer lifecycle tests for update, uninstall, and legacy-file cleanup.
5. Resolve the Joomla CLI CMS application/workflow compatibility issue so CLI
   content import can be covered rather than using the category-only fixture.

## 8. Implementation status

The approved cleanup has been implemented:

- Removed the legacy administrator helper files.
- Removed the empty `classmap.php` and its manifest/build references.
- Removed the broken frontend site entry point and its manifest/build references.
- Removed `Sender.php` and `Messages.php` and their unit test/package references.
- Removed the obsolete Webservices manifest backup.
- Removed the obsolete standalone import diagnostic test.
- Removed XML-RPC-only language strings and send-template references.
- Removed unused event-result assignments while preserving all event dispatches.
- Preserved the J2XML export/import event hooks required by separately maintained integrations such as an updated Attachments connector.
- Updated README, AGENTS, and CHANGELOG documentation for the REST-based architecture.

The following existing integration-coverage changes remain in the working tree:

- `cli/j2xml.php`
- `libraries/eshiol/J2xml/Importer.php`
- `libraries/eshiol/J2xml/Table/Viewlevel.php`
- `scripts/check-tests.sh`
- `tests/scripts/run-all-tests.sh`
- `tests/fixtures/cli-import.xml`
- `tests/fixtures/malformed.xml`
- `tests/fixtures/name-refs.xml`
- `tests/fixtures/unsupported-version.xml`

Verification is pending after the cleanup changes.
