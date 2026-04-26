---
name: bitrix-service-locator
description: Покрывает DI-контейнер Bitrix\Main\DI\ServiceLocator (PSR-11) — регистрация сервисов в секции services файла .settings.php модуля, autowire, получение зависимостей через has()/get(), constructor injection в сервисах и параметрах action-методов контроллеров, биндинг интерфейсов на реализации. Применяется при выносе логики в сервисы, отказе от статических вызовов, внедрении зависимостей в контроллеры, сервисы и консольные команды. Ключевые термины — ServiceLocator, DI, services, autowire, PSR-11, dependency injection, container.
---

# ServiceLocator (DI) в Bitrix

`Bitrix\Main\DI\ServiceLocator` — PSR-11 контейнер ядра. Получать его следует через `ServiceLocator::getInstance()`, но напрямую в прикладном коде — только там, где нельзя внедрить зависимость обычным способом (фабрики, легаси, статический контекст).

## Правила слоёв

- Домен не знает о контейнере.
- Сервисы из `Application/` / `Infrastructure/` получают зависимости **через конструктор**.
- Контроллер получает сервисы **через параметры действия** (автосвязывание) или конструктор.
- Обработчики событий и команды консоли создаются контейнером, если они в нём зарегистрированы.

## Регистрация сервисов

Файл `/local/modules/vendor.module/.settings.php`:

```php
<?php
return [
    'services' => [
        'value' => [
            // 1. По имени строкой
            'vendor.module.postService' => [
                'className' => \Vendor\Module\Application\Service\PostService::class,
            ],

            // 2. По FQCN (предпочтительно — меньше магии, поддержка IDE)
            \Vendor\Module\Application\Service\PostService::class => [
                'className' => \Vendor\Module\Application\Service\PostService::class,
            ],

            // 3. Интерфейс → реализация
            \Vendor\Module\Domain\Repository\PostRepositoryInterface::class => [
                'className' => \Vendor\Module\Infrastructure\Repository\PostRepository::class,
            ],

            // 4. С параметрами конструктора
            \Vendor\Module\Infrastructure\Http\TelegramClient::class => [
                'className' => \Vendor\Module\Infrastructure\Http\TelegramClient::class,
                'constructorParams' => static fn () => [
                    'token' => getenv('TELEGRAM_BOT_TOKEN'),
                ],
            ],

            // 5. Фабрика-замыкание (полный контроль над созданием)
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

### Режимы

- **`className`** — простая регистрация; контейнер сам разрешает зависимости через autowire (по FQCN из конструктора).
- **`className` + `constructorParams`** — передать скалярные параметры.
- **`constructor`** — полный контроль, возвращает готовый объект.

### Глобальные сервисы

В `/local/.settings.php` секцию `services` тоже можно использовать — регистрация не требует модуля:

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

Модульные и глобальные `services` объединяются: модуль видит свои и глобальные.

## Получение сервиса

### Autowire через конструктор

```php
final class PostService
{
    public function __construct(
        private readonly \Vendor\Module\Domain\Repository\PostRepositoryInterface $posts,
        private readonly \Psr\Log\LoggerInterface $logger,
    ) {}
}
```

Достаточно зарегистрировать сам `PostService` — его зависимости будут получены из контейнера по типам.

### В контроллере (Bitrix получит автоматически)

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

### В консольной команде

Контейнер создаёт команду сам, если она зарегистрирована в `console.commands`. Конструкторные зависимости резолвятся как обычно.

### Явное обращение к контейнеру

```php
$sl = \Bitrix\Main\DI\ServiceLocator::getInstance();

if ($sl->has(PostService::class))
{
    /** @var PostService $posts */
    $posts = $sl->get(PostService::class);
}
```

Используй только там, где DI невозможна (init.php, глобальные функции, старые события без класса-обработчика).

## Подмена сервиса

Чтобы переопределить сервис из другого модуля, зарегистрируй его **повторно** в `.settings.php` своего модуля с тем же ключом, **не** помечая readonly:

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

> Если оригинальная регистрация помечена `readonly: true`, переопределить её из другого модуля нельзя.

## Жизненный цикл

- Сервисы — **singleton** на процесс/запрос. Не храни в них per-request состояние; используй request-скоуп через параметры методов.
- В CLI-процессах, живущих долго (messenger-consumer), избегай глобального состояния и утечек памяти.

## Антипаттерны

- `$service = new PostService(...);` в контроллере/команде — делай через DI.
- `ServiceLocator::getInstance()->get(...)` в доменных классах — они не должны знать о контейнере.
- Регистрация «конфига» как сервиса без обёртки — передавай конфиг как объект/DTO, а не массив.
- Подмешивание глобальных `\Bitrix\Main\Application::getInstance()->...` через статику вместо инъекции.

## Чек-лист

- [ ] Все прикладные сервисы — в `services` модуля или глобально.
- [ ] Ключ совпадает с FQCN там, где это возможно (автодополнение + понятность).
- [ ] Интерфейсы домена смотрят на реализации из инфраструктуры только через `ServiceLocator`.
- [ ] В контроллерах и командах нет `new` для сервисов.
- [ ] Нет циклических зависимостей (контейнер при этом бросает `Exception`).
