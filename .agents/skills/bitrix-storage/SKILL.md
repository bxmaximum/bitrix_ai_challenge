---
name: bitrix-storage
description: Covers Persistent Storage API (main 25.1100+) — PersistentStorageInterface, DeferredStorageDecorator, PSR-16 based temporary storage with guaranteed TTL. Applied when sessions/cache/files are insufficient for time-bound data. Key terms — PersistentStorage, DeferredStorageDecorator, StorageInterface, TTL.
---

# Persistent Storage (main 25.1100+)

For data that must survive for a guaranteed period — unlike cache (may evict anytime) or sessions (cleared on logout).

## Interfaces

- `StorageInterface` — extends PSR-16 `CacheInterface`.
- `PersistentStorageInterface` — adds guaranteed TTL retention.
- `ConnectionBasedPersistentStorage` — DB-backed implementation.
- `DeferredStorageDecorator` — batches writes until end of hit (faster, but data lost on crash).

## Usage

```php
$storage = \Bitrix\Main\DI\ServiceLocator::getInstance()
    ->get(\Bitrix\Main\Data\Storage\PersistentStorageInterface::class);

$storage->set('vendor.module.processing.item123', ['status' => 'pending'], 3600);
$data = $storage->get('vendor.module.processing.item123');
$storage->delete('vendor.module.processing.item123');
```

Key format: `module.feature.unique_key` (max 255 chars). Value must be JSON-serializable.

TTL: `null`, seconds (`int`), or `\DateInterval`. Max recommended TTL: 604800 (7 days).

## Deferred Storage

For high-write scenarios where batching is acceptable:

```php
$deferred = new \Bitrix\Main\Data\Storage\DeferredStorageDecorator(
    $persistentStorage,
);
$deferred->set('key', $value, 3600);
// Writes flushed at end of hit
```

## When to Use What

| Need | Use |
| --- | --- |
| Per-user session data | `Application::getSession()` |
| Computed results, may evict | `Cache` / `ManagedCache` |
| Guaranteed TTL, moderate writes | `PersistentStorageInterface` |
| Many writes per hit, loss OK on crash | `DeferredStorageDecorator` |
| Large blobs | Files in `/upload/` |

## Checklist

- [ ] Keys follow `module.feature.id` convention.
- [ ] TTL ≤ 7 days unless business requires otherwise.
- [ ] Values are JSON-serializable scalars/arrays.
- [ ] Not used for secrets — use `CryptoField` or encrypted cookies instead.
