# Deprecated Patterns — Resolution Record

All previously tracked deprecated Joomla/PHP patterns have been reviewed.
This file documents how each item was resolved, including the cases where the
"deprecated" API is intentionally retained because Joomla itself still depends
on it internally.

---

## 1. `HTMLHelper::_('bootstrap.renderModal')` — RESOLVED

Both call sites were migrated to the Joomla Dialog Web Component
(`<joomla-dialog>`), available since Joomla 5.0 via the `joomla.dialog`
web asset:

- `plugins/system/j2xml/layouts/joomla/toolbar/modal.php` — the toolbar
  Export/Send dialogs are now created lazily via
  `new (customElements.get('joomla-dialog'))({...})` on button click.
  `popupButtons` callbacks are passed in the constructor config (they cannot
  be expressed through element attributes or `data-joomla-dialog` JSON), and
  the element is only attached on `show()`, which is the only way to have the
  footer buttons rendered — `connectedCallback()` runs `renderLayout()` as
  soon as the element upgrades, so assigning `popupButtons` to an
  already-connected element is too late.
- `administrator/components/com_j2xml/views/import/tmpl/default.php` — the
  import-options dialog is created the same way via
  `eshiol.j2xml.showImportDialog()`, called from `media/com_j2xml/js/import.js`
  after the file is validated.

Related JS updates:

- `media/lib_eshiol_j2xml/js/j2xml.js` — `hideParentModal()` closes the
  Joomla dialog with its `close()` method; the unused `showParentModal()` and
  Joomla 4 Bootstrap fallback were removed. `importerModal()` now finds
  the iframe inside the dialog element (the previous code looked up the
  container id as if it were the iframe, so field copying was silently dead).
- The `form-validation` attribute used by the old send footer was verified to
  be inert upstream (Joomla's `joomla-toolbar-button` only acts on it when a
  `task` attribute is set, which it never was) and was dropped.
- The modern FormData/drag-and-drop upload path is used when available, while
  the legacy file-form uploader remains as a fallback for older browsers.

Manual verification on Joomla 5/6 still recommended for: toolbar export/send
dialogs, import options dialog, footer submit/cancel, iframe field copying.

## 2. `triggerEvent()` — RESOLVED

All active call sites now use the event dispatcher directly:

- `libraries/eshiol/J2xml/Exporter.php` — the 10 identical
  `onJ2xmlAfterExport` dispatches were consolidated into a private
  `dispatchEvent()` helper returning `$event['result']`, matching the
  `triggerEvent()` return contract.
- `Importer.php`, `Table/Content.php`, `cli/j2xml.php`,
  `views/import/tmpl/default.php` — direct
  `getDispatcher()->dispatch($name, new \Joomla\Event\Event($name, $args))`.
- `ImportModel.php` — `onContentPrepareData` uses the concrete
  `\Joomla\CMS\Event\Model\PrepareDataEvent` class (the same class
  `triggerEvent()` resolved internally via `getEventClassByEventName()`).
  The API `ImportController` and other custom J2XML events use generic
  `Joomla\Event\Event` because no more-specific core event class exists for
  those event names.
- Event names, argument order (numeric keys) and mutable-by-reference args are
  preserved — `PrepareDataEvent` maps positional args via its
  `legacyArgumentsOrder` table, identical to the old code path.

## 3. `$table->getError()` / `$user->getError()` — RETAINED (justified)

**Not removable.** Verified against the Joomla 5/6 source:

- `Joomla\CMS\Table\Table::store()` catches database exceptions internally,
  calls `setError($e->getMessage())` and returns `false` — it does not
  rethrow. Wrapping `store()` in try/catch would never see the exception and
  `getError()` remains the only way to recover the failure message.
- `Joomla\CMS\User\User::save()` behaves identically (catch → `setError()` →
  `return false`).
- Joomla core itself still calls `getError()` throughout its own admin models
  for exactly this reason.

The 14 call sites (failure logging in the `Table\*` import classes) are
therefore correct as-is and match core practice. If Joomla eventually stops
catching inside `store()`/`save()`, these sites can be revisited.

## 4. Underscore-prefixed properties — RESOLVED

J2XML-owned properties renamed to camelCase, dead ones deleted:

| Old | New / action |
| --- | --- |
| `Table::$_excluded` | `protected array $excluded` |
| `Table::$_aliases` | `protected array $aliases` |
| `Table::$_jsonEncode` | `protected array $jsonEncode` |
| `Exporter::$_option` | `private ?string $option` |
| `Exporter::$_image_path`, `$_admin` | deleted (dead) |
| `Importer::$_option` | `protected ?string $option` |
| `Importer::$_nullDate` | `protected string $nullDate` |
| `Importer::$_user` | `protected ?User $user` |
| `Importer::$_user_id` | `protected int $userId` |
| `Importer::$_now` | `protected string $now` |
| `Importer::$_usergroups` | deleted (dead) |

Notes:

- `Table::$_jsonEncode` intentionally shadowed Joomla's own
  `Table::$_jsonEncode` (used by `bind()`). Both had the same `[]` default and
  nothing ever set either, so the rename is behavior-neutral.
