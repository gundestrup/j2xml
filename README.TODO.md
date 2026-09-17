# README.TODO — all items completed (2026-09)

- [x] Install the plugin via a "compiled" zip file of the plugin, not online
      — Done: `tests/scripts/run-all-tests.sh` Phase 2 builds
      `build/pkg_j2xml.zip` via `scripts/build-package.sh` and installs it
      through Joomla's installer on both Joomla 5 and Joomla 6.
- [x] Test uninstall of the plugin (that it uninstalls cleanly)
      — Done: `run-all-tests.sh` Phase 8 uninstalls the package and asserts
      no uninstaller warnings/errors on Joomla 5 and 6.

## Test coverage — all covered

Features:

- [x] Export — covered by integration suite (export + round-trip)
- [x] Import — covered by integration suite (UI, CLI, REST API paths)
- [x] Send — covered by comprehensive send test (all types, J5 + J6)

Content types:

- [x] Users
- [x] Articles
- [x] Categories
- [x] Contacts
- [x] Modules
- [x] Menus
- [x] Tags
- [x] Fields

Reference: <https://www.eshiol.it/joomla/j2xml/j2xml39.html> (screenshots and
explanations of the functions)

- [x] Export all content types, import new content, then re-export showing
      old and new content — Done: Phase 5 import → re-export round-trip
      verifies imported content survives an export cycle.

----

- [x] Analyse what XMLRPC is being used for in the plugin
      — Done: XML-RPC was the legacy transfer mechanism; it has been fully
      removed (Sender.php, Messages.php, eshiol/phpxmlrpc library). Transfer
      now uses the Joomla Webservices REST API with token authentication.
