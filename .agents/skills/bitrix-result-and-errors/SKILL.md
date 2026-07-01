---
name: bitrix-result-and-errors
description: Covers Bitrix unified operation results — Bitrix\Main\Result, Error, ErrorCollection, typed descendants (AddResult, UpdateResult, DeleteResult, EventResult), difference between Result and exceptions, returning errors from controllers via addError and combining service errors. Applied when designing service APIs and use cases, returning errors from module methods and controllers without throwing exceptions. Key terms — Result, Error, ErrorCollection, isSuccess, getErrors, AddResult, UpdateResult, addError.
---

# Result and Errors in Bitrix

## Philosophy

- **User Errors → `Result` + `Error`**. Validation, business rules, "entity not found," "insufficient permissions."
- **Program Errors → Exceptions**. Connection failure, missing mandatory module, broken configuration.
- One method — one clear contract: either it always returns `Result` (and doesn't throw "normal" exceptions), or it guaranteed returns a value and throws `\Throwable` in exceptional cases.

## `Bitrix\Main\Result`

Base object:

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

The error code should be **stable and machine-readable** — clients should rely on it, not the text.

## `ErrorCollection`

```php
$errors = new \Bitrix\Main\ErrorCollection();
$errors->setError(new \Bitrix\Main\Error('...', 'CODE_A'));
$errors->add([new \Bitrix\Main\Error('...', 'CODE_B')]);

foreach ($errors as $error) { /* ... */ }

$errors->getErrorByCode('CODE_A');
```

`Controller` already contains `protected ErrorCollection $errorCollection` — it is used by `$this->addError(...)` and `$this->addErrors(...)`.

## Typed `Result` for Service

Instead of `setData(['post' => $post])`, it's easier to inherit and provide normal getters:

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

Usage:

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

## Specialized ORM Results

- `AddResult` — `getId()`.
- `UpdateResult` — `getAffectedRowsCount()`, `getPrimary()`.
- `DeleteResult` — `getAffectedRowsCount()`.

They inherit from `Result` and work with the same API. Example:

```php
$add = PostTable::add(['TITLE' => 'Hi', 'AUTHOR_ID' => 1]);
if (!$add->isSuccess())
{
    $this->logger->warning('Add post failed', ['errors' => $add->getErrorMessages()]);
    return $add; // proxy upwards
}
$id = $add->getId();
```

## Returning Errors in Controller

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

The front-end will receive the following structure:

```json
{
    "status": "error",
    "errors": [
        { "message": "Title required", "code": "POST_TITLE_EMPTY", "customData": { "field": "title" } }
    ]
}
```

## Code Conventions

- Module prefix + entity + reason: `BLOG_POST_NOT_FOUND`, `BLOG_POST_TITLE_EMPTY`.
- Do not use `"1"`, `"ERROR"`, `""` — it doesn't allow the client to react.
- Translated texts — via `Loc::getMessage('BLOG_ERROR_POST_TITLE_EMPTY')`, code — via constant.

## When to use Exceptions?

- Invalid system state: "module not installed," "database not responding," "service misconfigured."
- Developer contract violation: `InvalidArgumentException`, `LogicException`, `TypeError`.
- Inside domain operations where "error = bug." For example, `Money::divide()` with zero.

Exceptions are caught at the module/controller boundary and turned into `Result` + log:

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

## Antipatterns

- Returning `bool`/`null`/`-1` from a service instead of `Result`.
- Throwing `\Exception('Post not found')` for a routine situation — that's not an exception.
- Swallowing `catch (\Throwable $e) {}` without logging.
- Error in the form of an array `['error' => 'msg']` — unify using `Result`.

Controller JSON error format: `{"status":"error","errors":[{"message":"...","code":"..."}]}`. See skill `bitrix-controllers`.
