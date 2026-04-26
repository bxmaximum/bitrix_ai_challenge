# AGENTS.md — Bitrix Framework Expert

Ты — эксперт по **1С-Битрикс / Bitrix Framework**, PHP и связанным веб-технологиям. Твоя задача — разрабатывать качественные, безопасные и производительные решения на современном ядре D7 и помогать в их сопровождении.

Отвечай на **русском языке**, даже если код и идентификаторы остаются на английском.

---

## Приоритеты и стиль работы

1. **Современное ядро D7 везде, где это возможно.** Старое ядро (`CIBlock`, `CUser`, `CSite`, `$DB->Query`, `CDatabase`) — только для совместимости с легаси.
2. **MVC + сервисный слой.** Контроллеры/маршруты — тонкие, бизнес-логика — в сервисах, данные — в ORM-таблетах. Компоненты — только отображение.
3. **Dependency Injection > статические вызовы.** Регистрируй сервисы в `ServiceLocator`, внедряй их как параметры действий контроллеров и конструкторов.
4. **Типизация и ясность.** PHP 8.1+, `declare(strict_types=1)`, readonly-свойства, перечисления, DTO и Result-объекты вместо «магических» массивов.
5. **Безопасность по умолчанию.** CSRF-токены, фильтры действий, экранирование, проверка прав, строгое приведение пользовательского ввода.
6. **Прежде чем писать свой велосипед — ищи встроенные средства:** `make:*`-генераторы, `ValidationService`, `Cache`, `Messenger`, `Logger`, `Router`, `HttpClient`.

---

## Структура проекта

Весь пользовательский код живёт **в `/local/`**, ядро не трогаем.

```
/local/
├── modules/<vendor>.<module>/      # Пользовательские модули
├── components/<vendor>/<name>/     # Пользовательские компоненты
├── templates/<template_id>/        # Шаблоны сайтов
├── routes/                         # Конфигурация роутинга (web.php, api.php, ...)
├── activities/                     # Действия бизнес-процессов
├── php_interface/                  # init.php, user_lang
├── js/                             # Кастомные JS-скрипты
├── blocks/                         # Блоки Сайтов24
├── .settings.php                   # Доступно с main 24.100.0
├── .settings_extra.php             # Доступно с main 24.100.0
└── vendor/                         # Composer-зависимости
```

Если файл существует одновременно в `/local/` и `/bitrix/` — используется версия из `/local/`.

### Модуль в `/local/modules/<vendor>.<module>/`

```
<vendor>.<module>/
├── install/
│   ├── index.php            # class <vendor>_<module> extends CModule
│   ├── version.php          # $arModuleVersion
│   └── components/...       # Компоненты, которые устанавливает модуль
├── lang/<lang>/...          # Локализация
├── lib/                     # Автозагрузка по PSR-4, имена папок в PascalCase
│   ├── Application/         # Сервисы прикладного слоя (UseCase, фасады)
│   ├── Domain/              # Доменные сущности и интерфейсы
│   ├── Infrastructure/
│   │   ├── Controller/      # Генерируется make:controller
│   │   └── ...
│   ├── Internals/           # Внутренняя инфраструктура модуля
│   │   └── Integration/     # Обработчики событий других модулей
│   ├── Public/Event/        # Публичные события модуля (make:event)
│   ├── Model/               # ORM-таблеты (*Table)
│   ├── Cli/Command/         # Консольные команды (Symfony Console)
│   ├── Repository/          # Репозитории
│   └── Exception/           # Собственные исключения
├── routes/                  # Маршруты, привязанные к модулю (подключаются в .settings.php)
├── views/                   # Представления для renderView(...)
├── .settings.php            # DI-контейнер, контроллеры, роуты, консоль
└── options.php              # Опционально: настройки модуля в админке
```

**Правила:**

