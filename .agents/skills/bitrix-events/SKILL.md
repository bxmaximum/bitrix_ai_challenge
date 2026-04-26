---
name: bitrix-events
description: Покрывает событийную систему Bitrix — новая модель (Bitrix\Main\Event, EventResult, EventManager::addEventHandler, make:event, make:eventhandler) и старая (OnBefore*/OnAfter* хуки в CIBlock, CUser, CSale и других классических API). Применяется при интеграции модулей, хуках жизненного цикла сущностей, публикации собственных событий и подписке на события других модулей. Ключевые термины — Event, EventManager, EventResult, OnBefore, OnAfter, handler, subscriber, addEventHandler.
---

# События Bitrix

Есть **две модели событий**: новая (ООП, `Event` + `EventResult`) и старая (строковый код + обработчик, возвращающий bool/массив). Для нового кода — новая модель. Старая используется для совместимости с ядром (`OnBeforeUserAdd`, `OnPageStart`, ...).

## Новая модель — публикация своего события

### 1. Создать класс события

```bash
php bitrix/bitrix.php make:event PostCreated -m vendor.blog
```

Файл: `/local/modules/vendor.blog/lib/Public/Event/Post/PostCreatedEvent.php`.

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

### 2. Бросить событие из сервиса

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

### 3. Написать обработчик

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

### 4. Зарегистрировать обработчик в `install/index.php`

```php
\Bitrix\Main\EventManager::getInstance()->registerEventHandler(
    fromModule: 'vendor.blog',
    eventType: \Vendor\Blog\Public\Event\Post\PostCreatedEvent::class,
    toModuleId: 'vendor.notify',
    toClass: \Vendor\Notify\Internals\Integration\Blog\EventHandler\NotifyAuthorHandler::class,
    toMethod: '__invoke',
);
```

В `DoUninstall()` — **обязательно** `unRegisterEventHandler` с теми же параметрами.

## Старая модель (совместимость)

Старые события имеют строковые имена: `OnBeforeUserAdd`, `OnAfterUserAdd`, `OnEpilog`, `OnPageStart`. Они передают массив/объект параметров, возвращают:

- `true`/ничего — продолжить;
- `false` + `$APPLICATION->ThrowException(...)` — отменить действие;
- массив с `'FIELDS' => [...]` — модифицировать поля (для `OnBefore*`).

Регистрация обработчиков, принимающих **старую** сигнатуру:

```php
EventManager::getInstance()->registerEventHandlerCompatible(
    'main',
    'OnAfterUserAdd',
    'vendor.blog',
    \Vendor\Blog\Internals\Integration\Main\EventHandler\OnAfterUserAddHandler::class,
    'handle',
);
```

Новая `registerEventHandler` тоже работает со старыми событиями, но адаптирует их к сигнатуре `Event $event` — параметры достаются через `$event->getParameter('fields')`, модификация через `EventResult`.

## Инъекция зависимостей в обработчик

Bitrix создаёт обработчик через `ServiceLocator`, если класс там зарегистрирован. Иначе — через `new` (без конструкторных параметров).

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

## Порядок и цепочка обработчиков

- Обработчики вызываются в порядке регистрации. Можно задать вес через `$sort` в `addEventHandler`/`registerEventHandler` (5-й/7-й параметр).
- Новое API (`Event::send()`) собирает результаты всех обработчиков — цепочка не прерывается, даже если один вернул `ERROR`.
- В старом API одиночный `false` может прервать действие (зависит от вызывающего кода в ядре).

## Динамическая подписка в одном процессе

Для хуков, которые не нужно хранить в БД (тесты, одноразовые обёртки):

```php
EventManager::getInstance()->addEventHandler(
    'main',
    'OnAfterUserAdd',
    fn (array $fields) => /* ... */,
);
```

Такая регистрация живёт до конца запроса.

## Чек-лист

- [ ] Файлы публичных событий — в `/lib/Public/Event/<Aggregate>/`.
- [ ] Обработчики чужих событий — в `/lib/Internals/Integration/<OtherModule>/EventHandler/`.
- [ ] Регистрация и снятие обработчиков парой в `DoInstall`/`DoUninstall`.
- [ ] Для собственных событий используется `Bitrix\Main\Event` + `EventResult`, а не возврат массивов.
- [ ] Обработчик идемпотентен и не падает в фатал — всё заворачиваем в `try/catch` с логированием.
- [ ] Тяжёлая логика уносится в очередь (`Messenger`), обработчик лишь ставит задачу.
