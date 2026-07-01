---
name: bitrix-background-jobs
description: Covers background tasks and deferred processing in Bitrix — CAgent agents, Application::addBackgroundJob(), Messenger queues (brokers/queues, messenger:consume, AbstractMessage, AbstractReceiver). Applied when designing cron tasks, deferred integrations, mailings, and choosing between an agent, background job, and queue. Key terms — agent, CAgent, addBackgroundJob, Messenger, queue, broker, consumer, delayed job, cron.
---

# Background Tasks in Bitrix

Three ways of deferred work — each has its own niche:

| Mechanism | When to Use | Where It Lives |
| --- | --- | --- |
| `CAgent` | Periodic tasks (cleanup, synchronization, reminders) | DB + hit/cron |
| `Application::addBackgroundJob()` | Small work **after** sending the response within the same process | PHP-FPM, same request |
| `Messenger` (queues) | Long-running/reliable tasks with parallelism and retries support | `web` background jobs or `messenger:consume` CLI |

## Agents (`CAgent`)

```php
\CAgent::AddAgent(
    name: \Vendor\Module\Cli\Agent\QueueAgent::class . '::run();',
    module: 'vendor.module',
    period: 'N',        // 'Y' — periodic (always by interval), 'N' — shift next_exec
    interval: 300,      // seconds
    datecheck: '',
    active: 'Y',
    next_exec: '',
    sort: 100,
    existError: true,
);
```

Agent method:

```php
namespace Vendor\Module\Cli\Agent;

final class QueueAgent
{
    public static function run(): string
    {
        \Bitrix\Main\Loader::includeModule('vendor.module');
        \Bitrix\Main\DI\ServiceLocator::getInstance()
            ->get(\Vendor\Module\Application\Service\QueueProcessor::class)
            ->processBatch(limit: 100);

        return self::class . '::run();'; // important: return string for re-registration
    }
}
```

### Rules

- An agent works either on hits or via cron (Admin Panel → Agent Settings).
- For heavy agents **always** enable cron — otherwise they block user hits.
- An agent running longer than 10 minutes is blocked by the kernel.
- Periodic (`period = 'Y'`) vs non-periodic (`period = 'N'`) agents differ in how `next_exec` is calculated.
- In module's `DoUninstall`: `CAgent::RemoveModuleAgents('vendor.module')`.
- Do not keep state in statics between calls — the process may change.
- Combine `addBackgroundJob` for immediate post-response work with `CAgent` for scheduled retries.

### One-time task for "in 5 minutes"

```php
\CAgent::AddAgent(
    \Vendor\Module\Cli\Agent\SendEmailAgent::class . "::run({$userId});",
    'vendor.module',
    'N',
    60,
    '',
    'Y',
    (new \Bitrix\Main\Type\DateTime())->add('+5 minutes')->toString(),
);
```

## `Application::addBackgroundJob()`

Deferred call **after** sending the response (before `fastcgi_finish_request` / in `onAfterEpilog`). Ideal for metrics, welcome emails, or other short tail work.

```php
\Bitrix\Main\Application::getInstance()->addBackgroundJob(
    function () use ($userId) {
        \Vendor\Module\Application\Service\Notifier::fromContainer()->sendWelcome($userId);
    },
    priority: 0,
);
```

### Constraints

- Still a single PHP process. Long tasks degrade worker release time.
- No delivery guarantee: if the process crashes — the task won't execute.
- Not suitable if retries and parallelism are needed — use `Messenger`.

## Messenger (Message Queues)

> **Alpha status** (main 25.100.300+): API may change without backward compatibility guarantees. Use with caution in production.

Queue = logical channel from sender to handler. Message → broker → receiver processes it.

Components: **message** (DTO), **handler** (`AbstractReceiver`), **broker** (storage), **queue** (named handler binding).

### 1. Message (DTO)

```bash
php bitrix/bitrix.php make:message SendWelcomeEmail -m vendor.module
```

```php
namespace Vendor\Module\Public\Message;

use Bitrix\Main\Messenger\Entity\AbstractMessage;
use Bitrix\Main\Messenger\Entity\MessageInterface;

final class SendWelcomeEmailMessage extends AbstractMessage
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
    ) {}

    public static function createFromData(array $data): MessageInterface
    {
        return new self(...$data);
    }
}
```

Requirements:
- JSON-serializable data only: `string`, `int`, `float`, `bool`, `array`.
- Implement `jsonSerialize()` for complex structures.
- Include all data needed at processing time (entity may be deleted before delayed handling).

### 2. Handler

```bash
php bitrix/bitrix.php make:messagehandler SendWelcomeEmail \
    --event-module=vendor.module --handler-module=vendor.module
```

