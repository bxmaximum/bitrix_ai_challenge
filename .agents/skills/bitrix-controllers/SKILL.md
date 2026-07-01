---
name: bitrix-controllers
description: Covers D7 controllers based on Bitrix\Main\Engine\Controller and JsonController — actions, parameter autowiring, ActionFilter filters (Authentication, Csrf, HttpMethod, Scope, CloseSession, ContentType), errors via addError/ErrorCollection, configureActions, rendering and JSON responses. Applied when implementing AJAX/REST endpoints, internal APIs and component ajax actions via Controllerable. Key terms — Controller, action, ActionFilter, Csrf, runAction, addError, configureActions, ajax endpoint, REST.
---

# Bitrix Controllers

## Location and Naming

- Files: `/local/modules/<vendor>.<module>/lib/Infrastructure/Controller/<Name>.php`.
- Namespace (default): `\Vendor\Module\Infrastructure\Controller\<Name>`.
- Public URL for AJAX: `/bitrix/services/main/ajax.php?action=vendor:module.<name>.<action>`.
- URL can be rewritten by a route (see `bitrix-routing`).

Namespace configuration — in `/local/modules/vendor.module/.settings.php`:

```php
'controllers' => [
    'value' => [
        'defaultNamespace' => '\\Vendor\\Module\\Infrastructure\\Controller',
        'namespaces' => [
            '\\Vendor\\Module\\Infrastructure\\Controller\\Web' => 'web',
        ],
        'restIntegration' => ['enabled' => true], // for REST
    ],
    'readonly' => true,
],
```

Access to `Web\PostController::getAction` → `?action=vendor:module.web.post.get`.

## Minimal Controller

```php
<?php declare(strict_types=1);

namespace Vendor\Module\Infrastructure\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Vendor\Module\Application\Service\PostService;

final class Post extends Controller
{
    public function __construct(
        private readonly PostService $postService,
    ) {
        parent::__construct();
    }

    public function configureActions(): array
    {
        return [
            'get' => [
                '+prefilters' => [new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET])],
                '-prefilters' => [ActionFilter\Csrf::class], // GET without CSRF
            ],
            'create' => [
                '+prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Authentication(),
                ],
            ],
        ];
    }

    public function getAction(int $id): array
    {
        $post = $this->postService->find($id);
        if ($post === null)
        {
            $this->addError(new Error('Not found', 'POST_NOT_FOUND'));
            return [];
        }

        return ['post' => $post];
    }

    public function createAction(string $title, string $body): array
    {
        $result = $this->postService->create($title, $body);

        if (!$result->isSuccess())
        {
            $this->addErrors($result->getErrors());
            return [];
        }

        return ['id' => $result->getId()];
    }
}
```

## Action Parameter Autowiring

Action parameters are collected by the engine in the following order:

1. **Scalar types** (`int`, `string`, `bool`, `float`, `array`) → from `GET`/`POST`/`FILES`.
2. **Service objects** → from `ServiceLocator` by name/type.
3. **`HttpRequest`, `Session`, `CurrentUser`** → from context.
4. **Request DTO** with `#[Bitrix\Main\Validation\Engine\ValidationParameter]` attribute → mapping from request + validation (see `bitrix-validation`).
5. **ORM objects**, if the action accepts `EntityObject` — loaded by `id`.

Missing mandatory parameter → automatic error.

## Controller Lifecycle

1. Constructor (DI via `ServiceLocator`).
2. `init()` — load modules, initialize services (`parent::init()` first).
3. Prefilters run.
4. Action method executes.
5. Postfilters run.
6. Response is serialized.

`executeComponent()` in component controllers does **not** run during AJAX actions — use `onPrepareComponentParams()` for shared setup.

## Default Prefilters

By default, actions get: `HttpMethod` (GET only), `Authentication`, `Csrf` (for POST). Override per action as needed.

## Action Filters

Predefined filters:

- `ActionFilter\Authentication` — requires an authorized user (401 without redirect).
- `ActionFilter\Csrf` — `sessid`/`X-Bitrix-Csrf-Token` check (on by default for POST).
- `ActionFilter\HttpMethod([...])` — method restriction.
- `ActionFilter\CloseSession` — closes session before action (parallel AJAX).
- `ActionFilter\ContentType(['application/json'])` — allowed `Content-Type`.
- `ActionFilter\Scope($scope)` — restricts call to a specific scope (ajax/rest/cli).
- `ActionFilter\Cors` — CORS headers for cross-origin AJAX.

