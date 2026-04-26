---
name: bitrix-logger
description: Покрывает PSR-3 логирование в Bitrix — Bitrix\Main\Diag\Logger, FileLogger, SysLogger, NullLogger, LogFormatter, секция loggers в .settings.php, именованные логгеры ядра (main.HttpClient, main.Default, main.Mail, main.Engine), интеграция с Monolog и сторонними PSR-3 логгерами. Применяется при настройке логов модуля, отладке интеграций, сборе ошибок с конкретных компонентов ядра и ротации логов. Ключевые термины — Logger, FileLogger, SysLogger, LogFormatter, PSR-3, Monolog, loggers config, log level.
---

# Логирование в Bitrix (PSR-3)

Bitrix следует стандарту PSR-3. В коде инжектируй `\Psr\Log\LoggerInterface`, а в `.settings.php` настраивай конкретную реализацию. Прямой вызов `AddMessage2Log` — легаси; в новом коде пиши через DI-логгер.

## Встроенные реализации

Все в неймспейсе `\Bitrix\Main\Diag\`:

| Класс | Назначение |
| --- | --- |
| `Logger` | Абстрактный базовый класс; `Logger::create('id', $params)` создаёт логгер через фабрику |
| `FileLogger` | В файл, с авторотацией при превышении `$maxLogSize` (по умолчанию 1 МБ) |
| `SysLogger` | В системный `syslog` через `openlog`/`syslog` |
| `EventLogger` | В таблицу `b_event_log` (админка → Журнал событий) |
| `LogFormatter` | Форматтер по умолчанию: подставляет `{placeholder}`, рендерит исключения и стеки |
| `JsonLinesFormatter` | С 25.300.0; по строке JSON на запись, удобно для ELK/Loki |

Уровни — константы `\Psr\Log\LogLevel::*` (`emergency`, `alert`, `critical`, `error`, `warning`, `notice`, `info`, `debug`).

## Сервис с логгером (DI — рекомендуется)

```php
<?php declare(strict_types=1);

