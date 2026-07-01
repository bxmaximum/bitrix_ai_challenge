---
name: bitrix-events
description: Covers Bitrix event system — new model (Bitrix\Main\Event, EventResult, EventManager::addEventHandler, make:event, make:eventhandler) and old model (OnBefore*/OnAfter* hooks in CIBlock, CUser, CSale and other classic APIs). Applied when integrating modules, entity lifecycle hooks, publishing custom events and subscribing to events of other modules. Key terms — Event, EventManager, EventResult, OnBefore, OnAfter, handler, subscriber, addEventHandler.
---

# Bitrix Events

There are **two event models**: new (OOP, `Event` + `EventResult`) and old (string code + handler returning bool/array). For new code — use the new model. The old model is used for compatibility with the kernel (`OnBeforeUserAdd`, `OnPageStart`, ...).

## New Model — Publishing Your Event

### 1. Create Event Class

```bash
php bitrix/bitrix.php make:event PostCreated -m vendor.blog
```

File: `/local/modules/vendor.blog/lib/Public/Event/Post/PostCreatedEvent.php`.

```php
<?php declare(strict_types=1);

namespace Vendor\Blog\Public\Event\Post;

use Bitrix\Main\Event;

final class PostCreatedEvent extends Event
{
    public function __construct(
        public readonly int $postId,
        public readonly int $authorId,
        public readonly string $title,
    ) {
        parent::__construct('vendor.blog', self::class, [
            'postId'   => $this->postId,
            'authorId' => $this->authorId,
            'title'    => $this->title,
        ]);
    }
}
```

### 2. Dispatch Event from Service

```php
use Vendor\Blog\Public\Event\Post\PostCreatedEvent;

$event = new PostCreatedEvent($post->getId(), $post->getAuthorId(), $post->getTitle());
$event->send();

foreach ($event->getResults() as $result)
{
    if ($result->getType() === \Bitrix\Main\EventResult::ERROR)
    {
        $this->logger->warning('Subscriber failed', ['errors' => $result->getParameters()]);
    }
}
```

### 3. Write Handler

```bash
php bitrix/bitrix.php make:eventhandler NotifyAuthor \
    --event-module=vendor.blog --handler-module=vendor.notify
```

```php
<?php declare(strict_types=1);

namespace Vendor\Notify\Internals\Integration\Blog\EventHandler;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Vendor\Blog\Public\Event\Post\PostCreatedEvent;

final class NotifyAuthorHandler
{
    public function __construct(
        private readonly \Vendor\Notify\Application\Service\Notifier $notifier,
    ) {}

    public function __invoke(Event $event): EventResult
    {
        if (!$event instanceof PostCreatedEvent)
        {
            return new EventResult(EventResult::UNDEFINED);
        }

        $result = $this->notifier->notifyAuthor($event->authorId, $event->title);

        return new EventResult(
            $result->isSuccess() ? EventResult::SUCCESS : EventResult::ERROR,
            $result->getErrorMessages(),
        );
    }
}
```

### 4. Register Handler in `install/index.php`

```php
\Bitrix\Main\EventManager::getInstance()->registerEventHandler(
    fromModule: 'vendor.blog',
    eventType: \Vendor\Blog\Public\Event\Post\PostCreatedEvent::class,
    toModuleId: 'vendor.notify',
    toClass: \Vendor\Notify\Internals\Integration\Blog\EventHandler\NotifyAuthorHandler::class,
    toMethod: '__invoke',
);
```

In `DoUninstall()` — **mandatory** `unRegisterEventHandler` with the same parameters.

## Old Model (Compatibility)

Old events have string names: `OnBeforeUserAdd`, `OnAfterUserAdd`, `OnEpilog`, `OnPageStart`. They pass an array/object of parameters and return:

- `true`/nothing — continue;
- `false` + `$APPLICATION->ThrowException(...)` — cancel action;
- array with `'FIELDS' => [...]` — modify fields (for `OnBefore*`).

Registering handlers accepting the **old** signature:

```php
EventManager::getInstance()->registerEventHandlerCompatible(
    'main',
    'OnAfterUserAdd',
    'vendor.blog',
    \Vendor\Blog\Internals\Integration\Main\EventHandler\OnAfterUserAddHandler::class,
    'handle',
);
```

The new `registerEventHandler` also works with old events but adapts them to the `Event $event` signature — parameters are retrieved via `$event->getParameter('fields')`, modification via `EventResult`.

## Dependency Injection into Handler

Bitrix creates the handler via `ServiceLocator` if the class is registered there. Otherwise — via `new` (without constructor parameters).

```php
'services' => [
    'value' => [
        \Vendor\Notify\Internals\Integration\Blog\EventHandler\NotifyAuthorHandler::class => [
            'className' => \Vendor\Notify\Internals\Integration\Blog\EventHandler\NotifyAuthorHandler::class,
        ],
    ],
    'readonly' => true,
],
```

## Order and Chain of Handlers

- Handlers are called in order of registration. Weight can be specified via `$sort` in `addEventHandler`/`registerEventHandler` (5th/7th parameter).
- The new API (`Event::send()`) collects results from all handlers — the chain is not interrupted even if one returns `ERROR`.
- In the old API, a single `false` can interrupt the action (depends on the calling code in the kernel).

## Dynamic Subscription in One Process

For hooks that don't need to be stored in the DB (tests, one-time wrappers):

```php
EventManager::getInstance()->addEventHandler(
    'main',
    'OnAfterUserAdd',
    fn (array $fields) => /* ... */,
);
```

Such registration lives until the end of the request.

## Checklist

- [ ] Public event files — in `/lib/Public/Event/<Aggregate>/`.
- [ ] Handlers of other modules' events — in `/lib/Internals/Integration/<OtherModule>/EventHandler/`.
- [ ] Registration and unregistration of handlers as a pair in `DoInstall`/`DoUninstall`.
- [ ] Custom events use `Bitrix\Main\Event` + `EventResult` instead of returning arrays.
- [ ] Handler is idempotent and does not crash — wrap everything in `try/catch` with logging.
- [ ] Heavy logic is moved to a queue (`Messenger` via `$message->send()`), handler only dispatches a task.
