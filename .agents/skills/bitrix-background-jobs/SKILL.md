---
name: bitrix-background-jobs
description: Покрывает фоновые задачи и отложенную обработку в Bitrix — агенты CAgent, Application::addBackgroundJob(), очереди Messenger (messenger:consume, AsyncMessage, MessageHandler). Применяется при проектировании cron-задач, отложенных интеграций, рассылок и выборе между агентом, background job и очередью. Ключевые термины — agent, CAgent, addBackgroundJob, Messenger, queue, consumer, delayed job, cron.
---

# Фоновые задачи в Bitrix

Три способа отложенной работы — у каждого своя ниша:

| Механизм | Когда использовать | Где живёт |
| --- | --- | --- |
| `CAgent` | Периодические задачи (чистка, синхронизация, напоминания) | БД + hit/cron |
| `Application::addBackgroundJob()` | Небольшая работа **после** отдачи ответа в рамках того же процесса | PHP-FPM, тот же запрос |
| `Messenger` (очереди) | Продолжительные/надёжные задачи с возможностью параллелизма и ретраев | Отдельный процесс `messenger:consume` |

## Агенты (`CAgent`)

```php
\CAgent::AddAgent(
    name: \Vendor\Module\Cli\Agent\QueueAgent::class . '::run();',
    module: 'vendor.module',
    period: 'N',        // 'Y' — периодический (всегда через interval), 'N' — сдвигать next_exec
    interval: 300,      // секунд
    datecheck: '',      // дата следующего запуска
    active: 'Y',
    next_exec: '',      // yyyy-mm-dd hh:mm:ss
    sort: 100,
    existError: true,
);
```

Метод агента:

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

        return self::class . '::run();'; // важно: вернуть строку для повторной регистрации
    }
}
```

### Правила

- Агент работает либо на хитах, либо по cron (настройка в админке → «Настройки агентов»).
- Для тяжёлых агентов **всегда** включай cron: иначе они блокируют пользовательский хит.
- В `DoUninstall` модуля: `CAgent::RemoveModuleAgents('vendor.module')`.
- Не держи состояние в статике между вызовами — процесс может смениться.

### Разовая задача на «через 5 минут»

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

Отложенный вызов **после** отдачи ответа (перед `fastcgi_finish_request`/в `onAfterEpilog`). Идеально для «дожить секунду и отправить метрику/письмо».

```php
\Bitrix\Main\Application::getInstance()->addBackgroundJob(
    function () use ($userId) {
        \Vendor\Module\Application\Service\Notifier::fromContainer()->sendWelcome($userId);
    },
    priority: 0,
);
```

### Ограничения

- Всё ещё один процесс PHP. Долгие задачи ухудшат время освобождения worker'а.
- Нет гарантии доставки: если процесс упадёт — задача не выполнится.
- Не подходит, если нужны ретраи и параллелизм — используй `Messenger`.

## Messenger (очереди сообщений)

Новый модуль `messenger` — декларативные очереди на базе Symfony Messenger (`Bitrix\Messenger`).

### 1. Сообщение (DTO)

```bash
php bitrix/bitrix.php make:message SendWelcomeEmail -m vendor.module
```

```php
namespace Vendor\Module\Public\Message;

use Bitrix\Main\Messenger\Entity\AbstractMessage;

final class SendWelcomeEmailMessage extends AbstractMessage
{
    public function __construct(
        public readonly int $userId,
    ) {}
}
```

### 2. Обработчик

```bash
php bitrix/bitrix.php make:messagehandler SendWelcomeEmail \
    --event-module=vendor.module --handler-module=vendor.module
```

```php
namespace Vendor\Module\Internals\Integration\Self\MessageHandler;

use Bitrix\Main\Messenger\Entity\AbstractReceiver;
use Bitrix\Main\Messenger\Message\MessageInterface;
use Vendor\Module\Public\Message\SendWelcomeEmailMessage;

final class SendWelcomeEmailHandler extends AbstractReceiver
{
    public function __construct(
        private readonly \Vendor\Module\Application\Service\Mailer $mailer,
    ) { parent::__construct(); }

    public function handle(MessageInterface $message): void
    {
        if (!$message instanceof SendWelcomeEmailMessage) { return; }
        $this->mailer->sendWelcome($message->userId);
    }
}
```

### 3. Постановка задачи

```php
use Bitrix\Main\Messenger\MessageBus;

\Bitrix\Main\DI\ServiceLocator::getInstance()
    ->get(MessageBus::class)
    ->dispatch(new SendWelcomeEmailMessage($userId));
```

### 4. Консьюмер

```bash
php bitrix/bitrix.php messenger:consume vendor_module_queue \
    --time-limit=300 --sleep=1 --memory-limit=256M
```

Запускай под Supervisor/systemd с перезапуском. Флаги:

- `--time-limit=300` — процесс завершается через 5 минут (защита от утечек памяти).
- `--memory-limit=256M` — мягкий лимит.
- `--sleep=1` — пауза, если очередь пустая.

### 5. Конфигурация брокеров

В `.settings.php`:

```php
'messenger' => [
    'value' => [
        'transports' => [
            'default' => ['dsn' => 'doctrine://default'],
            'high'    => ['dsn' => 'redis://127.0.0.1:6379/messages'],
        ],
        'routing' => [
            \Vendor\Module\Public\Message\SendWelcomeEmailMessage::class => 'default',
        ],
    ],
    'readonly' => true,
],
```

Поддерживаются Doctrine/MySQL, Redis, in-memory — зависит от установки.

## Когда что выбрать

- **Периодическая задача по расписанию** → `CAgent` + cron-режим.
- **«Почти мгновенный» хвост после ответа** (email-уведомление, метрика) → `addBackgroundJob`.
- **Надёжная обработка с ретраями, большими объёмами, параллелизмом** → `Messenger`.
- **Очень длительная разовая миграция данных** → консольная команда, запускаемая вручную.

## Чек-лист

- [ ] Фоновый код не полагается на `$_SESSION`/`$_COOKIE` в контексте хита.
- [ ] Агенты, регистрируемые модулем, удаляются в `DoUninstall`.
- [ ] Для очередей настроены `time-limit`, `memory-limit`, перезапуск процесса.
- [ ] Сообщения сериализуются (скаляры/DTO); не клади в них `EntityObject` с подгруженными связями.
- [ ] Обработчик идемпотентен: повторная обработка того же сообщения безопасна.
- [ ] Ошибки внутри задач логируются, но не «заглушаются» — используй PSR-3 логгер.