namespace Vendor\Module\Application\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class PostService
{
    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function publish(int $postId): void
    {
        try
        {
            // ...
            $this->logger->info('Post {id} published', ['id' => $postId]);
        }
        catch (\Throwable $e)
        {
            $this->logger->error('Publish failed for post {id}: {exception}', [
                'id' => $postId,
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
```

Регистрация в `/local/modules/vendor.module/.settings.php`:

```php
'services' => [
    'value' => [
        \Vendor\Module\Application\Service\PostService::class => [
            'constructor' => static fn (): \Vendor\Module\Application\Service\PostService =>
                new \Vendor\Module\Application\Service\PostService(
                    new \Bitrix\Main\Diag\FileLogger('/var/log/bitrix/post-service.log'),
                ),
        ],
    ],
    'readonly' => true,
],
```

## Плейсхолдеры PSR-3

Сообщение — шаблон с `{key}`, значения берутся из `$context`:

```php
$logger->warning('User {userId} tried {action} on post {postId}', [
    'userId' => $uid, 'action' => 'delete', 'postId' => $pid,
]);
```

Специальные ключи, которые понимает `LogFormatter`:

- `{date}` — текущее время (подставляется автоматически).
- `{host}` — HTTP_HOST (автоматически).
- `{delimiter}` — разделитель записей (автоматически).
- `{exception}` — объект `\Throwable` → форматируется класс, сообщение, стек.
- `{trace}` — ручной стек: `Diag\Helper::getBackTrace(6, DEBUG_BACKTRACE_IGNORE_ARGS, 3)`.

Включить аргументы в стеке:

```php
$logger->setFormatter(new \Bitrix\Main\Diag\LogFormatter(showArguments: true, argMaxChars: 120));
```

## Настройка через `.settings.php` — секция `loggers`

Позволяет переопределять логгеры именованных точек ядра (`main.HttpClient`, `main.Default`, `main.GeoIpManager`) и собственные идентификаторы.

```php
return [
    'services' => [
        'value' => [
            'formatter.withArgs' => [
                'className' => \Bitrix\Main\Diag\LogFormatter::class,
                'constructorParams' => [true],
            ],
        ],
        'readonly' => true,
    ],
    'loggers' => [
        'value' => [
            'main.Default' => [
                'constructor' => static fn () => new \Bitrix\Main\Diag\FileLogger(
                    '/var/log/bitrix/app.log', 10 * 1024 * 1024,
                ),
                'level'     => \Psr\Log\LogLevel::INFO,
                'formatter' => 'formatter.withArgs',
            ],

            'main.HttpClient' => [
                'constructor' => static function (
                    \Bitrix\Main\Web\Http\DebugInterface $debug,
                    \Psr\Http\Message\RequestInterface $request,
                ) {
                    $debug->setDebugLevel(\Bitrix\Main\Web\HttpDebug::ALL);
                    return new \Bitrix\Main\Diag\FileLogger(
                        '/var/log/bitrix/http-' . spl_object_hash($request) . '.log',
                    );
                },
                'level' => \Psr\Log\LogLevel::DEBUG,
            ],

            'vendor.module.myLogger' => [
                'constructor' => static fn () => new \Bitrix\Main\Diag\FileLogger(
                    '/var/log/bitrix/vendor.module.log',
                ),
                'level' => \Psr\Log\LogLevel::DEBUG,
            ],
        ],
        'readonly' => true,
    ],
];
```

### Важное

- Замыкания-`constructor` должны лежать в `.settings.php` / `.settings_extra.php` — файл **не редактируется** админкой, замыкание не сериализуется.
- `level` — пороговый уровень; ниже него логгер игнорирует сообщения.
- `formatter` — ключ из секции `services`.
- Получение логгера в коде:

    ```php
    $logger = \Bitrix\Main\Diag\Logger::create('vendor.module.myLogger');
    $logger = \Bitrix\Main\Diag\Logger::create('vendor.module.myLogger', [$this, $extraArg]);
    ```

## Именованные точки ядра

| ID | Где используется | Параметры фабрики |
| --- | --- | --- |
| `main.Default` | `AddMessage2Log`, `CEventLog`, общий дефолт | `LOG_FILENAME`, `$showArgs` |
| `main.HttpClient` | `Bitrix\Main\Web\HttpClient` (включая legacy и PSR-18) | `DebugInterface $debug`, `RequestInterface $request` |
| `main.GeoIpManager` | `Bitrix\Main\Service\GeoIp\Manager` | — |

Настройка этих логгеров перенаправляет все вызовы ядра — удобно для аудита внешних обращений (см. пример в `bitrix-http-client`).

## LoggerAware + фабрика

Для классов, которые нужно снабжать логгером «по идентификатору»:

```php
final class Indexer implements \Psr\Log\LoggerAwareInterface
{
    use \Psr\Log\LoggerAwareTrait;

    public function run(): void
    {
        $this->ensureLogger()->info('Indexing started');
    }

    private function ensureLogger(): \Psr\Log\LoggerInterface
    {
        if ($this->logger === null)
        {
            $this->setLogger(\Bitrix\Main\Diag\Logger::create('vendor.module.indexer', [$this]));
        }
        return $this->logger;
    }
}
```

## Monolog через Composer

```bash
composer require monolog/monolog
```

```php
'services' => [
    'value' => [
        \Vendor\Module\Application\Service\PostService::class => [
            'constructor' => static function (): \Vendor\Module\Application\Service\PostService
            {
                $logger = new \Monolog\Logger('vendor.module');
                $logger->pushHandler(new \Monolog\Handler\StreamHandler('/var/log/bitrix/post.log', \Monolog\Logger::INFO));
                $logger->pushHandler(new \Monolog\Handler\ErrorLogHandler());
                return new \Vendor\Module\Application\Service\PostService($logger);
            },
        ],
    ],
    'readonly' => true,
],
```

Dev-окружению подойдёт `BrowserConsoleHandler` + `FingersCrossedHandler` на ошибках.

## `NullLogger` vs `if ($logger)`

Используй `Psr\Log\NullLogger` как дефолт в конструкторе — не нужно проверять `null` перед вызовом. Сообщение и контекст формируются, даже если никуда не пишутся (не клади тяжёлые вычисления без проверки уровня).

Проверка уровня (чтобы не строить дорогие строки):

```php
if ($this->logger instanceof \Psr\Log\LoggerInterface
    && !$this->logger instanceof \Psr\Log\NullLogger)
{
    $this->logger->debug('Heavy dump: {data}', ['data' => $this->buildDump()]);
}
```

## Контекст и чувствительные данные

- Не клади токены/пароли в `$message` — только в `$context` с маскированием.
- Для структурированных агрегаторов (ELK, Loki) — `JsonLinesFormatter` + поля в `$context`.
- Ротацию файлов на продакшене лучше отдать `logrotate`, а `FileLogger::$maxLogSize = 0`.

## Fatal / необработанные исключения

Это **не** секция `loggers`. Конфигурируются в `exception_handling` внутри `.settings.php`:

```php
'exception_handling' => [
    'value' => [
        'debug' => false,         // true только на dev!
        'log' => [
            'settings' => ['file' => '/var/log/bitrix/exceptions.log'],
            'class_name' => \Bitrix\Main\Diag\FileExceptionHandlerLog::class,
        ],
    ],
    'readonly' => false,
],
```

## Антипаттерны

- `error_log(...)`/`echo` в проде — не попадает в централизованный сбор.
- `AddMessage2Log($m)` без контекста; предпочти PSR-3 и структуру `context`.
- Пароли/PII в сообщении лога.
- `debug => true` в `exception_handling` на проде.
- Логирование в цикле без уровня (`$logger->debug(...)` для сотен записей без `FingersCrossedHandler` / level-gate).

## Чек-лист

- [ ] Сервисы получают `LoggerInterface` через конструктор, дефолт — `NullLogger`.
- [ ] В `.settings.php` секция `loggers` задаёт уровень и формат для `main.Default`, `main.HttpClient` и собственных id.
- [ ] Сообщения содержат `{placeholder}` + `$context`, а не конкатенацию.
- [ ] Исключения логируются с `'exception' => $e` — `LogFormatter` сам отрендерит стек.
- [ ] Секреты и PII не попадают в лог.
- [ ] Ротация настроена — либо `FileLogger` с `$maxLogSize`, либо внешний `logrotate`.
