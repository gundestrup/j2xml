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

## [4.0.0] - 2026-08

**Major release: Joomla 3 support removed; Joomla 5/6 + PHP 8.4+ only.**

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

### Added
- **Docker-based integration test suite** — `tests/docker/` with Joomla 5 + 6 containers (PHP 8.4, MySQL 8.0); `tests/scripts/run-all-tests.sh` verifies issues #72, #71, #70 and Joomla 6 compatibility
- **Test fixtures** — `tests/fixtures/articles-j3.xml`, `users-j3.xml`, `categories-j3.xml` with J3-era XML format (version 21.12.0)
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
- **Library manifest** — added `<namespace path="eshiol/J2xml">eshiol\J2xml</namespace>` for automatic PSR-4 autoloading
- **Copyright years** updated from 2010-2023 to 2010-2026 across all PHP, XML, INI, JS, and CSS files
- **phpxmlrpc vendored library upgraded from 4.10.1 to 4.11.5** (latest stable, Nov 2025) — replaced all files in `libraries/eshiol/phpxmlrpc/src/` and `lib/`; preserved J2XML-specific `Log/Logger/XmlrpcLogger.php`; re-applied `utf8_encode()` → `mb_convert_encoding()` patches on top of 4.11.5

### Note
- Verified clean lint on PHP 8.4.24 and PHP 8.5.9 (0 errors, 0 deprecations)
- PHPStan passes with 0 errors
- All 26 integration tests pass on Joomla 5.4.7 and Joomla 6.1.2
- See `README.TODO.deprecated.md` for remaining deprecated patterns scheduled for future removal

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
