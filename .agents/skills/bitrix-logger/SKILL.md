---
name: bitrix-logger
description: Covers PSR-3 logging in Bitrix — Bitrix\Main\Diag\Logger, FileLogger, SysLogger, NullLogger, LogFormatter, loggers section in .settings.php, named kernel loggers (main.HttpClient, main.Default, main.Mail, main.Engine), integration with Monolog and third-party PSR-3 loggers. Applied when configuring module logs, debugging integrations, gathering errors from specific kernel components and log rotation. Key terms — Logger, FileLogger, SysLogger, LogFormatter, PSR-3, Monolog, loggers config, log level.
---

# Logging in Bitrix (PSR-3)

Bitrix follows the PSR-3 standard. In code, inject `\Psr\Log\LoggerInterface`, and in `.settings.php`, configure the specific implementation. Direct calls to `AddMessage2Log` are legacy; in new code, write via DI logger.

## Built-in Implementations

All are in the `\Bitrix\Main\Diag\` namespace:

| Class | Purpose |
| --- | --- |
| `Logger` | Abstract base class; `Logger::create('id', $params)` creates a logger via factory |
| `FileLogger` | Into a file, with auto-rotation when `$maxLogSize` is exceeded (default 1 MB) |
| `SysLogger` | Into system `syslog` via `openlog`/`syslog` |
| `EventLogger` | Into `b_event_log` table (Admin Panel → Event Log) |
| `LogFormatter` | Default formatter: interpolates `{placeholder}`, renders exceptions and stacks |
| `JsonLinesFormatter` | From 25.300.0; one JSON line per entry, convenient for ELK/Loki |

Levels are constants of `\Psr\Log\LogLevel::*` (`emergency`, `alert`, `critical`, `error`, `warning`, `notice`, `info`, `debug`).

## Service with Logger (DI — Recommended)

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

Registration in `/local/modules/vendor.module/.settings.php`:

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

## PSR-3 Placeholders

Message is a template with `{key}`, values are taken from `$context`:

```php
$logger->warning('User {userId} tried {action} on post {postId}', [
    'userId' => $uid, 'action' => 'delete', 'postId' => $pid,
]);
```

Special keys understood by `LogFormatter`:

- `{date}` — current time (interpolated automatically).
- `{host}` — HTTP_HOST (automatic).
- `{delimiter}` — entry separator (automatic).
- `{exception}` — `\Throwable` object → formats class, message, stack trace.
- `{trace}` — manual stack trace: `Diag\Helper::getBackTrace(6, DEBUG_BACKTRACE_IGNORE_ARGS, 3)`.

Enable arguments in stack trace:

```php
$logger->setFormatter(new \Bitrix\Main\Diag\LogFormatter(showArguments: true, argMaxChars: 120));
```

## Configuration via `.settings.php` — `loggers` section

Allows overriding loggers for named kernel points (`main.HttpClient`, `main.Default`, `main.GeoIpManager`) and your own identifiers.

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

### Important

- `constructor` closures must be in `.settings.php` / `.settings_extra.php` — the file **is not edited** by Admin Panel, closures are not serialized.
- `level` — threshold level; logger ignores messages below this.
- `formatter` — key from `services` section.
- Retrieving logger in code:

    ```php
    $logger = \Bitrix\Main\Diag\Logger::create('vendor.module.myLogger');
    $logger = \Bitrix\Main\Diag\Logger::create('vendor.module.myLogger', [$this, $extraArg]);
    ```

## Named Kernel Points

| ID | Used In | Factory Parameters |
| --- | --- | --- |
| `main.Default` | `AddMessage2Log`, `CEventLog`, general default | `LOG_FILENAME`, `$showArgs` |
| `main.HttpClient` | `Bitrix\Main\Web\HttpClient` (including legacy and PSR-18) | `DebugInterface $debug`, `RequestInterface $request` |
| `main.GeoIpManager` | `Bitrix\Main\Service\GeoIp\Manager` | — |

Configuring these loggers redirects all kernel calls — convenient for auditing external calls (see example in `bitrix-http-client`).

## LoggerAware + Factory

For classes that should be supplied with a logger "by identifier":

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

## Monolog via Composer

```bash
composer require monolog/monolog
```

Integration into `.settings.php`:

```php
'loggers' => [
    'value' => [
        'vendor.module.external' => [
            'constructor' => static function () {
                $log = new \Monolog\Logger('vendor.module');
                $log->pushHandler(new \Monolog\Handler\StreamHandler('/var/log/bitrix/monolog.log'));
                return $log;
            },
            'level' => \Psr\Log\LogLevel::DEBUG,
        ],
    ],
],
```

## Checklist

- [ ] PSR-3 standard followed (placeholders, context, exception key).
- [ ] Loggers are configured via `.settings.php` rather than hardcoded in services.
- [ ] Threshold `level` is set for each environment.
- [ ] Loggers for external integrations (`HttpClient`) are redirected to separate files for audit.
- [ ] For heavy load, `JsonLinesFormatter` is used for external collectors.
- [ ] Logs are stored outside `DOCUMENT_ROOT` or protected by `.htaccess`.
- [ ] Sensitive data (passwords, tokens) are stripped from context before logging.

Link `exception_handling.log` in `.settings.php` with named loggers for unified error tracking. See skill `bitrix-settings`.
