---
name: bitrix-sessions
description: Covers Bitrix sessions — Application::getSession(), getKernelSession(), getLocalSession(), BX_SECURITY_SESSION_READONLY and BX_SECURITY_SESSION_VIRTUAL modes, storages (cache, database, redis, null session handler), separated session mode in .settings.php. Applied instead of direct $_SESSION access, when optimizing AJAX session locks, configuring alternative storages, and separating kernel/local sessions. Key terms — session, getSession, session storage, BX_SECURITY_SESSION_READONLY, separated session, getKernelSession, getLocalSession.
---

# Bitrix Sessions

Directly accessing `$_SESSION` breaks non-functional modes (`readonly`, virtual session, separated session) and tests. Use the **Session API**.

```php
use Bitrix\Main\Application;

$session = Application::getInstance()->getSession();

if (!$session->has('cart'))
{
    $session->set('cart', ['items' => []]);
}

$session['cart']['items'][] = $productId;
$session['cart'] = $cart; // set via ArrayAccess

$session->remove('flash_message');
$session->clear();          // remove everything
```

Interface — `Bitrix\Main\Session\SessionInterface` + `ArrayAccess`.

## Kernel Session (hot)

For a small amount of fast data that the kernel accesses almost every hit:

```php
$kernelSession = Application::getInstance()->getKernelSession();
$kernelSession->set('UF_LAST_LOGIN', time());
```

In `separated` mode, the kernel stores the hot fragment in encrypted cookies — making authorization/CSRF fast without accessing backend storage.

## SessionLocalStorage — "Session Cache"

Using `$session->set(...)` for cart cache or temporary calculations is bad: long values block the hit and slow down parallel AJAX. Since `main 20.5.400`, there is an isolated container tied to `session_id()`:

```php
$local = Application::getInstance()->getLocalSession('cart');

if (!isset($local['productIds']))
{
    $local->set('productIds', [1, 2, 3]);
    $local->set('total', 42);
}

$ids = $local->get('productIds');
```

- Stored in the cache from the `cache` section in `.settings.php` (not in `$_SESSION`).
- Automatically saved at the end of the hit.
- With file cache, `$_SESSION` is used internally so that GC correctly cleans up stale data.

Use for: carts, temporary filters, wizards, UI drafts.

## Session Modes

### Read-only (non-blocking)

Suitable for AJAX where writing is not needed — removes the write lock:

```php
// before including prolog
define('BX_SECURITY_SESSION_READONLY', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
```

After this:

- Session is read from redis/memcache/db without `flock`/SETNX — parallel AJAX requests don't wait for each other.
- Changes **will not be saved** at the end of the hit.

Good for read-only endpoints (search, suggestions, counters).

### Virtual (in-memory)

```php
define('BX_SECURITY_SESSION_VIRTUAL', true);
```

- Session is created in memory, not saved at the end of the hit.
- Used for REST-API with token-based authorization — authorization passes, but the session doesn't clutter storage.

### Separated Mode

"Hot" kernel data → cookies, "cold" data → backend storage. Enabled in `.settings.php`:

```php
'session' => [
    'value' => [
        'mode'     => 'separated',
        'lifetime' => 14400,
        'handlers' => [
            'kernel'  => 'encrypted_cookies',
            'general' => ['type' => 'redis', 'host' => '127.0.0.1', 'port' => 6379],
        ],
    ],
],
```

- Fewer calls to Redis/DB.
- Suitable for high-load: the "hot" part (`$_SESSION['BX']`) goes to cookies/separate kernel storage, the "cold" part — to the general backend (Redis/DB).

## Storages

Specified in `/local/.settings.php` (or `/bitrix/.settings.php`) in the `session.value.handlers.general.type` section:

| type | When | Note |
| --- | --- | --- |
| `file` | Dev, small projects | Lock by `flock` → AJAX slows down |
| `redis` | High-load, clusters | Supports `servers` (cluster/single), serialization |
| `memcache` | Legacy projects | No persistence |
| `database` | When no cache servers | `b_user_session` table, not for high-load |

### Redis Cluster Example (multi-master)

```php
'session' => [
    'value' => [
        'mode' => 'default',
        'handlers' => [
            'general' => [
                'type' => 'redis',
                'servers' => [
                    ['host' => '10.0.0.1', 'port' => 6379],
                    ['host' => '10.0.0.2', 'port' => 6379],
                    ['host' => '10.0.0.3', 'port' => 6379],
                ],
                'serializer' => \Redis::SERIALIZER_IGBINARY,
                'persistent' => false,
                'failover'   => \RedisCluster::FAILOVER_DISTRIBUTE,
                'timeout'      => null,
                'read_timeout' => null,
            ],
        ],
    ],
],
```

### Memcache Cluster Example

```php
'handlers' => [
    'general' => [
        'type' => 'memcache',
        'servers' => [
            ['host' => '10.0.0.1', 'port' => 11211, 'weight' => 1],
            ['host' => '10.0.0.2', 'port' => 11211],
        ],
    ],
],
```

### Database

```php
'handlers' => [
    'general' => ['type' => 'database'], // b_user_session table
],
```

## General Options

```php
'session' => [
    'value' => [
        'lifetime'                 => 14400,  // seconds
        'mode'                     => 'default',
        'regenerateIdAfterLogin'   => true,   // recommended: fixation protection
        'ignoreSessionStartErrors' => false,  // true — hit continues even if Redis is unavailable
        'handlers' => [ ... ],
    ],
],
```

## Flash Messages (common pattern)

```php
$session = Application::getInstance()->getSession();
$session->set('flash.success', 'Post saved');

// next request:
if ($msg = $session->get('flash.success'))
{
    $session->remove('flash.success');
    echo htmlspecialcharsbx($msg);
}
```

## Security

- After successful login/password change — `$session->regenerateId()`. Or `regenerateIdAfterLogin = true` in config.
- Session cookies should be `HttpOnly`, `Secure`, `SameSite=Lax|Strict` — configured in main module or via `session.cookie_*` in php.ini. See `bitrix-security`.