```php
namespace Vendor\Module\Internals\Messenger\Receiver;

use Bitrix\Main\Messenger\Entity\MessageInterface;
use Bitrix\Main\Messenger\Receiver\AbstractReceiver;
use Vendor\Module\Public\Message\SendWelcomeEmailMessage;

final class SendWelcomeEmailHandler extends AbstractReceiver
{
    public function __construct(
        private readonly \Vendor\Module\Application\Service\Mailer $mailer,
    ) {
        parent::__construct();
    }

    protected function process(MessageInterface $message): void
    {
        if (!$message instanceof SendWelcomeEmailMessage) {
            throw new \Bitrix\Main\Messenger\Internals\Exception\UnprocessableMessageException(
                $message->getId(),
                $this->queueId,
            );
        }

        $this->mailer->sendWelcome($message->userId, $message->email);
    }
}
```

Handler rules:
- Extend `AbstractReceiver`, implement **`protected function process()`** (not `handle()`).
- Return `void` on success; throw on failure.
- Exception types: `UnprocessableMessageException` (wrong message type), `UnrecoverableMessageException` (no retry), `RecoverableMessageException` (temporary, optional `getRetryDelay()`).

### 3. Dispatching

```php
$message = new SendWelcomeEmailMessage($userId, $email);
$message->send('vendor_module_queue');

// Delayed processing (1 hour):
use Bitrix\Main\Messenger\Entity\ProcessingParam\DelayParam;
use Bitrix\Main\Messenger\Entity\ProcessingParam\ItemIdParam;

$message->send('vendor_module_queue', [
    new DelayParam(3600),
    new ItemIdParam('welcome-' . $userId),
]);
```

Do **not** use `MessageBus::dispatch()` — the current API is `$message->send('queue_name')`.

### 4. Configuration in `.settings.php`

Global config (`/bitrix/.settings.php` or `/local/.settings.php`) — brokers and cross-module queues:

```php
'messenger' => [
    'value' => [
        'run_mode' => 'web', // 'web' — background jobs on hit; 'cli' — requires messenger:consume
        'brokers' => [
            'default' => [
                'type' => 'db',
                'params' => [
                    'table' => \Bitrix\Main\Messenger\Internals\Storage\Db\Model\MessengerMessageTable::class,
                ],
            ],
        ],
        'queues' => [
            'vendor_module_queue' => [
                'handler' => \Vendor\Module\Internals\Messenger\Receiver\SendWelcomeEmailHandler::class,
            ],
        ],
    ],
    'readonly' => true,
],
```

Module config (`/local/modules/vendor.module/.settings.php`) — module-specific queues:

```php
'messenger' => [
    'value' => [
        'queues' => [
            'vendor_module_queue' => [
                'handler' => \Vendor\Module\Internals\Messenger\Receiver\SendWelcomeEmailHandler::class,
                'limit' => 10,                    // messages per batch (default 50)
                'total_processing_limit' => 50,     // max concurrent (must be >= limit)
                'retry_strategy' => [
                    'max_retries' => 3,
                    'delay' => 5,
                    'multiplier' => 2,
                    'max_delay' => 300,
                ],
            ],
        ],
    ],
    'readonly' => true,
],
```

Notes:
- Only broker type **`db`** is supported currently (not Redis/Doctrine DSN).
- The `default` broker must always exist in global config.
- Put queues in the module `.settings.php` they belong to; global config only for cross-module queues.
- Custom broker table: extend `MessengerMessageTable`, register in `brokers`, create table in module installer.

### 5. Consumer (CLI mode)

Set `'run_mode' => 'cli'` and run under Supervisor/systemd:

```bash
php bitrix/bitrix.php messenger:consume vendor_module_queue \
    --time-limit=300 --sleep=1
```

Flags:
- `-t, --time-limit` — process lifetime in seconds.
- `--sleep` — pause between iterations when queue is empty (default 1).

For production with heavy queues, prefer `cli` mode with a supervisor over `web` mode.

## When to Choose What

- **Periodic task by schedule** → `CAgent` + cron mode.
- **"Almost instant" tail after response** (email notification, metric) → `addBackgroundJob`.
- **Reliable processing with retries, high volumes, parallelism** → `Messenger`.
- **Very long one-time data migration** → console command run manually.

## Checklist

- [ ] Background code does not rely on `$_SESSION`/`$_COOKIE` in the hit context.
- [ ] Agents registered by the module are removed in `DoUninstall`.
- [ ] For CLI queues, `time-limit`, supervisor restart, and `run_mode=cli` are configured.
- [ ] Messages contain scalars/DTOs with all data needed at processing time; no `EntityObject` with loaded relations.
- [ ] Handler is idempotent: re-processing the same message is safe.
- [ ] `total_processing_limit` >= `limit` in queue config.
- [ ] Errors inside tasks are logged via PSR-3 logger, not silently suppressed.
