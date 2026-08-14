# Known Deprecated Patterns — J2XML 4.0.0

This file tracks deprecated Joomla/PHP patterns still present in the J2XML
codebase.  They are scheduled for removal in a future release.  Each entry
lists the file(s), the deprecated call, the recommended replacement, and the
Joomla version in which the API is scheduled for removal.

---

## 1. `HTMLHelper::_('bootstrap.renderModal')`

| | |
|---|---|
| **Deprecated in** | Joomla 5.1 |
| **Removed in** | Joomla 6.0 (scheduled) |
| **Priority** | Medium |

**Files**
- `plugins/system/j2xml/layouts/joomla/toolbar/modal.php:68`
- `administrator/components/com_j2xml/views/import/tmpl/default.php:126`

**Current code**
```php
echo HTMLHelper::_('bootstrap.renderModal', $selector . 'Modal', [...]);
```

**Recommended replacement**
Use the JoomlaDialog web-component API (`<joomla-dialog>`) introduced in
Joomla 5.1.  See:
https://manual.joomla.org/docs/general-concepts/dialogs/

**Why deferred**
The modal UI is not covered by the automated test suite.  Replacing
`bootstrap.renderModal` requires HTML/JS refactoring and manual visual
verification.  The deprecated API still works in Joomla 5 and 6.

---

## 2. `Table::getInstance()`

| | |
|---|---|
| **Deprecated in** | Joomla 5.0 |
| **Removed in** | Joomla 6.0 (scheduled) |
| **Priority** | Low |

**File**
- `libraries/eshiol/J2xml/Table/Table.php:601`

**Current code**
```php
$u = \Joomla\CMS\Table\Table::getInstance('Usergroup');
```

**Recommended replacement**
```php
$u = new \Joomla\CMS\Table\Usergroup($db);
```

**Why deferred**
Single occurrence in a static method; needs a `$db` instance which is
already available via the container in that method.

---

## 3. `JLoader::registerNamespace()` (manual PSR-4 registration)

| | |
|---|---|
| **Deprecated in** | Not deprecated, but redundant when library manifest declares `<namespace>` |
| **Priority** | Low |

**Files (6 occurrences)**
- `plugins/system/j2xml/j2xml.php:25`
- `libraries/eshiol/J2xml/classmap.php:23`
- `cli/j2xml.php:58`
- `components/com_j2xml/j2xml.php:24`
- `administrator/components/com_j2xml/src/Dispatcher/Dispatcher.php:77`
- `tests/scripts/test-import-articles.php:13`

**Current code**
```php
\JLoader::registerNamespace('eshiol\\J2xml', JPATH_LIBRARIES . '/eshiol/J2xml');
```

**Recommended replacement**
The library manifest (`administrator/manifests/libraries/eshiol/j2xml.xml`)
now declares `<namespace path="eshiol/J2xml">eshiol\J2xml</namespace>`, so
Joomla's autoloader handles PSR-4 registration automatically.  The manual
`registerNamespace()` calls can be removed once verified in a real
installation.

---

## 4. `Factory::getApplication()->triggerEvent()`

| | |
|---|---|
| **Deprecated in** | Joomla 5.0 (soft deprecation) |
| **Removed in** | Joomla 6.0+ (event dispatcher pattern recommended) |
| **Priority** | Low |

**Files (16 occurrences)**
- `libraries/eshiol/J2xml/Importer.php:226`
- `libraries/eshiol/J2xml/Exporter.php` (lines 210, 255, 300, 344, 388, 432, and more)

**Current code**
```php
$results = \Joomla\CMS\Factory::getApplication()->triggerEvent('onContentAfterImport', [...]);
```

**Recommended replacement**
Use the Joomla 5+ event dispatcher pattern with concrete event classes:
```php
use Joomla\Event\Event;
$event = new Event('onContentAfterImport', [...]);
Factory::getContainer()->get(\Joomla\Event\DispatcherInterface::class)->dispatch($event);
```

**Why deferred**
Custom events (`onContentAfterImport`, `onJ2xmlAfterExport`, etc.) would
need proper event classes.  `triggerEvent()` still works in Joomla 5/6.

---

## 5. `$table->getError()` / `$user->getError()`

| | |
|---|---|
| **Deprecated in** | Joomla 4.0 (inherited from JObject) |
| **Removed in** | Joomla 7.0 (scheduled) |
| **Priority** | Low |

**Files (10+ occurrences)**
- `libraries/eshiol/J2xml/Table/Category.php:294`
- `libraries/eshiol/J2xml/Table/Menutype.php:164`
- `libraries/eshiol/J2xml/Table/Tag.php:130`
- `libraries/eshiol/J2xml/Table/User.php:265, 348`
- `libraries/eshiol/J2xml/Table/Content.php:444, 449`
- `libraries/eshiol/J2xml/Table/Field.php:174`
- `libraries/eshiol/J2xml/Table/Module.php` (import method)
- `libraries/eshiol/J2xml/Table/Viewlevel.php:194`