### `configureActions()` format

```php
'default' => [
    'prefilters' => [...],   // replace the list entirely
    '+prefilters' => [...],  // add
    '-prefilters' => [...],  // remove (by FQCN)
    'postfilters' => [...],
],
```

## Errors

- `$this->addError(new \Bitrix\Main\Error('msg', 'CODE', ['key' => 'value']));`
- `$this->addErrors($result->getErrors());`
- Never throw exceptions outward for "ordinary" user errors — they worsen UX and are harder to test. Use `Result` + `Error`.
- Response with errors automatically receives `status: 'error'` and `errors` array.

## Response Types

- `array` → JSON: `{ "status": "success", "data": [...] }`.
- `null` → `{ "status": "success" }` without data.
- `Bitrix\Main\HttpResponse` — custom response (headers, status, body).
- `Bitrix\Main\Engine\Response\Html` / `Json` / `Redirect` / `AjaxJson`.
- `Bitrix\Main\Engine\Response\Component` — component render.
- `Bitrix\Main\Engine\Response\Component\Ajax` — JSON + component render.
- `Bitrix\Main\Engine\Response\BFile` / `File` / `HttpResponseFile` — file delivery.

Controller helpers:

```php
return $this->renderView('list', ['items' => $items]);
// => /local/modules/vendor.module/views/list.php

return $this->renderComponent('vendor:post.list', '.default', ['IBLOCK_ID' => 12]);

return $this->renderExtension('vendor.post.list', ['items' => $items]);
```

## Scope (AJAX / REST / CLI)

- **AJAX**: call via `/bitrix/services/main/ajax.php?action=...` or `BX.ajax.runAction('...', {})` from JS. Automatically available if controller is declared and `controllers` exists in `.settings.php`.
- **REST**: requires `restIntegration.enabled = true` in settings + `rest` module installed.
- **CLI**: controller can be called from a command if `ActionFilter\Scope` is present.

Different scenarios — different sets of filters. Example of overriding by scope:

```php
public function configureActions(): array
{
    return [
        'get' => [
            'prefilters' => [
                new ActionFilter\Scope(ActionFilter\Scope::AJAX),
                new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
            ],
        ],
    ];
}
```

## PHP 8 Attribute Filters

Alternative to `configureActions()` — apply filters via attributes on action methods:

```php
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\Prefilters;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\HttpMethod;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\Authentication;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\Csrf;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\DisablePrefilters;

final class Post extends Controller
{
    #[Prefilters([
        new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
    ])]
    #[DisablePrefilters([ActionFilter\Csrf::class])]
    public function listAction(): array { /* ... */ }

    #[HttpMethod(HttpMethod::METHOD_POST)]
    #[Authentication]
    #[Csrf]
    public function createAction(string $title): array { /* ... */ }
}
```

Controller-level defaults via `getDefaultPreFilters()` / `getDefaultPostFilters()`. Use `#[EnablePrefilters]` / `#[DisablePrefilters]` to adjust inherited defaults per action.

## Additional Autowire Types

- `Bitrix\Main\Engine\CurrentUser` — current user context.
- `Bitrix\Main\Engine\JsonPayload` — raw JSON body.
- `Bitrix\Main\UI\PageNavigation` — pagination from request.

Custom DTO autowiring via `getAutoWiredParameters()`.

## Front-end Call

```js
BX.ajax.runAction('vendor:module.post.create', {
    data: { title: 'Title', body: 'Body' },
}).then((response) => {
    console.log(response.data);
});
```

For REST — `BX.rest.callMethod('vendor.module.post.create', {...})`.

## Checklist

- [ ] Controller is **thin**: calls service, returns DTO/array.
- [ ] `HttpMethod` and `Authentication`/`Csrf` are specified where needed.
- [ ] Input is validated via Request DTO + `#[ValidationParameter]` (see `bitrix-validation`).
- [ ] Errors are returned via `$this->addError(...)`, not via exceptions.
- [ ] Return type is explicit: `array`, `HttpResponse` or `renderXxx`.
- [ ] Dependencies are injected via constructor; services are registered in `ServiceLocator`.
