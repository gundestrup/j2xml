# Known Deprecated Patterns — J2XML 4.0.0

This file tracks deprecated Joomla/PHP patterns still present in the J2XML
codebase.  They are scheduled for removal in a future release.  Each entry
lists the file(s), the deprecated call, the recommended replacement, and the
Joomla version in which the API is scheduled for removal.

---

## 1. `HTMLHelper::_('bootstrap.renderModal')`

|| | |
|---|---|---|
| **Deprecated in** | Joomla 5.1 |
| **Removed in** | Joomla 6.0 (scheduled) |
| **Priority** | Medium |

**Files**
- `plugins/system/j2xml/layouts/joomla/toolbar/modal.php:67`
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

|| | |
|---|---|---|
| **Deprecated in** | Joomla 5.0 |
| **Removed in** | Joomla 6.0 (scheduled) |
| **Priority** | Low |

**File**
- `libraries/eshiol/J2xml/Table/Table.php:602`

**Current code**
```php
$u = \Joomla\CMS\Table\Table::getInstance('Usergroup');
```

**Recommended replacement**
```php
$u = new \Joomla\CMS\Table\Usergroup($db);
```

**Why deferred**
Single active occurrence in a static method; needs a `$db` instance which is
already available via the container in that method.  Other occurrences in
`Category.php:173` and `Viewlevel.php:194` are already commented out and
show the replacement.

---

## 3. `JLoader::registerNamespace()` (manual PSR-4 registration)

|| | |
|---|---|---|
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

## 4. `triggerEvent()` (soft-deprecated event dispatch)

|| | |
|---|---|---|
| **Deprecated in** | Joomla 5.0 (soft deprecation) |
| **Removed in** | Joomla 6.0+ (event dispatcher pattern recommended) |
| **Priority** | Low |

**Files (18 active occurrences)**
- `libraries/eshiol/J2xml/Exporter.php` — 10 calls via `$this->app->triggerEvent('onJ2xmlAfterExport', [...])` (lines 226, 271, 316, 360, 404, 448, 492, 538, 584, 629)
- `libraries/eshiol/J2xml/Importer.php:251` — `$this->app->triggerEvent('onContentAfterImport', [...])`
- `libraries/eshiol/J2xml/Table/Content.php:612` — `Factory::getApplication()->triggerEvent('onJ2xmlBeforeExportContent', [...])`
- `administrator/components/com_j2xml/src/Model/ImportModel.php` — 3 calls via `Factory::getApplication()->triggerEvent(...)` (lines 198, 237, 245)
- `api/components/com_j2xml/src/Controller/ImportController.php:170` — `$app->triggerEvent('onContentBeforeImport', [...])`
- `administrator/components/com_j2xml/views/import/tmpl/default.php:106` — `Factory::getApplication()->triggerEvent('onLoadJS')`
- `cli/j2xml.php:160` — `$this->triggerEvent('onBeforeImport', [...])`

**Current code**
```php
// In Exporter/Importer (dependency-injected):
$results = $this->app->triggerEvent('onJ2xmlAfterExport', [...]);

// In Model/Controller/template (direct Factory call):
$results = \Joomla\CMS\Factory::getApplication()->triggerEvent('onContentBeforeImport', [...]);
```

**Recommended replacement**
Use the Joomla 5+ event dispatcher pattern with concrete event classes:
```php
use Joomla\Event\Event;
$event = new Event('onContentAfterImport', [...]);
Factory::getContainer()->get(\Joomla\Event\DispatcherInterface::class)->dispatch($event);
```

**Why deferred**
The Exporter and Importer already receive a `CMSApplicationInterface` via
constructor injection and call `triggerEvent()` on it, which is an
improvement over the old `Factory::getApplication()` calls.  However,
`triggerEvent()` itself is still soft-deprecated on the application interface.
Custom events (`onContentAfterImport`, `onJ2xmlAfterExport`, etc.) would
need proper event classes.  `triggerEvent()` still works in Joomla 5/6.

---

## 5. `$table->getError()` / `$user->getError()`

|| | |
|---|---|---|
| **Deprecated in** | Joomla 4.0 (inherited from JObject) |
| **Removed in** | Joomla 7.0 (scheduled) |
| **Priority** | Low |

**Files (15 occurrences across 13 files)**
- `libraries/eshiol/J2xml/Table/Category.php:294`
- `libraries/eshiol/J2xml/Table/Contact.php:276`
- `libraries/eshiol/J2xml/Table/Content.php:444, 449`
- `libraries/eshiol/J2xml/Table/Field.php:174`
- `libraries/eshiol/J2xml/Table/Fieldgroup.php:112`
- `libraries/eshiol/J2xml/Table/Menu.php:277`
- `libraries/eshiol/J2xml/Table/Menutype.php:164`
- `libraries/eshiol/J2xml/Table/Module.php:176`
- `libraries/eshiol/J2xml/Table/Tag.php:130`
- `libraries/eshiol/J2xml/Table/User.php:277, 360`
- `libraries/eshiol/J2xml/Table/Usernote.php:169`
- `libraries/eshiol/J2xml/Table/Viewlevel.php:214`
- `libraries/eshiol/J2xml/Table/Weblink.php:249`

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