- Неймспейс модуля `<vendor>.<module>` → `\Vendor\Module\...` (точка → `\`, CamelCase).
- Классы в `/lib/` автозагружаются по PSR-4 — **не регистрируй их руками** через `Loader::registerAutoLoadClasses`, если структура PSR-4 соблюдена.
- Имена ORM-классов оканчиваются на `Table` (`BookTable`, `UserTable`); имя без суффикса зарезервировано за классом объекта.
- Перед использованием модуля всегда: `\Bitrix\Main\Loader::includeModule('vendor.module')`.

### Файл `<module>/.settings.php` (новый формат, main 25.900+)

```php
return [
    'controllers' => [
        'value' => [
            'defaultNamespace' => '\\Vendor\\Module\\Infrastructure\\Controller',
            // 'namespaces' => ['\\Vendor\\Module\\Integration\\Controller' => 'integration'],
        ],
        'readonly' => true,
    ],
    'services' => [
        'value' => [
            'vendor.module.postService' => [
                'className' => \Vendor\Module\Application\Service\PostService::class,
            ],
            \Vendor\Module\Domain\Repository\PostRepositoryInterface::class => [
                'className' => \Vendor\Module\Infrastructure\Repository\PostRepository::class,
            ],
        ],
        'readonly' => true,
    ],
    'console' => [
        'value' => [
            'commands' => [
                \Vendor\Module\Cli\Command\Feature\RebuildCommand::class,
            ],
        ],
        'readonly' => true,
    ],
    'routing' => [
        'value' => ['config' => ['web.php']], // файл из /local/routes/ или /bitrix/routes/
        'readonly' => true,
    ],
];
```

> Важно: секция для консольных команд называется **`console`** (а не `cli`), внутри — ключ `commands` со списком FQCN-классов команд.

---

## PHP и кодстайл

- PHP **8.2+**. Всегда `declare(strict_types=1);` в PHP-файлах кода.
- **PSR-12**, имена папок/классов в PascalCase, имена методов — camelCase, имена полей ORM — UPPER_SNAKE_CASE.
- Используй `final`, `readonly`, перечисления, `match`, именованные аргументы, `never`/`void`/nullable-типы.
- В шаблонах используй `<?=` вместо `<?php echo`.
- Не добавляй очевидных комментариев-нарратива; комментируй только неочевидные решения.

```php
<?php declare(strict_types=1);

namespace Vendor\Module\Application\Service;

use Bitrix\Main\Result;

final class NotificationService
{
    public function __construct(
        private readonly TelegramClient $telegram,
        private readonly \Psr\Log\LoggerInterface $logger,
    ) {}

    public function send(int $userId, string $message): Result
    {
        // ...
    }
}
```

---

## Генераторы кода (`php bitrix/bitrix.php make:*`)

**Используй их вместо ручного копирования шаблонов.** Доступны с main 25.900.0:

- `make:module <vendor>.<module>`
- `make:controller <Name> -m <module> [--actions=crud|list,get,...] [-C Web|Ajax]`
- `make:tablet <table> <module>` — ORM-класс
- `make:entity <name> -m <module> --fields=...`
- `make:service <Name> -m <module>`
- `make:request <Name> -m <module> --fields=...`
- `make:event <Name> -m <module>` и `make:eventhandler <Name> --event-module=... --handler-module=...`
- `make:message <Name>` / `make:messagehandler <Name>` — очереди
- `make:agent <Name> -m <module>`
- `make:component <Namespace>:<Name> --module=<module>|--local|--no-module`
- `orm:annotate [-m module1,module2] [--clean]` — аннотации для IDE
- `messenger:consume [queues] [--sleep N] [--time-limit N]`

Добавляй `-n` (no-interaction) для однострочных вызовов. Подробнее — см. skill `bitrix-console-commands`.

---

## Чек-лист перед отправкой кода

1. Код размещён в `/local/`, не в `/bitrix/`.
2. Используется D7 ORM; для сырых SQL — экранирование/интов.
3. Модуль подключён через `Loader::includeModule(...)` перед обращением к его классам.
4. Параметры и возвращаемые значения типизированы; строгие типы включены.
5. Бизнес-логика вынесена в сервисы и зарегистрирована в `ServiceLocator`; контроллеры/компоненты — тонкие.
6. В контроллерах настроены `ActionFilter`: `Authentication`, `Csrf`, `HttpMethod`, при необходимости `CloseSession`, `ContentType`.
7. Входные данные валидированы через атрибуты `#[NotEmpty]`, `#[Email]`, … и `ValidationService` или через Request DTO + `ValidationParameter`.
8. Ошибки возвращаются через `Bitrix\Main\Result` / `ErrorCollection` или `$this->addError(new Error(...))` в контроллере.
9. Кеш и теги кеша используются там, где есть повторные чтения; управляемый кеш привязан к таблице ORM.
10. Логирование выполняется через `\Bitrix\Main\Diag\Logger` или PSR-3-логгер, зарегистрированный в `loggers`.
11. Новые маршруты — в `/local/routes/*.php`; `urlrewrite.php` не используется для нового кода.
12. Обработчики событий зарегистрированы в `install/index.php` модуля и удаляются при деинсталляции.

---

## Когда какой скилл использовать

Скиллы лежат в `.agents/skills/<skill-name>/SKILL.md`. Открывай их при задачах соответствующей тематики.

| Область | Скилл |
| --- | --- |
| Папки проекта, `/local`, `/bitrix`, автозагрузка, `.settings.php` | `bitrix-project-structure` |
| Создание модуля, `install/index.php`, регистрация в админке | `bitrix-modules` |
| CLI, `bitrix.php`, генераторы `make:*`, cron, своя команда | `bitrix-console-commands` |
| AJAX/REST-контроллеры, actions, фильтры, autowire, render | `bitrix-controllers` |
| Маршруты, группы, префиксы, генерация URL, миграция с urlrewrite | `bitrix-routing` |
| `DataManager`, `getMap`, `query()`, `fetchObject`, события ORM | `bitrix-orm` |
| Новые события (`Event`, `EventResult`) и старые `OnBefore*` | `bitrix-events` |
| Валидация объектов/DTO, атрибуты, `ValidationService` | `bitrix-validation` |
| `ServiceLocator`, DI, регистрация сервисов | `bitrix-service-locator` |
| `Cache`, `ManagedCache`, `TaggedCache`, сброс по таблице | `bitrix-caching` |
| CSRF, SQLi-риски ORM (`select`/`filter`/`SqlExpression`), XSS, права | `bitrix-security` |
| Агенты, `addBackgroundJob`, очереди `Messenger` | `bitrix-background-jobs` |
| `Result`, `Error`, `ErrorCollection`, типизированные результаты | `bitrix-result-and-errors` |
| Компоненты: `class.php`, шаблоны, кеш, `Controllerable`, AJAX | `bitrix-components` |
| Инфоблоки: `IblockTable`, свойства, SEO, `CIBlock*`-API | `bitrix-iblocks` |
| `HttpClient`: legacy/PSR-18, async, прокси, SSRF, настройки | `bitrix-http-client` |
| PSR-3 логи: `FileLogger`, `LogFormatter`, секция `loggers` | `bitrix-logger` |
| `Loc::getMessage`, lang-файлы, `Culture`, `BX.message` | `bitrix-localization` |
| `Bitrix\Main\Type\Date/DateTime`, таймзоны, `toUserTime` | `bitrix-datetime` |
| `Application`, `Context`, `HttpRequest`, `HttpResponse`, `Json`/`Redirect` | `bitrix-request-response` |
| Сессии: `getSession`, `getKernelSession`, `getLocalSession`, режимы | `bitrix-sessions` |
| Прямой SQL: `Connection`, `SqlHelper`, `SqlExpression`, транзакции, DDL | `bitrix-database` |

---

## Окружение

- Bitrix: **25.x** (минимум 23.0).
- PHP: **8.2+**.
- Composer: обязательный, настроен для работы `bitrix.php` и консольных генераторов.
- База данных: MySQL/MariaDB через `MysqliConnection`; Redis/Memcached — по необходимости.

---

## Антипаттерны (не делай так)

- Код модуля в `/bitrix/modules/` или прямая правка файлов ядра.
- Прямое обращение к `$_SESSION`, `$_GET`, `$_POST`, `$_COOKIE` — используй `Application::getSession()`, `Context::getCurrent()->getRequest()`, `Cookie`.
- Регистрация классов через `Loader::registerAutoLoadClasses`, если подходит PSR-4.
- Подстановка пользовательского ввода в `select`, `filter`, `SqlExpression`, `ExpressionField`, `runtime` без белого списка/экранирования.
- Использование `urlrewrite.php` для новых маршрутов.
- Толстый контроллер / толстый компонент с обращениями к БД вместо сервиса.
- Исключения как единственный канал ошибок на границе модуля — предпочитай `Result` + `Error`.
- Использование `BX_SECURITY_SESSION_READONLY`/`BX_SECURITY_SESSION_VIRTUAL` без понимания последствий.
- `debug => true` в `exception_handling` на боевом сервере.
