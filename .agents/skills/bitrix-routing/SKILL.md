---
name: bitrix-routing
description: Покрывает новую систему маршрутов Bitrix — RoutingConfigurator, файлы /local/routes/*.php (web.php, api.php), группы и префиксы, middleware, именованные маршруты, параметры и where-ограничения, генерация URL через UrlManager и route name. Применяется при создании публичных URL, REST-эндпоинтов, миграции с urlrewrite.php и подключении маршрутов модуля через секцию routing в .settings.php. Ключевые термины — route, RoutingConfigurator, web.php, api.php, prefix, middleware, urlrewrite, UrlManager.
---

# Маршрутизация в Bitrix

## Включение нового роутинга

В `/local/.settings.php` (или `/bitrix/.settings.php`):

```php
'routing' => [
    'value' => [
        'config' => ['web.php'],             // файлы из /local/routes/ или /bitrix/routes/
    ],
    'readonly' => true,
],
```

В модульном `/local/modules/vendor.module/.settings.php`:

```php
'routing' => [
    'value' => ['config' => ['web.php']],
    'readonly' => true,
],
```

Файлы роутинга ищутся сначала в `/local/routes/`, потом в `/bitrix/routes/`, модульные — в `/local/modules/<m>/routes/`.

## Базовый `web.php`

```php
<?php declare(strict_types=1);

use Bitrix\Main\Routing\RoutingConfigurator;
use Vendor\Module\Infrastructure\Controller\Post;

return function (RoutingConfigurator $routes): void {
    $routes->get('/api/posts', [Post::class, 'listAction'])->name('post.list');
    $routes->get('/api/posts/{id}', [Post::class, 'getAction'])
        ->where('id', '\d+')
        ->name('post.get');

    $routes->post('/api/posts', [Post::class, 'createAction'])->name('post.create');
    $routes->put('/api/posts/{id}', [Post::class, 'updateAction'])->where('id', '\d+');
    $routes->delete('/api/posts/{id}', [Post::class, 'deleteAction'])->where('id', '\d+');
};
```

## Поддерживаемые методы

- `get`, `post`, `put`, `patch`, `delete`, `head`, `options` — для конкретного HTTP-метода.
- `any($uri, $handler)` — для любого метода.
- `match(['GET', 'POST'], $uri, $handler)` — явный список методов.

## Обработчики

Принимаются:

- `[Controller::class, 'actionMethod']` — метод контроллера Bitrix (без суффикса `Action`? **С суффиксом**, как есть: `createAction`).
- Callable/closure:

    ```php
    $routes->get('/health', function () {
        return new \Bitrix\Main\HttpResponse('ok');
    });
    ```

- Строка `Controller::class . '@actionMethod'`.

Возврат замыкания: `HttpResponse`, строка, массив (будет преобразован в JSON), `null`.

## Параметры маршрута

Фигурные скобки объявляют параметр:

```php
$routes->get('/posts/{slug}', [Post::class, 'bySlugAction']);
$routes->get('/users/{id}/posts/{postId?}', [Post::class, 'userPostsAction']);
```

`{param?}` — необязательный (нужен `default`):

```php
$routes->get('/posts/{page?}', [Post::class, 'listAction'])->default('page', 1);
```

Регулярка на параметр:

```php
$routes->get('/posts/{id}', [Post::class, 'getAction'])
    ->where('id', '[0-9]+');

$routes->get('/{section}/{slug}', $handler)
    ->where(['section' => '[a-z]+', 'slug' => '[a-z0-9\-]+']);
```

## Имена и генерация URL

```php
$routes->get('/posts/{id}', [Post::class, 'getAction'])
    ->where('id', '\d+')
    ->name('post.get');
```

```php
use Bitrix\Main\Routing\RouteCollection;

$url = (string)\Bitrix\Main\Application::getInstance()
    ->getRouter()
    ->route('post.get', ['id' => 42]);
// /posts/42
```

## Группы

```php
$routes->group(['prefix' => '/api', 'name' => 'api.'], function (RoutingConfigurator $routes) {
    $routes->get('/posts', [Post::class, 'listAction'])->name('post.list');     // /api/posts, name: api.post.list
    $routes->post('/posts', [Post::class, 'createAction'])->name('post.create');

    $routes->group(['prefix' => '/admin', 'name' => 'admin.'], function ($routes) {
        $routes->get('/stats', [Admin::class, 'statsAction'])->name('stats');   // /api/admin/stats, name: api.admin.stats
    });
});
```

Поддерживается объединение опций: `prefix`, `name` (префикс имени), `where` (регулярки для группы).

## Отдача view / компонента

```php
$routes->get('/about', fn () => \Bitrix\Main\Engine\Response\Component::createByComponentName(
    'bitrix:main.include', '.default', ['PATH' => '/about.inc.php']
));
```

Или вернуть массив/объект — движок сериализует через `Engine\Response\Converter`.

## Миграция с `urlrewrite.php`

1. Для **новых** маршрутов используй `web.php` — `urlrewrite.php` больше не нужен.
2. Старый `urlrewrite.php` можно оставить для легаси ЧПУ компонентов: ядро обработает его до роутера.
3. Правило миграции: запись

    ```php
    ['CONDITION' => '#^/catalog/section/(\d+)/?$#', 'RULE' => 'SECTION_ID=$1', 'PATH' => '/catalog/section.php']
    ```

    заменяется на

    ```php
    $routes->get('/catalog/section/{id}', [Catalog::class, 'sectionAction'])->where('id', '\d+');
    ```

4. Не забудь очистить кеш `urlrewrite`: `CUrlRewriter::ReIndexAll()`.

## Чек-лист

- [ ] `web.php` лежит в `/local/routes/` (или в модуле) и подключён в `.settings.php` через `routing.config`.
- [ ] Для GET без побочных эффектов — `->get()`, для модификаций — `post/put/patch/delete`.
- [ ] Все параметры с ограничениями через `->where(...)`.
- [ ] Имена маршрутов уникальны и даны всем «важным» URL.
- [ ] URL в коде и шаблонах генерируются через `router()->route('name', [...])`, а не склеиваются строками.
- [ ] Для AJAX-эндпоинтов на публичных URL не забыт фильтр `Csrf` в контроллере.
