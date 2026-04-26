---
name: bitrix-result-and-errors
description: Покрывает унифицированный результат операций Bitrix — Bitrix\Main\Result, Error, ErrorCollection, типизированные наследники (AddResult, UpdateResult, DeleteResult, EventResult), разница между Result и исключениями, возврат ошибок из контроллеров через addError и объединение ошибок сервисов. Применяется при проектировании API сервисов и use-case-ов, возврате ошибок из методов модуля и контроллеров без бросания исключений. Ключевые термины — Result, Error, ErrorCollection, isSuccess, getErrors, AddResult, UpdateResult, addError.
---

# Result и ошибки в Bitrix

## Философия

- **Пользовательские ошибки → `Result` + `Error`**. Валидация, бизнес-правила, «сущность не найдена», «недостаточно прав».
- **Программные ошибки → исключения**. Сбой соединения, отсутствие обязательного модуля, битая конфигурация.
- Один метод — один чёткий контракт: либо всегда возвращает `Result` (и не кидает «нормальные» исключения), либо гарантированно возвращает значение и кидает `\Throwable` в исключительных случаях.

## `Bitrix\Main\Result`

Базовый объект:

```php
$result = new \Bitrix\Main\Result();

if ($titleEmpty)
{
    $result->addError(new \Bitrix\Main\Error('Title required', 'POST_TITLE_EMPTY'));
}

if (!$result->isSuccess()) { return $result; }

$result->setData(['id' => $id]);
return $result;
```

API:

- `isSuccess(): bool`
- `getErrors(): Error[]`
- `getErrorMessages(): string[]`
- `getErrorCollection(): ErrorCollection`
- `addError(Error $e): self`
- `addErrors(array $errors): self`
- `setData(array $data): self`
- `getData(): array`

## `Bitrix\Main\Error`

```php
new \Bitrix\Main\Error(
    message: 'Title required',
    code: 'POST_TITLE_EMPTY',
    customData: ['field' => 'title'],
);
```

Код ошибки должен быть **стабильным и машинно-читаемым** — клиенты должны ориентироваться на него, а не на текст.

## `ErrorCollection`

```php
$errors = new \Bitrix\Main\ErrorCollection();
$errors->setError(new \Bitrix\Main\Error('...', 'CODE_A'));
$errors->add([new \Bitrix\Main\Error('...', 'CODE_B')]);

foreach ($errors as $error) { /* ... */ }

$errors->getErrorByCode('CODE_A');
```

`Controller` уже содержит `protected ErrorCollection $errorCollection` — им пользуются `$this->addError(...)` и `$this->addErrors(...)`.

## Типизированный `Result` для сервиса

Вместо `setData(['post' => $post])` проще наследоваться и предоставить нормальные геттеры:

```php
<?php declare(strict_types=1);

namespace Vendor\Blog\Application\Result;

use Bitrix\Main\Result;
use Vendor\Blog\Domain\Model\Post;

final class CreatePostResult extends Result
{
    private ?Post $post = null;

    public function setPost(Post $post): self
    {
        $this->post = $post;
        $this->setData(['post' => $post]);
        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }
}
```

Использование:

```php
public function create(CreatePostRequest $request): CreatePostResult
{
    $result = new CreatePostResult();

    $validation = $this->validator->validate($request);
    if (!$validation->isSuccess())
    {
        foreach ($validation->getErrors() as $error)
        {
            $result->addError(new \Bitrix\Main\Error($error->getMessage(), $error->getCode()));
        }
        return $result;
    }

    $post = $this->repository->save(Post::fromRequest($request));
    return $result->setPost($post);
}
```

## Специализированные Result-ы ORM

- `AddResult` — `getId()`.
- `UpdateResult` — `getAffectedRowsCount()`, `getPrimary()`.
- `DeleteResult` — `getAffectedRowsCount()`.

Они наследуются от `Result` и работают с тем же API. Пример:

```php
$add = PostTable::add(['TITLE' => 'Hi', 'AUTHOR_ID' => 1]);
if (!$add->isSuccess())
{
    $this->logger->warning('Add post failed', ['errors' => $add->getErrorMessages()]);
    return $add; // проксируем наверх
}
$id = $add->getId();
```

## Возврат ошибок в контроллере

```php
public function createAction(#[ValidationParameter] CreatePostRequest $request): array
{
    $result = $this->postService->create($request);

    if (!$result->isSuccess())
    {
        $this->addErrors($result->getErrors());
        return [];
    }

    return ['id' => $result->getPost()->getId()];
}
```

Фронту уйдёт структура:

```json
{
    "status": "error",
    "errors": [
        { "message": "Title required", "code": "POST_TITLE_EMPTY", "customData": { "field": "title" } }
    ]
}
```

## Соглашения по кодам

- Префикс модуля + сущность + причина: `BLOG_POST_NOT_FOUND`, `BLOG_POST_TITLE_EMPTY`.
- Не используй `"1"`, `"ERROR"`, `""` — это не даёт клиенту реагировать.
- Переводимые тексты — через `Loc::getMessage('BLOG_ERROR_POST_TITLE_EMPTY')`, код — константой.

## Когда всё же исключение?

- Недопустимое состояние системы: «модуль не установлен», «база не отвечает», «неправильно сконфигурирован сервис».
- Нарушение контракта разработчика: `InvalidArgumentException`, `LogicException`, `TypeError`.
- Внутри доменных операций, где «ошибка = баг». Например, `Money::divide()` с нулём.

Исключения ловятся на границе модуля/контроллера и превращаются в `Result` + лог:

```php
try
{
    return $this->service->operation($dto);
}
catch (\Bitrix\Main\SystemException $e)
{
    $this->logger->error('Operation failed', ['exception' => $e]);
    $result = new \Bitrix\Main\Result();
    $result->addError(new \Bitrix\Main\Error('Internal error', 'INTERNAL_ERROR'));
    return $result;
}
```

## Антипаттерны

- Возврат `bool`/`null`/`-1` из сервиса вместо `Result`.
- Выбрасывание `\Exception('Post not found')` для штатной ситуации — это не исключение.
- Проглатывание `catch (\Throwable $e) {}` без логирования.
- Ошибка в виде массива `['error' => 'msg']` — унифицируй на `Result`.
- `Error` без `code` — клиент не сможет обработать.

## Чек-лист

- [ ] Все публичные методы сервисов возвращают `Result` (или его наследника), а не примитивы.
- [ ] У каждой ошибки есть осмысленный `code` и, при необходимости, `customData`.
- [ ] Контроллер пробрасывает ошибки из `Result` в `$this->addErrors(...)`.
- [ ] Исключения системного уровня ловятся на границе и логируются.
- [ ] Тексты ошибок локализованы через `Loc::getMessage`.
