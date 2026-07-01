---
name: bitrix-caching
description: Covers caching in Bitrix — Cache (unmanaged), ManagedCache, TaggedCache, ORM auto-cache, component cache via startResultCache/endResultCache, Composite Site, cache engine configuration (files, memcached, redis) in .settings.php. Applied when optimizing performance, invalidating by tags and events, setting TTL, cache warm-up, and debugging cache hits. Key terms — cache, invalidate, TaggedCache, ManagedCache, startResultCache, cacheDir, clean.
---

# Caching in Bitrix

## Cache Levels

1. **Unmanaged Cache** (`Bitrix\Main\Data\Cache`) — with TTL, key, and path. Cleared automatically by TTL and manually.
2. **Managed Cache** (`ManagedCache`) — lives until explicit invalidation, convenient for "rarely changing" data.
3. **Tagged Cache** (`TaggedCache`) — keys are grouped by tags; invalidating one tag clears all associated entries.
4. **ORM Cache** — automatic: `isCacheable()` in the tablet + `['cache' => ['ttl' => ...]]` in `getList`.
5. **Component Cache** — via `startResultCache()` / `endResultCache()` and parameters `CACHE_TYPE`, `CACHE_TIME`, `CACHE_GROUPS`.
6. **Composite Cache** — HTML cache of the entire page (`Bitrix\Main\Composite\Engine`).

## Configuration in `.settings.php`

```php
'cache' => [
    'value' => [
        'type' => [
            // 'class_name' => \Bitrix\Main\Data\CacheEngineRedis::class, // one of these
            'type' => 'redis',     // files|memcache|redis|apc|xcache|none
            'host' => '127.0.0.1',
            'port' => 6379,
            'serializer' => \Redis::SERIALIZER_IGBINARY,
        ],
        'sid' => 'PROJECT_',       // key prefix
        'cache_flags' => [
            'config_options' => 3600,
            'site_template' => 3600,
            'iblock_include' => 3600,
        ],
    ],
    'readonly' => false,
],
```

Different sections (`config_options`, `menu`, `site_template`, etc.) define TTL for internal kernel caches.

## Unmanaged Cache Template

```php
$cache = \Bitrix\Main\Data\Cache::createInstance();
$ttl = 3600;
$cacheId = 'posts_list_' . md5(serialize($filter));
$cacheDir = '/vendor_blog/posts';

if ($cache->initCache($ttl, $cacheId, $cacheDir))
{
    $data = $cache->getVars();
}
elseif ($cache->startDataCache())
{
    $data = PostTable::getList([
        'filter' => $filter,
        'select' => ['ID', 'TITLE'],
    ])->fetchAll();

    // If conditions are not met — stop writing cache:
    if (empty($data))
    {
        $cache->abortDataCache();
    }
    else
    {
        $cache->endDataCache($data);
    }
}
```

- `cacheId` — unique key, includes all variables affecting the result.
- `cacheDir` — cache "folder"; convenient to clear by directory `$cache->cleanDir($cacheDir)`.

## Managed Cache

```php
$managed = \Bitrix\Main\Application::getInstance()->getManagedCache();

if ($managed->read(86400, $cacheId, 'posts'))
{
    $data = $managed->get($cacheId);
}
else
{
    $data = $this->fetchExpensive();
    $managed->setImmediate($cacheId, $data); // or set() — write at the end of request
}

// Invalidation:
$managed->clean($cacheId, 'posts');
$managed->cleanDir('posts');
```

## Tags

```php
use Bitrix\Main\Application;

$taggedCache = Application::getInstance()->getTaggedCache();

$taggedCache->startTagCache('/vendor_blog/posts');
$taggedCache->registerTag('posts_list');
$taggedCache->registerTag('post_42');
$taggedCache->endTagCache();

// Tag invalidation — clears all entries registered under this tag:
$taggedCache->clearByTag('posts_list');
```

### ORM Tags

Any `*Table` class with `isCacheable() === true` automatically publishes the tag `ORM_<TABLE_NAME>` upon write. This allows linking dependent HTML caches to the table:

```php
$taggedCache->registerTag('ORM_VENDOR_MODULE_POST'); // cache will clear when table changes
```

## ORM Query Cache

```php
PostTable::getList([
    'select' => ['*'],
    'filter' => ['=ACTIVE' => 'Y'],
    'cache'  => [
        'ttl' => 3600,
        'cache_joins' => true, // cache JOIN queries
    ],
]);
```

Reset: `PostTable::cleanCache()`.

## Component Cache

In `class.php` / `component.php`:

```php
if ($this->startResultCache(false, [
    $USER->IsAuthorized(),
    $arParams['SECTION_ID'],
]))
{
    $this->arResult['ITEMS'] = $this->fetchItems();
    $this->includeComponentTemplate();
}
```

Component parameters controlling cache:

- `CACHE_TYPE`: `A` (autocache), `Y`, `N`.
- `CACHE_TIME`: TTL in seconds.
- `CACHE_GROUPS`: `Y` — key depends on user groups.

## Composite Cache

For composite — enable the `compression` module and add to required components:

```php
\CBitrixComponent::includeComponentClass($componentName);
\Bitrix\Main\Page\Asset::getInstance()->addString(...);
```

Consider composite constraints: dynamic blocks are marked via `$APPLICATION->SetPageProperty('composite_frame_mode', 'Y')` / `setFrameMode`, personal data should not be in the static part, AJAX components are fetched with a separate request.

### Composite zones and NGINX

- Static zone — full page HTML cache.
- Dynamic zone (`data-dynamic`) — refreshed via AJAX on each hit.
- Autocomposite vs manual composite — configure in Admin → Composite settings.
- NGINX can serve static composite files directly; configure composite pool path (BitrixVM: *Configure nginx to use composite cache*).

For ORM/SQL optimization see skill `bitrix-performance`.

```php
'cache' => [
    'value' => [
        'type' => [
            'class_name' => \Bitrix\Main\Data\CacheEngineRedis::class,
            'extension' => 'redis',
            'host' => '127.0.0.1',
            'port' => 6379,
        ],
    ],
],
```

For ORM/SQL optimization see skill `bitrix-performance`.

## Invalidation by Events

Typical scheme: `OnAfterUpdate`/`OnAfter*` handler in the tablet calls `$taggedCache->clearByTag(...)`. Use new ORM events via `EventResult` instead of `$GLOBALS['USER_FIELD_MANAGER']->...`.

## Antipatterns

- Caching "live" data (balances, stock levels) with high TTL without invalidation.
- Using global `$_SESSION`/`$USER` inside the cache key instead of explicit variables.
- One shared `cacheDir` for all modules — hard to clear selectively.
- Missing `abortDataCache()` for empty/error results.
- Enabling `composite` without testing dynamic blocks.

## Checklist

- [ ] Cache level selected: short-lived → unmanaged; rarely changing and critical → managed + tags.
- [ ] Cache key includes all parameters affecting the result (filters, language, permissions).
- [ ] Cache invalidation is automated via tags/events, not manual `cleanDir('/')`.
- [ ] Component cache accounts for user groups where important.
- [ ] Production environment uses Redis/Memcached instead of file cache.
