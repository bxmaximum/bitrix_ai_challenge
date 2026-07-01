---
name: bitrix-settings
description: Covers kernel configuration — .settings.php sections (connections, cache, session, crypto, exception_handling, routing, messenger, pull, smtp, loggers, composer), .settings_extra.php, readonly flag. Applied when configuring kernel behavior. Key terms — .settings.php, settings, readonly, connections, exception_handling.
---

# Kernel Configuration (.settings.php)

Primary config: `/bitrix/.settings.php` or `/local/.settings.php` (from main 24.100+). Overrides: `/bitrix/.settings_extra.php` or `/local/.settings_extra.php`.

> Errors in `.settings.php` can break the site. Back up before changes.

Also maintain `/local/php_interface/dbconn.php` for legacy kernel compatibility even when using D7 only.

## Structure

Each section:

```php
'section_name' => [
    'value' => [ /* settings */ ],
    'readonly' => true,  // true = no runtime API changes
],
```

## Key Sections

| Section | Purpose |
| --- | --- |
| `connections` | **Required.** DB and additional connections |
| `cache` | Cache engine (files/redis/memcache) |
| `session` | Session handlers, lifetime, separated mode |
| `crypto` | Encryption keys for cookies and fields |
| `exception_handling` | `debug`, error masks, `log` file |
| `routing` | Route config files (`web.php`, etc.) |
| `messenger` | Queue brokers and handlers |
| `loggers` | PSR-3 logger registration |
| `controllers` | Controller namespaces |
| `services` | DI container (global services) |
| `console` | CLI commands |
| `composer` | Path to `composer.json` |
| `pull` | Push/pull server settings |
| `smtp` | Mail transport |
| `default_language` | Default language code |

Module `.settings.php` files merge into global config after `includeModule`.

## exception_handling

```php
'exception_handling' => [
    'value' => [
        'debug' => false,  // NEVER true in production
        'log' => [
            'settings' => [
                'file' => 'bitrix/modules/error.log',
                'log_size' => 1000000,
            ],
        ],
    ],
    'readonly' => false,
],
```

## composer

```php
'composer' => [
    'value' => ['config_path' => '../composer.json'],
    'readonly' => true,
],
```

## crypto

Encryption keys for `CryptoField`, encrypted cookies. Store keys outside git — use `.settings_extra.php` or environment.

## readonly

- `true` — protects critical settings (DB, services) from runtime modification.
- `false` — allows runtime changes (e.g. `exception_handling`).

## Checklist

- [ ] User overrides in `/local/.settings.php`, not edited `/bitrix/.settings.php`.
- [ ] `debug => false` on production.
- [ ] Secrets in `.settings_extra.php` or env vars.
- [ ] `readonly => true` for connections and services.
- [ ] Module settings in module `.settings.php`, not global unless cross-module.
