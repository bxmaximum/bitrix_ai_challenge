---
name: bitrix-service-locator
description: Covers DI container Bitrix\Main\DI\ServiceLocator (PSR-11) — registration of services in the services section of a module's .settings.php file, autowire, retrieving dependencies via has()/get(), constructor injection in services and action method parameters of controllers, binding interfaces to implementations. Applied when moving logic to services, avoiding static calls, and injecting dependencies into controllers, services, and console commands. Key terms — ServiceLocator, DI, services, autowire, PSR-11, dependency injection, container.
---

# ServiceLocator (DI) in Bitrix

`Bitrix\Main\DI\ServiceLocator` is the kernel's PSR-11 container. It should be retrieved via `ServiceLocator::getInstance()`, but directly in application code only where dependencies cannot be injected the usual way (factories, legacy, static context).

## Layer Rules

- Domain does not know about the container.
- Services from `Application/` / `Infrastructure/` receive dependencies **via constructor**.
- Controller receives services **via action parameters** (autowiring) or constructor.
- Event handlers and console commands are created by the container if they are registered in it.

## Service Registration

File `/local/modules/vendor.module/.settings.php`:

```php
<?php
return [
    'services' => [
        'value' => [
            // 1. By string name
            'vendor.module.postService' => [
                'className' => \Vendor\Module\Application\Service\PostService::class,
            ],

            // 2. By FQCN (preferred — less magic, IDE support)
            \Vendor\Module\Application\Service\PostService::class => [
                'className' => \Vendor\Module\Application\Service\PostService::class,
            ],

            // 3. Interface → Implementation
            \Vendor\Module\Domain\Repository\PostRepositoryInterface::class => [
                'className' => \Vendor\Module\Infrastructure\Repository\PostRepository::class,
            ],

            // 4. With constructor parameters
            \Vendor\Module\Infrastructure\Http\TelegramClient::class => [
                'className' => \Vendor\Module\Infrastructure\Http\TelegramClient::class,
                'constructorParams' => static fn () => [
                    'token' => getenv('TELEGRAM_BOT_TOKEN'),
                ],
            ],

            // 5. Closure factory (full control over creation)
            \Psr\Log\LoggerInterface::class => [
                'constructor' => static function (): \Psr\Log\LoggerInterface {
                    return \Vendor\Module\Infrastructure\Logger\LoggerFactory::create();
                },
            ],
        ],
        'readonly' => true,
    ],
];
```

### Modes

- **`className`** — simple registration; the container resolves dependencies via autowire (by FQCN from constructor).
- **`className` + `constructorParams`** — pass scalar parameters.
- **`constructor`** — full control, returns a finished object.

### Global Services

The `services` section can also be used in `/local/.settings.php` — registration does not require a module:

```php
'services' => [
    'value' => [
        'project.featureFlags' => [
            'className' => \App\FeatureFlags::class,
        ],
    ],
    'readonly' => true,
],
```

Modular and global `services` are merged: a module sees its own and global ones.

## Retrieving a Service

### Autowire via Constructor

```php
final class PostService
{
    public function __construct(
        private readonly \Vendor\Module\Domain\Repository\PostRepositoryInterface $posts,
        private readonly \Psr\Log\LoggerInterface $logger,
    ) {}
}
```

Simply registering `PostService` itself is enough — its dependencies will be retrieved from the container by type.

### In a Controller (Bitrix retrieves automatically)

```php
final class Post extends \Bitrix\Main\Engine\Controller
{
    public function __construct(
        private readonly PostService $postService,
    ) {
        parent::__construct();
    }
}
```

### In a Console Command

The container creates the command itself if it's registered in `console.commands`. Constructor dependencies are resolved as usual.

### Explicit Container Access

```php
$sl = \Bitrix\Main\DI\ServiceLocator::getInstance();

if ($sl->has(PostService::class))
{
    /** @var PostService $posts */
    $posts = $sl->get(PostService::class);
}
```

Use only where DI is impossible (init.php, global functions, old events without a handler class).

## Service Overriding

To override a service from another module, register it **again** in your module's `.settings.php` with the same key, **not** marking it as readonly:

```php
'services' => [
    'value' => [
        \Vendor\Blog\Domain\Repository\PostRepositoryInterface::class => [
            'className' => \Vendor\Override\Repository\CachedPostRepository::class,
        ],
    ],
    'readonly' => false,
],
```

> If the original registration is marked `readonly: true`, it cannot be overridden from another module.

## Lifecycle

- Services are **singletons** per process/request. Do not store per-request state in them; use request scope via method parameters.
- In long-running CLI processes (messenger-consumer), avoid global state and memory leaks.

## Antipatterns

- `$service = new PostService(...);` in a controller/command — use DI.
- `ServiceLocator::getInstance()->get(...)` in domain classes — they should not know about the container.
- Registering "config" as a service without a wrapper — pass config as an object/DTO rather than an array.
- Mixing global `\Bitrix\Main\Application::getInstance()->...` via statics instead of injection.

## Checklist

- [ ] All application services are in module `services` or global.
- [ ] The key matches FQCN where possible (autocompletion + clarity).
- [ ] Domain interfaces look at infrastructure implementations only via `ServiceLocator`.
- [ ] There is no `new` for services in controllers and commands.
- [ ] No circular dependencies (the container throws an `Exception` in this case).

## Persistent Storage (main 25.1100+)

`PersistentStorageInterface` is registered in kernel `services`. Retrieve via:

```php
$storage = ServiceLocator::getInstance()
    ->get(\Bitrix\Main\Data\Storage\PersistentStorageInterface::class);
$storage->set('vendor.module.key', $data, 3600);
```

See skill `bitrix-storage` for `DeferredStorageDecorator` and TTL rules.
