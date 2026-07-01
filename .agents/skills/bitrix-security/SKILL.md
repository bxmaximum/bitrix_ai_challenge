---
name: bitrix-security
description: Covers security in Bitrix — CSRF tokens and Csrf filter, SSRF protection in HttpClient, SQL injections (including via ORM select/filter/SqlExpression/runtime/ExpressionField), XSS via htmlspecialcharsbx, user and group access rights verification, field encryption (CryptoField, Cipher). Applied when processing user input, designing APIs, admin actions, code auditing and working with personal data. Key terms — CSRF, XSS, SSRF, SQL injection, htmlspecialcharsbx, CryptoField, Cipher, permissions, access rights.
---

# Security in Bitrix

## CSRF

### Form/AJAX Protection

- `Bitrix\Main\Engine\ActionFilter\Csrf` filter is enabled by default for controller `POST` actions. Disable it only for conscious cases (public webhook with its own verification).
- In HTML forms:

    ```php
    <?= bitrix_sessid_post() ?> <!-- <input type="hidden" name="sessid" value="..."> -->
    ```

- In `fetch` requests: `X-Bitrix-Csrf-Token: <bitrix_sessid()>` header.
- Manual check (if writing a handler directly): `if (!check_bitrix_sessid()) { die('Invalid sessid'); }`.

### When Sessions are Read-only

If `CloseSession` is enabled (via filter), the CSRF token behaves as usual — the kernel reads it from the request rather than the session.

### Antipatterns

- A `GET` endpoint that changes state without a CSRF token and checks.
- Custom `sessid` field in a form without `bitrix_sessid_post()`.

## SSRF

- Do not access URLs from user input directly: `file_get_contents($url)`, `curl` with user hosts.
- Use `Bitrix\Main\Web\HttpClient` with an explicit whitelist of schemes/hosts and timeouts:

    ```php
    $client = new \Bitrix\Main\Web\HttpClient([
        'socketTimeout' => 5,
        'streamTimeout' => 10,
        'redirect' => false,
        'disableSslVerification' => false,
    ]);
    ```

- Block local addresses (`127.0.0.1`, `169.254.*`, internal IPs) before the request.
- For user-provided webhooks — validate host/scheme/port, sign requests with a secret.

## SQL Injections

### Raw SQL (Old Kernel)

```php
$conn = \Bitrix\Main\Application::getConnection();
$helper = $conn->getSqlHelper();

$id = (int)$userInput; // for integers — forced casting
$login = $helper->forSql($userLogin); // string escaping

$conn->queryExecute("UPDATE b_user SET LOGIN = '{$login}' WHERE ID = {$id}");
```

For bulk inserts/updates:

```php
[$insertFields, $insertValues] = $helper->prepareInsert('b_user', $fields);
$conn->queryExecute("INSERT INTO b_user ({$insertFields}) VALUES ({$insertValues})");

$update = $helper->prepareUpdate('b_user', $fields);
$conn->queryExecute("UPDATE b_user SET {$update[0]} WHERE ID = {$id}", $update[1]);
```

### ORM Queries — Can Also Be Vulnerable

Dangerous spots in `getList`/`query()`:

- `select` and `order` — field names **are not escaped**. Never put a "field name from request" there without a whitelist:

    ```php
    $allowedOrder = ['ID', 'CREATED_AT', 'TITLE'];
    $order = in_array(strtoupper($userOrder), $allowedOrder, true) ? strtoupper($userOrder) : 'ID';

    PostTable::getList(['order' => [$order => 'DESC']]);
    ```

- `filter` — values are parameterized, but **keys** (field names with `=`, `>`, etc. prefixes) — are not. Also whitelist.
- `SqlExpression` and `ExpressionField` — the second argument is substituted as is. Never build it from user input:

    ```php
    // DANGEROUS:
    new \Bitrix\Main\DB\SqlExpression("IF({$userField} = 1, 'a', 'b')");

    // SAFE:
    new \Bitrix\Main\DB\SqlExpression('IF(?# = 1, "a", "b")', $userField);
    ```

- `runtime` fields — same rules.

## XSS and HTML Sanitization

- Output everything via `htmlspecialcharsbx($value)`.
- In templates — `<?= htmlspecialcharsbx($item['TITLE']) ?>`.
- For HTML content from users, use `\Bitrix\Main\Text\HtmlFilter` or `CBXSanitizer`:

```php
$sanitizer = new \CBXSanitizer();
$sanitizer->SetLevel(\CBXSanitizer::SECURE_LEVEL_HIGH); // or MEDIUM, LOW
$safeHtml = $sanitizer->SanitizeHtml($userHtml);
```

- Pass JS data via `\Bitrix\Main\Web\Json::encode($data)` instead of direct concatenation.
- `arResult` in a component template is not escaped by default — escape it yourself.

## JWT

`Bitrix\Main\Web\JWT` for token generation and validation:

```php
$payload = ['sub' => $userId, 'exp' => time() + 3600, 'iat' => time()];
$token = \Bitrix\Main\Web\JWT::encode($payload, $secret, 'HS256');
$decoded = \Bitrix\Main\Web\JWT::decode($token, $secret, ['HS256']);
```

Always set `exp` and `iat`. Store secrets in `.settings_extra.php` or environment variables.

## CSRF Details

- `bitrix_sessid_get()` — get token for JS/AJAX headers.
- Cookie `SameSite` settings affect CSRF protection — configure in `crypto` / cookie settings.

## Access Rights

### Basic Checks

```php
global $USER;

if (!$USER->IsAuthorized()) { return; }
if (!$USER->IsAdmin()) { /* ... */ }

if (!$USER->CanDoOperation('edit_own_profile')) { /* ... */ }
```

### Module Permissions

```php
$module = 'vendor.blog';
$rights = \CMain::GetUserRight($module, $USER->GetUserGroupArray());
if ($rights < 'W') { /* ... */ }
```

### Controller Checks

Use `ActionFilter\Authentication` and your own filter based on `Bitrix\Main\Engine\ActionFilter\Base`. Example:

```php
final class RequireRole extends \Bitrix\Main\Engine\ActionFilter\Base
{
    public function __construct(private readonly string $role) { parent::__construct(); }

    public function onBeforeAction(\Bitrix\Main\Event $event)
    {
        global $USER;
        if (!$USER->IsAuthorized() || !in_array($this->role, $USER->GetUserGroupArray(), true))
        {
            $this->errorCollection->add([new \Bitrix\Main\Error('Forbidden', 'ACCESS_DENIED')]);
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::ERROR, null, null, $this);
        }
        return null;
    }
}
```

### `access` Module

For complex ACL — use `access` module, roles, and permission providers (`Access\Role`, `Access\AccessibleItem`).

## Secure Cookies

```php
$response = \Bitrix\Main\Context::getCurrent()->getResponse();
$cookie = new \Bitrix\Main\Web\Cookie('VENDOR_TOKEN', $token, time() + 86400);
$cookie->setHttpOnly(true);
$cookie->setSecure(true);
$cookie->setSpread(\Bitrix\Main\Web\Cookie::SPREAD_DOMAIN); // if needed for all subdomains
$response->addCookie($cookie);
```

Use `HttpOnly` + `Secure` + `SameSite=Lax/Strict`. Do not put access tokens in `localStorage`.

## Value Encryption

- `CryptoField('SECRET')` — tablet field, encrypted transparently.
- `SecretField('TOKEN')` — not returned on `select = '*'`.
- Custom encryption: `Bitrix\Main\Security\Cipher`.

## Miscellaneous

- **Proactive protection** (`proactive` firewall) — scans suspicious request parameters; do not disable without reason.
- **Two-factor authentication** — enabled for admins by default; keep it.
- **Captcha** — `\Bitrix\Main\Captcha` / `CCaptcha` for public forms.
- **Frame protection** — `X-Frame-Options` / CSP headers via kernel settings.
- **Access control module** (`access`) — roles, `Access\Role`, `Access\AccessibleItem` for complex ACL.
- **`crypto` section** in `.settings.php` — encryption keys for cookies and `CryptoField`.
- Store secrets in `.settings_extra.php` and environment variables, **not** in `.settings.php` under git.

## Checklist

- [ ] All `POST` endpoints are protected by `Csrf` and/or `sessid`.
- [ ] External URLs from user input pass host validation, `HttpClient` with timeouts is used.
- [ ] In ORM queries, field names and operators are taken from a whitelist, not from the request.
- [ ] There is no concatenation with user input in `SqlExpression`/`ExpressionField`/`runtime`.
- [ ] In templates, everything coming from the user is via `htmlspecialcharsbx`.
- [ ] Administrative actions check `$USER->IsAdmin()` or specific `CanDoOperation`.
- [ ] Cookies with tokens are `HttpOnly`, `Secure`, `SameSite`.
- [ ] Secrets are not committed; access to `.settings_extra.php` is restricted.