- `User.php` accessed `$item->_excluded` on another instance; updated to the
  new name. `tests/unit/TableTest.php` reflection references updated.
- Inherited Joomla properties remain declared by the parent where Joomla needs
  them, but J2XML no longer reads the deprecated parent `$_db` property:
  all 278 table-library accesses now use the built-in `getDatabase()` API.
  Parent table metadata such as `$_tbl` remains internal Joomla API surface.

## 5. Native type declarations — RESOLVED

Verified against real Joomla 5/6 parent signatures (fetched from the
`joomla-cms` repository, not assumed):

- **Return types added** on all applicable overrides:
  `display(): void|bool`, `execute(): mixed`,
  `getModel(): BaseDatabaseModel|false`, `getForm(): Form|false`,
  `loadFormData(): mixed`, `populateState(): void`, plus private helpers
  (`: array|false`, `: string|false`, `: Registry`, `: ?string`, `: void`).
- **Parameter types were NOT added to overrides**: PHP forbids a child method
  from narrowing an untyped parent parameter (`?string $tpl` on
  `display($tpl = null)` would fatal). Param types were added only to private
  methods (`readPackageFile(string $file)`, `resolveContentType(Input $input)`,
  `modalButtonData(string ..., bool $formValidation)`, etc.).
- **Joomla 6 class-removal pitfall**: `resolveContentType()` was first typed
  against `Joomla\CMS\Input\Input`, which no longer exists on J6 — the app
  returns the framework `Joomla\Input\Input` there (J5's CMS class extends
  it), so every J6 admin page fatal-errored until the type was changed to the
  framework parent. When typing against Joomla classes, always verify the
  class still exists on J6.
- `return parent::display($tpl);` was converted to `parent::display($tpl);`
  in the Export/Send views — `return <expr>` inside a `: void` function is a
  compile-time error.
- Typed properties: `Table::$excluded/$aliases/$jsonEncode` (`array`),
  `Importer` props (`string`, `?User`, `int`, `?string`,
  `CMSApplicationInterface`), `AbstractRawView` props (`array`, `Registry`,
  `string`). All subclass redeclarations (`RawView::$exportMethod`) were
  updated in lockstep — a typed parent property requires typed children.
- `stubs/joomla.php` gained `BaseDatabaseModel`, `InstallerAdapter`,
  `InstallerScriptInterface`, `DatabaseAwareInterface/Trait`, the framework
  `Joomla\Input\Input`/`Files` classes, and corrected
  `getForm`/`loadForm`/`getModel` return types so PHPStan can verify the new
  declarations.

## 6. `script.php` installer — RESOLVED

`administrator/components/com_j2xml/script.php` now returns an anonymous
class implementing `InstallerScriptInterface` (available since Joomla 5.0,
and the only non-deprecated path in Joomla 6 — the legacy named-class path
triggers `E_USER_DEPRECATED` in the adapter). It implements
`DatabaseAwareInterface` so the adapter injects the database via
`setDatabase`; the application still comes from `Factory::getApplication()`
because **no `ApplicationAwareInterface` exists in joomla-cms** — an initial
attempt to use it returned HTTP 500 on install. `preflight`/`postflight`/
`update`/`install`/`uninstall` semantics preserved; `preflight` now also
guards a non-array `manifest_cache` decode.

The unreferenced legacy `plugins/system/j2xml/enable.php` installer file was
removed; it was excluded from the plugin manifest and was never shipped. The
component installer also avoids MySQL-only `AFTER password` syntax when adding
the optional Pro token column, so that migration remains valid on PostgreSQL.

## 7. `Factory::$user` in test bootstrap — RESOLVED

`tests/scripts/bootstrap.php` now follows Joomla's own `cli/joomla.php`
entry pattern:

1. Alias the session services to `session.cli`.
2. Resolve `Joomla\Console\Application` from the container (the service
   provider returns the CMS `Joomla\CMS\Application\ConsoleApplication`,
   which uses the `IdentityAware` trait).
3. Register it via `Factory::$application` — the same assignment Joomla's
   own `cli/joomla.php` performs (the provider deliberately does not do it).
4. Load the dummy admin with `$app->loadIdentity(new User([...]))` — the
   supported `IdentityAware` API (there is no `setIdentity()` method).

`Factory::$user` is gone entirely. `Factory::$application` remains by design
— it is the mechanism Joomla itself uses to register CLI applications and is
not deprecated.

---

## Summary

| # | Pattern | Resolution |
| --- | --- | --- |
| 1 | `bootstrap.renderModal` | Migrated to `joomla-dialog` web component |
| 2 | `triggerEvent()` | Migrated to `getDispatcher()->dispatch()` |
| 3 | `->getError()` | Retained — Joomla swallows exceptions internally (documented) |
| 4 | Underscore-prefixed properties | Renamed to camelCase; dead props removed |
| 5 | Missing native types | Return/prop types added; param types impossible on overrides |
| 6 | Class-based installer | `InstallerScriptInterface` anonymous class (×2) |
| 7 | `Factory::$user` | `ConsoleApplication` + `loadIdentity()` |
