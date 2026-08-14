# REST API Migration Plan — Replacing XML-RPC

## Executive Summary

J2XML's "Send" feature currently uses XML-RPC (via the bundled `phpxmlrpc`
library) to transfer content between Joomla instances. XML-RPC is a security
liability: cleartext credentials, unauthenticated methods, a large attack
surface in the PHP implementation, and a long history of CVEs. Joomla 4+
removed core XML-RPC support and introduced a built-in REST API with
token-based authentication.

**Decision:** Remove `phpxmlrpc` entirely. Replace the XML-RPC Send feature
with Joomla's REST API (Web Services). The Send feature becomes REST-only.

---

## Current Architecture (XML-RPC)

### Components involved

| File | Role |
|------|------|
| `libraries/eshiol/phpxmlrpc/` (38 files, 548K) | Bundled phpxmlrpc library (server + client) |
| `media/lib_eshiol_phpxmlrpc/js/jquery.xmlrpc.js` | jQuery XML-RPC client for browser-side Send |
| `components/com_j2xml/controllers/services.xmlrpc.php` | XML-RPC server endpoint controller |
| `components/com_j2xml/helpers/xmlrpc.php` | `XMLRPCJ2XMLServices` class — `import()` and `importAjax()` methods |
| `libraries/eshiol/J2xml/Sender.php` | Server-side Sender class — sends XML via `xmlrpc_client` |
| `administrator/components/com_j2xml/controllers/json.php` | JSON export controller (used by browser-side Send) |
| `media/lib_eshiol_j2xml/js/j2xml.js` | Browser-side JS — `eshiol.j2xml.send()` / `sendItem()` using `jQuery.xmlrpc()` |
| `plugins/system/basicauth/` | Basic HTTP auth plugin (enables XML-RPC credential passing) |
| `administrator/manifests/libraries/eshiol/phpxmlrpc.xml` | Library manifest |
| `administrator/components/com_j2xml/config.xml` | Component config — `xmlrpc` enable/disable field |

### Data flow (browser-side Send)

1. User selects items in admin list view, clicks "Send" toolbar button
2. `eshiol.j2xml.send()` collects checked IDs and form params
3. For each item, `sendItem()` calls `options.export_url` (JSON export
   controller) to get the XML data for that item
4. `sendItem()` calls `jQuery.xmlrpc()` to POST the XML to
   `options.remote_url` (the remote site's XML-RPC endpoint) using
   `j2xml.importAjax` method
5. Remote site's `services.xmlrpc.php` controller receives the request,
   calls `XMLRPCJ2XMLServices::importAjax()` which parses the XML and
   imports it via `eshiol\J2xml\Importer`

### Data flow (server-side Send via Sender::send())

1. `Sender::send()` queries `#__j2xml_websites` for remote site URL,
   username, password
2. Encodes XML as base64, builds XML-RPC request with `j2xml.import` method
3. Sends via `xmlrpc_client->send()` or `stream_context_create()` (HTTP POST)
4. Parses XML-RPC response, displays messages to user

### XML-RPC methods exposed

| Method | Auth | Parameters | Description |
|--------|------|------------|-------------|
| `j2xml.import` | username + password | base64 XML, string username, string password | Import XML data (authenticates via Joomla login) |
| `j2xml.importAjax` | session/cookie | string XML, string JSON options | Import XML data (uses already-authenticated user) |

---

## Target Architecture (REST API)

### Authentication

Use Joomla's built-in **token-based authentication** (`X-Joomla-Token`
header or `Authorization: Bearer <token>`). The `#__j2xml_websites` table
will store a `token` field instead of `username` + `password`.

### New REST endpoint

A new `plg_webservices_j2xml` plugin registers a single API route:

```
POST /api/index.php/v1/j2xml/import
```

**Request:**
- Headers: `X-Joomla-Token: <token>`, `Content-Type: application/xml`
  (or `application/json` with the XML in a `data` field)
- Body: J2XML XML content (same format as the file import)
- Query params: `options` — JSON string with import flags
  (categories, contacts, content, fields, etc.)

**Response:** JSON (`Joomla\CMS\Response\JsonResponse`)

```json
{
  "success": true,
  "messages": [
    {"code": 1, "message": "Article imported", "type": "message"},
    {"code": 8, "message": "Category imported", "type": "message"}
  ]
}
```

### New plugin: `plg_webservices_j2xml`

Location: `plugins/webservices/j2xml/`

Files:
- `j2xml.php` — `PlgWebservicesJ2xml` class, registers the API route
- `j2xml.xml` — plugin manifest
- `install.mysql.sql` / `uninstall.mysql.sql` — (none needed)

The plugin implements `onBeforeApiRoute(&$router)` to register:

```php
$router->addRoute(new \Joomla\Router\Route(
    ['POST'],
    'v1/j2xml/import',
    'j2xml.import',
    [],
    [
        'component' => 'com_j2xml',
        'format' => ['application/xml', 'application/json'],
    ]
));
```

The route's controller `j2xml.import` is handled by a new API controller
in the component:

### New API controller: `J2xmlApiControllerImport`