**Current code**
```php
$table->getError()
```

**Recommended replacement**
Joomla Table classes throw exceptions on error in Joomla 5+.  Wrap
`store()` / `bind()` in try/catch and use `$e->getMessage()`:
```php
try {
    $table->store();
} catch (\Exception $e) {
    Log::add(... $e->getMessage() ...);
}
```

**Why deferred**
Pervasive pattern; requires careful audit of each call site to ensure
exception-based error handling doesn't break the import flow.

---

## 6. Underscore-prefixed properties (`$_option`, `$_user`, etc.)

| | |
|---|---|
| **Deprecated in** | Not deprecated (PSR-1 convention violation) |
| **Priority** | Low (code style) |

**Files**
- `libraries/eshiol/J2xml/Exporter.php` — `$_image_path`, `$_admin`, `$_option`
- `libraries/eshiol/J2xml/Importer.php` — `$_nullDate`, `$_user`, `$_user_id`, `$_now`, `$_option`, `$_usergroups`
- `libraries/eshiol/J2xml/Table/Table.php` — `$_excluded`, `$_aliases`, `$_jsonEncode`
- `administrator/components/com_j2xml/src/Model/ImportModel.php` — `$_context` (already renamed to `$context`)

**Recommended replacement**
Rename to camelCase without leading underscore:
```php
private $imagePath = 'images';
protected $excluded = [];
```

**Why deferred**
100+ usages of `$this->_` throughout the Table classes.  Mechanical
rename but large diff; should be done in a dedicated refactor commit.

---

## 7. Missing native type declarations on properties and methods

| | |
|---|---|
| **Deprecated in** | Not deprecated (modernization opportunity) |
| **Priority** | Low (code quality) |

**Files**
- All View classes in `administrator/components/com_j2xml/src/View/` — missing return types on `display()`, `__construct()`
- All Controller classes in `administrator/components/com_j2xml/src/Controller/` — missing return types on `execute()`, `getModel()`, `display()`
- Property declarations using `@var` annotations instead of native types in Table classes

**Recommended replacement**
Add PHP 8.0+ native types:
```php
public function display(?string $tpl = null): void
protected array $excluded = [];
```

**Why deferred**
Adding return types to methods that override Joomla base classes requires
matching the parent signature exactly; must be verified against Joomla 5/6
base class signatures.

---

## 8. `script.php` — class-based installer instead of `InstallerScriptInterface`

| | |
|---|---|
| **Deprecated in** | Joomla 5.0 (soft deprecation) |
| **Removed in** | Not scheduled for removal |
| **Priority** | Low |

**File**
- `administrator/components/com_j2xml/script.php`

**Current code**
```php
class Com_J2xmlInstallerScript
{
    public function install($parent) { }
    public function uninstall($parent) { }
    // ...
}
```

**Recommended replacement**
```php
return new class () implements InstallerScriptInterface {
    public function install(InstallerAdapter $adapter): bool { }
    public function uninstall(InstallerAdapter $adapter): bool { }
    // ...
};
```

**Why deferred**
The class-based pattern still works in Joomla 5/6.  Migrating to the
anonymous-class pattern changes the install/uninstall flow and requires
thorough testing on a real installation.

---

## 9. `Factory::getDbo()` in test scripts

| | |
|---|---|
| **Deprecated in** | Joomla 5.0 |
| **Priority** | Low (test code only) |

**Files**
- `tests/scripts/bootstrap.php:63`
- `tests/scripts/test-import-articles.php:20`

**Recommended replacement**
```php
Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class)
```

**Why deferred**
Test scripts only; not shipped in the package.

---

## 10. `Factory::getUser($user_id)` in commented-out code

| | |
|---|---|
| **Deprecated in** | Joomla 5.0 |
| **Priority** | None (dead code) |

**File**
- `libraries/eshiol/J2xml/Table/Usernote.php:139` (inside `/* ... */` block)

No action needed — the code is commented out.

---

## Summary

| # | Pattern | Occurrences | Priority | Target version |
|---|---------|-------------|----------|----------------|
| 1 | `bootstrap.renderModal` | 2 | Medium | 4.1 |
| 2 | `Table::getInstance()` | 1 | Low | 4.1 |
| 3 | `JLoader::registerNamespace()` | 6 | Low | 4.1 |
| 4 | `triggerEvent()` | 16 | Low | 5.0 |
| 5 | `->getError()` | 10+ | Low | 5.0 |
| 6 | Underscore-prefixed properties | 100+ | Low | 5.0 |
| 7 | Missing native types | 20+ | Low | 5.0 |
| 8 | `script.php` class-based installer | 1 | Low | 5.0 |
| 9 | `Factory::getDbo()` in tests | 2 | Low | 4.1 |
| 10 | `Factory::getUser()` in dead code | 1 | None | — |
