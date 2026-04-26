---
name: bitrix-controllers
description: Покрывает контроллеры D7 на базе Bitrix\Main\Engine\Controller и JsonController — actions, автосвязывание параметров, фильтры ActionFilter (Authentication, Csrf, HttpMethod, Scope, CloseSession, ContentType), ошибки через addError/ErrorCollection, configureActions, рендер и JSON-ответы. Применяется при реализации AJAX/REST-эндпоинтов, внутренних API и ajax-экшенов компонентов через Controllerable. Ключевые термины — Controller, action, ActionFilter, Csrf, runAction, addError, configureActions, ajax endpoint, REST.
---

# Контроллеры Bitrix

## Где лежат и как называются

- Файлы: `/local/modules/<vendor>.<module>/lib/Infrastructure/Controller/<Name>.php`.
- Неймспейс (по умолчанию): `\Vendor\Module\Infrastructure\Controller\<Name>`.
- Публичный URL для AJAX: `/bitrix/services/main/ajax.php?action=vendor:module.<name>.<action>`.
- URL можно переписать маршрутом (см. `bitrix-routing`).

Настройка пространства имён — в `/local/modules/vendor.module/.settings.php`:

```php
'controllers' => [
    'value' => [
        'defaultNamespace' => '\\Vendor\\Module\\Infrastructure\\Controller',
        'namespaces' => [
            '\\Vendor\\Module\\Infrastructure\\Controller\\Web' => 'web',
        ],
        'restIntegration' => ['enabled' => true], // для REST
    ],
    'readonly' => true,
],
```

Доступ к `Web\PostController::getAction` → `?action=vendor:module.web.post.get`.

## Минимальный контроллер

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
                '-prefilters' => [ActionFilter\Csrf::class], // GET без CSRF
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

## Автосвязывание параметров действий

Параметры действия собираются движком в следующем порядке:

1. **Скалярные типы** (`int`, `string`, `bool`, `float`, `array`) → из `GET`/`POST`/`FILES`.
2. **Объекты-сервисы** → из `ServiceLocator` по имени/типу.
3. **`HttpRequest`, `Session`, `CurrentUser`** → из контекста.
4. **Request DTO** с атрибутом `#[Bitrix\Main\Validation\Engine\ValidationParameter]` → маппинг из запроса + валидация (см. `bitrix-validation`).
5. **ORM-объекты**, если действие принимает `EntityObject` — загружаются по `id`.

Отсутствие обязательного параметра → автоматическая ошибка.

## Фильтры действий

Предустановленные:

- `ActionFilter\Authentication` — требует авторизованного пользователя.
- `ActionFilter\Csrf` (по умолчанию включён на `POST`) — проверка `sessid`/`X-Bitrix-Csrf-Token`.
- `ActionFilter\HttpMethod([...])` — ограничение по методу.
- `ActionFilter\CloseSession` — закрывает сессию перед действием (параллельные AJAX).
- `ActionFilter\ContentType(['application/json'])` — допустимый `Content-Type`.
- `ActionFilter\Scope($scope)` — ограничивает вызов конкретным scope (ajax/rest/cli).

Формат в `configureActions()`:

```php
'default' => [
    'prefilters' => [...],   // полностью заменить список
    '+prefilters' => [...],  // добавить
    '-prefilters' => [...],  // удалить (по FQCN)
    'postfilters' => [...],
],
```

## Ошибки

- `$this->addError(new \Bitrix\Main\Error('msg', 'CODE', ['key' => 'value']));`
- `$this->addErrors($result->getErrors());`
- Никогда не бросай исключения наружу ради «обычных» пользовательских ошибок — они ухудшают UX и сложнее тестируются. Используй `Result` + `Error`.
- Ответ с ошибками автоматически получает `status: 'error'` и массив `errors`.

## Типы ответов

- `array` → JSON: `{ "status": "success", "data": [...] }`.
- `null` → `{ "status": "success" }` без данных.
- `Bitrix\Main\HttpResponse` — кастомный ответ (заголовки, статус, тело).
- `Bitrix\Main\Engine\Response\Html` / `Json` / `Redirect` / `AjaxJson`.
- `Bitrix\Main\Engine\Response\Component` — рендер компонента.
- `Bitrix\Main\Engine\Response\Component\Ajax` — JSON + рендер компонента.
- `Bitrix\Main\Engine\Response\BFile` / `File` / `HttpResponseFile` — отдача файла.

Помощники контроллера:

```php
return $this->renderView('list', ['items' => $items]);
// => /local/modules/vendor.module/views/list.php

return $this->renderComponent('vendor:post.list', '.default', ['IBLOCK_ID' => 12]);

return $this->renderExtension('vendor.post.list', ['items' => $items]);
```

## Scope (AJAX / REST / CLI)

- **AJAX**: вызов через `/bitrix/services/main/ajax.php?action=...` или `BX.ajax.runAction('...', {})` из JS. Автоматически доступен если контроллер объявлен и есть `controllers` в `.settings.php`.
- **REST**: требуется `restIntegration.enabled = true` в настройках + установленный модуль `rest`.
- **CLI**: контроллер можно вызвать из команды, если есть `ActionFilter\Scope`.

Разные сценарии — разные наборы фильтров. Пример переопределения по scope:

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

## Вызов с фронта

```js
BX.ajax.runAction('vendor:module.post.create', {
    data: { title: 'Title', body: 'Body' },
}).then((response) => {
    console.log(response.data);
});
```

Для REST — `BX.rest.callMethod('vendor.module.post.create', {...})`.

## Чек-лист

- [ ] Контроллер **тонкий**: вызывает сервис, возвращает DTO/массив.
- [ ] Указаны `HttpMethod` и `Authentication`/`Csrf` там, где нужно.
- [ ] Вход валидируется через Request DTO + `#[ValidationParameter]` (см. `bitrix-validation`).
- [ ] Ошибки возвращаются через `$this->addError(...)`, а не через исключения.
- [ ] Возвращаемый тип явный: `array`, `HttpResponse` или `renderXxx`.
- [ ] Зависимости инжектятся через конструктор; сервисы зарегистрированы в `ServiceLocator`.