Location: `administrator/components/com_j2xml/src/Controller/ImportController.php`
(or `components/com_j2xml/src/Controller/`)

This controller:
1. Reads the raw XML body from `php://input`
2. Reads `options` from query params (JSON string)
3. Calls `eshiol\J2xml\Importer::import()` (same code path as file import)
4. Returns a `JsonResponse` with the message queue

### Updated Sender class

`libraries/eshiol/J2xml/Sender.php` is rewritten to use `Joomla\Http\Http`
(or PHP's `curl` extension) instead of `xmlrpc_client`:

```php
public static function send($xml, $options, $sid)
{
    // Query #__j2xml_websites for remote_url + token
    // POST XML to: {remote_url}/api/index.php/v1/j2xml/import
    // Headers: X-Joomla-Token: {token}, Content-Type: application/xml
    // Parse JSON response, enqueue messages
}
```

### Updated browser-side JS

`media/lib_eshiol_j2xml/js/j2xml.js` — `sendItem()` replaces
`jQuery.xmlrpc()` with `Joomla.request()` (or `fetch()`):

```javascript
eshiol.j2xml.sendItem = function(options, params) {
    // ... export item via JSON controller (unchanged) ...
    // Send to remote via REST:
    Joomla.request({
        url: options.remote_url,  // now points to /api/index.php/v1/j2xml/import
        method: 'POST',
        headers: {
            'X-Joomla-Token': options.token,
            'Content-Type': 'application/xml'
        },
        data: r.data,  // the XML
        onSuccess: function(resp) { /* parse JSON, show messages */ },
        onError: function(xhr) { /* handle error */ }
    });
};
```

### Updated `#__j2xml_websites` table

Add `token` column, deprecate `username` + `password`:

```sql
ALTER TABLE `#__j2xml_websites`
  ADD COLUMN `token` VARCHAR(255) NOT NULL DEFAULT '' AFTER `password`;
```

The Send form will show a "API Token" field instead of
"Username" / "Password".

---

## Files to Delete (phpxmlrpc removal)

| Path | Reason |
|------|--------|
| `libraries/eshiol/phpxmlrpc/` (entire directory, 38 files) | Bundled XML-RPC library |
| `media/lib_eshiol_phpxmlrpc/` (entire directory) | jQuery XML-RPC client JS |
| `administrator/manifests/libraries/eshiol/phpxmlrpc.xml` | Library manifest |
| `components/com_j2xml/controllers/services.xmlrpc.php` | XML-RPC server controller |
| `components/com_j2xml/controllers/cpanel.xmlrpc.php` | XML-RPC cPanel controller |
| `components/com_j2xml/helpers/xmlrpc.php` | `XMLRPCJ2XMLServices` class |
| `libraries/eshiol/phpxmlrpc/Log/Logger/XmlrpcLogger.php` | XML-RPC logger |

## Files to Modify

| Path | Changes |
|------|---------|
| `libraries/eshiol/J2xml/Sender.php` | Rewrite `send()` to use HTTP POST instead of XML-RPC; remove `_xmlrpc_j2xml_send()`; remove `http_parse_headers()` |
| `media/lib_eshiol_j2xml/js/j2xml.js` | Replace `jQuery.xmlrpc()` with `fetch()` / `Joomla.request()`; update `sendItem()` |
| `media/lib_eshiol_j2xml/js/j2xml.min.js` | Minified version of above |
| `administrator/components/com_j2xml/config.xml` | Remove `xmlrpc` field; add `api` field (enable/disable REST endpoint) |
| `administrator/components/com_j2xml/controllers/json.php` | Remove `Sender` import (no longer needed here) |
| `plugins/system/j2xml/j2xml.php` | Remove `JLoader::import('eshiol.J2xml.Sender')` if unused; remove XML-RPC references |
| `cli/j2xml.php` | Remove XML-RPC references |
| `components/com_j2xml/j2xml.php` | Remove XML-RPC controller registration |
| `administrator/manifests/packages/pkg_j2xml.xml` | Remove `lib_eshiol_phpxmlrpc` from package; add `plg_webservices_j2xml` |
| `libraries/eshiol/J2xml/classmap.php` | Remove `J2XMLSender` alias if Sender is rewritten |
| `administrator/components/com_j2xml/sql/install.mysql.utf8.sql` | Add `token` column to `#__j2xml_websites` |
| `administrator/components/com_j2xml/sql/uninstall.mysql.utf8.sql` | (no change) |
| Send view templates (`views/send/tmpl/*.php`) | Replace username/password fields with token field; update `remote_url` to point to API endpoint |

## Files to Create

| Path | Purpose |
|------|---------|
| `plugins/webservices/j2xml/j2xml.php` | Web Services plugin — registers `/v1/j2xml/import` route |
| `plugins/webservices/j2xml/j2xml.xml` | Plugin manifest |
| `plugins/webservices/j2xml/index.html` | Standard Joomla empty index.html |
| `administrator/components/com_j2xml/src/Controller/ImportController.php` | API controller for the import endpoint |

## Files to Remove from Package

| Path | Reason |
|------|--------|
| `lib_eshiol_phpxmlrpc` (library) | No longer bundled |
| `plg_system_basicauth` (plugin) | Was only needed for XML-RPC HTTP basic auth |

**Note on basicauth:** The basicauth plugin may still be useful for other
purposes (REST API basic auth for local development). We should check if
anything else depends on it before removing it from the package. If in
doubt, keep it in the package but remove the XML-RPC-specific references.

---

## Migration Path

### Phase 1: Add REST API (additive, no breakage)

1. Create `plg_webservices_j2xml` plugin
2. Create API `ImportController`
3. Add `token` column to `#__j2xml_websites` (additive ALTER)
4. Rewrite `Sender::send()` to use REST (keep old method as fallback)
5. Update browser JS to use REST (keep XML-RPC as fallback if REST fails)
6. Add REST plugin to package manifest
7. Update Send form to show token field (keep username/password as
   "legacy" fields)

### Phase 2: Remove XML-RPC (breaking change — major version bump)

1. Delete `libraries/eshiol/phpxmlrpc/` entirely
2. Delete `media/lib_eshiol_phpxmlrpc/` entirely
3. Delete `components/com_j2xml/controllers/services.xmlrpc.php`
4. Delete `components/com_j2xml/controllers/cpanel.xmlrpc.php`
5. Delete `components/com_j2xml/helpers/xmlrpc.php`
6. Remove XML-RPC references from `Sender.php`, `j2xml.js`, config, etc.
7. Remove `lib_eshiol_phpxmlrpc` from package manifest
8. Remove `xmlrpc` field from component config
9. Remove `username`/`password` from `#__j2xml_websites` (DROP COLUMN)
10. Remove basicauth plugin from package (if nothing else uses it)
11. Update tests to use REST API
12. Update documentation

### Phase 3: Cleanup

1. Remove `plg_system_basicauth` from package (if confirmed unused)
2. Remove XML-RPC language strings
3. Update CHANGELOG with breaking change notice
4. Bump major version

---

## REST API Endpoint Specification

### `POST /api/index.php/v1/j2xml/import`

Import J2XML content from an XML document.

**Authentication:** Required (X-Joomla-Token header)

**Request:**

| Header | Value |
|--------|-------|
| `X-Joomla-Token` | API token of a user with `core.admin` permission on `com_j2xml` |
| `Content-Type` | `application/xml` (raw XML body) or `application/json` (JSON with `data` field) |

| Query Parameter | Type | Default | Description |
|----------------|------|---------|-------------|
| `options` | string (JSON) | `{}` | Import options: `categories`, `contacts`, `content`, `fields`, `images`, `keep_id`, `keep_user_id`, `tags`, `users`, `viewlevels`, `weblinks`, etc. |

**Request body (application/xml):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<j2xml version="21.12.0">
  <content>
    <title>Test Article</title>
    ...
  </content>
</j2xml>
```

**Request body (application/json):**
```json
{
  "data": "<?xml version=\"1.0\" encoding=\"UTF-8\"?><j2xml version=\"21.12.0\">...</j2xml>",
  "options": {
    "content": 1,
    "categories": 1,
    "users": 1
  }
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "messages": [
    {"code": 1, "message": "Article 'Test Article' imported", "type": "message"},
    {"code": 8, "message": "Category 'Test Category' imported", "type": "message"}
  ]
}
```

**Response (401 Unauthorized):**
```json
{
  "success": false,
  "message": "JGLOBAL_AUTH_ACCESS_DENIED"
}
```

**Response (400 Bad Request):**
```json
{
  "success": false,
  "message": "LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN"
}
```

---

## Test Plan

### REST API tests

1. **Import via REST:** POST XML to `/api/index.php/v1/j2xml/import` with
   valid token → verify content imported in database
2. **Import without token:** POST without `X-Joomla-Token` → 401
3. **Import with invalid token:** POST with wrong token → 401
4. **Import invalid XML:** POST malformed XML → 400
5. **Import with options:** POST with `options={"content":1,"categories":0}`
   → only articles imported, not categories
6. **Send from J5 to J6:** Use Send UI to send articles from Joomla 5 to
   Joomla 6 via REST API
7. **Send from J6 to J5:** Reverse direction
8. **Round-trip:** Export → Send to remote → Re-export from remote →
   verify data matches

### Regression tests

1. **File import still works:** Upload XML via admin interface (unchanged)
2. **Export still works:** Export articles/users/categories via admin UI
3. **Uninstall clean:** Remove J2XML, verify no leftover tables/files

---

## Security Improvements

| XML-RPC (old) | REST API (new) |
|---------------|----------------|
| Cleartext username/password in XML-RPC request | Token-based auth (HMAC-SHA256), no password sent |
| Token in `X-Joomla-Token` header, can be revoked independently |
| Unauthenticated `importAjax` method | All endpoints require valid token |
| Large `phpxmlrpc` attack surface (38 files) | No third-party library — uses Joomla core API framework |
| Basic auth plugin needed for credential passing | Joomla core token auth plugin |
| XML-RPC protocol vulnerabilities (XXE, entity expansion, etc.) | Standard HTTP POST with XML/JSON body |