|| | |
|---|---|---|
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

|| | |
|---|---|---|
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

|| | |
|---|---|---|
| **Deprecated in** | Joomla 5.0 (soft deprecation) |
| **Removed in** | Not scheduled for removal |
| **Priority** | Low |

**File**
- `administrator/components/com_j2xml/script.php:25`

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

## 9. `Factory::getDbo()` in test scripts (mostly resolved)

|| | |
|---|---|---|
| **Deprecated in** | Joomla 5.0 |
| **Priority** | Low (test code only) |
| **Status** | Mostly resolved |

**Remaining file**
- `tests/scripts/test-export-import-roundtrip.sh:50` — inline PHP heredoc uses `JFactory::getDbo()`

**Already migrated**
- `tests/scripts/bootstrap.php:63` — now uses `Factory::getContainer()->get(DatabaseInterface::class)`
- `tests/scripts/test-import-articles.php:20` — now uses `Factory::getContainer()->get(DatabaseInterface::class)`

**Recommended replacement**
```php
Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class)
```

**Why deferred**
Test scripts only; not shipped in the package.  The remaining occurrence
is inside an inline PHP heredoc in a shell script.

---

## 10. `$app->input` property access (deprecated)

|| | |
|---|---|---|
| **Deprecated in** | Joomla 5.0 (soft deprecation) |
| **Removed in** | Joomla 6.0+ (`getInput()` recommended) |
| **Priority** | Medium |

**Files (14 occurrences across 7 files)**
- `libraries/eshiol/J2xml/Exporter.php:79` — `$app->input->getCmd('option')`
- `libraries/eshiol/J2xml/Importer.php:96` — `$app->input->getCmd('option')`
- `administrator/components/com_j2xml/src/Model/ImportModel.php` — 4 calls (lines 99, 291, 370, 411)
- `administrator/components/com_j2xml/src/Controller/ExportController.php` — 2 calls (lines 57, 91)
- `administrator/components/com_j2xml/src/Controller/ImportController.php` — 2 calls (lines 52, 87)
- `administrator/components/com_j2xml/src/View/AbstractRawView.php:66`
- `api/components/com_j2xml/src/Controller/ImportController.php` — 3 calls (lines 95, 105, 139)

**Current code**
```php
$this->_option = (PHP_SAPI != 'cli') ? $app->input->getCmd('option') : 'cli_' . ...;
```

**Recommended replacement**
```php
$this->_option = (PHP_SAPI != 'cli') ? $app->getInput()->getCmd('option') : 'cli_' . ...;
```

**Why deferred**
The `CMSApplicationInterface` already declares `getInput(): Input` as the
preferred accessor.  The `$app->input` property is deprecated since
Joomla 5.0 and may be removed in Joomla 6.0+.  The Exporter and Importer
already receive the application via constructor injection; switching from
`$app->input` to `$app->getInput()` is a mechanical change but touches 14
call sites across 7 files.

---

## 11. `@see JModelLegacy` PHPDoc references

|| | |
|---|---|---|
| **Deprecated in** | Not deprecated (stale documentation) |
| **Priority** | Low (documentation only) |

**Files**
- `administrator/components/com_j2xml/src/Model/ExportModel.php:49`
- `administrator/components/com_j2xml/src/Model/SendModel.php:49`

**Current code**
```php
 * @see JModelLegacy
```

**Recommended replacement**
```php
 * @see \Joomla\CMS\MVC\Model\BaseModel
```

**Why deferred**
PHPDoc only; no runtime impact.  `JModelLegacy` is a legacy alias for
`Joomla\CMS\MVC\Model\BaseModel`.

---

## Summary

| # | Pattern | Occurrences | Priority | Target version |
|---|---------|-------------|----------|----------------|
| 1 | `bootstrap.renderModal` | 2 | Medium | 4.1 |
| 2 | `Table::getInstance()` | 1 | Low | 4.1 |
| 3 | `JLoader::registerNamespace()` | 6 | Low | 4.1 |
| 4 | `triggerEvent()` | 18 | Low | 5.0 |
| 5 | `->getError()` | 15 | Low | 5.0 |
| 6 | Underscore-prefixed properties | 100+ | Low | 5.0 |
| 7 | Missing native types | 20+ | Low | 5.0 |
| 8 | `script.php` class-based installer | 1 | Low | 5.0 |
| 9 | `Factory::getDbo()` in tests | 1 | Low | 4.1 |
| 10 | `$app->input` property access | 14 | Medium | 5.0 |
| 11 | `@see JModelLegacy` PHPDoc | 2 | Low | 4.1 |
