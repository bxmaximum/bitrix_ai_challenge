---
name: bitrix-routing
description: Covers the new Bitrix routing system — RoutingConfigurator, /local/routes/*.php files (web.php, api.php), groups and prefixes, named routes, parameters and where constraints, URL generation via router->route(). Applied when creating public URLs, REST endpoints, migrating from urlrewrite.php and connecting module routes via the routing section in .settings.php. Key terms — route, RoutingConfigurator, web.php, api.php, prefix, routing_index.php, urlrewrite.
---

# Routing in Bitrix

Requires **main 21.400.0+**.

## Enabling New Routing

### Web server

Route non-existent files to `routing_index.php`:

**Apache** (`.htaccess`):

```apache
RewriteCond %{REQUEST_FILENAME} !/bitrix/routing_index.php$
RewriteRule ^(.*)$ /bitrix/routing_index.php [L]
```

**Nginx**:

```nginx
try_files $uri $uri/ /bitrix/routing_index.php;
```

### `.settings.php`

In `/local/.settings.php` (or `/bitrix/.settings.php`):

```php
'routing' => [
    'value' => [
        'config' => ['web.php'],             // files from /local/routes/ or /bitrix/routes/
    ],
    'readonly' => true,
],
```

In modular `/local/modules/vendor.module/.settings.php`:

```php
'routing' => [
    'value' => ['config' => ['web.php']],
    'readonly' => true,
],
```

Routing files are searched in order: `/local/routes/`, then `/bitrix/routes/`, and modular ones in `/local/modules/<m>/routes/`.

> **User routes belong only in `/local/routes/`.** `/bitrix/routes/` is reserved for the system.

## Basic `web.php`

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

## Supported Methods

- `get`, `post`, `put`, `patch`, `delete`, `head`, `options` — for specific HTTP methods.
- `any($uri, $handler)` — for any method.
- `match(['GET', 'POST'], $uri, $handler)` — explicit list of methods.

## Handlers

Accepted:

- `[Controller::class, 'actionMethod']` — Bitrix controller method (with `Action` suffix as is: `createAction`).
- Callable/closure:

    ```php
    $routes->get('/health', function () {
        return new \Bitrix\Main\HttpResponse('ok');
    });
    ```

- String `Controller::class . '@actionMethod'`.

Closure return: `HttpResponse`, string, array (converted to JSON), `null`.

## Route Parameters

Curly braces declare a parameter:

```php
$routes->get('/posts/{slug}', [Post::class, 'bySlugAction']);
$routes->get('/users/{id}/posts/{postId?}', [Post::class, 'userPostsAction']);
```

`{param?}` is optional (requires a `default`):

```php
$routes->get('/posts/{page?}', [Post::class, 'listAction'])->default('page', 1);
```

Regex on parameter:

```php
$routes->get('/posts/{id}', [Post::class, 'getAction'])
    ->where('id', '[0-9]+');

$routes->get('/{section}/{slug}', $handler)
    ->where(['section' => '[a-z]+', 'slug' => '[a-z0-9\-]+']);
```

## Names and URL Generation

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

## Groups

```php
$routes->group(['prefix' => '/api', 'name' => 'api.'], function (RoutingConfigurator $routes) {
    $routes->get('/posts', [Post::class, 'listAction'])->name('post.list');     // /api/posts, name: api.post.list
    $routes->post('/posts', [Post::class, 'createAction'])->name('post.create');

    $routes->group(['prefix' => '/admin', 'name' => 'admin.'], function ($routes) {
        $routes->get('/stats', [Admin::class, 'statsAction'])->name('stats');   // /api/admin/stats, name: api.admin.stats
    });
});
```

Option merging is supported: `prefix`, `name` (name prefix), `where` (regexes for group).

## Delivering view / component

```php
$routes->get('/about', fn () => \Bitrix\Main\Engine\Response\Component::createByComponentName(
    'bitrix:main.include', '.default', ['PATH' => '/about.inc.php']
));
```

Or return an array/object — the engine serializes via `Engine\Response\Converter`.

## Migration from `urlrewrite.php`

1. For **new** routes, use `web.php` — `urlrewrite.php` is no longer needed.
2. Old `urlrewrite.php` can be left for legacy component SEF: the kernel processes it before the router.
3. Migration rule: entry

    ```php
    ['CONDITION' => '#^/catalog/section/(\d+)/?$#', 'RULE' => 'SECTION_ID=$1', 'PATH' => '/catalog/section.php']
    ```

    is replaced by

    ```php
    $routes->get('/catalog/section/{id}', [Catalog::class, 'sectionAction'])->where('id', '\d+');
    ```

4. Don't forget to clear `urlrewrite` cache: `CUrlRewriter::ReIndexAll()`.

## Checklist

- [ ] Web server forwards to `routing_index.php`.
- [ ] `web.php` is in `/local/routes/` (or in a module) and connected in `.settings.php` via `routing.config`.
- [ ] For GET without side effects — `->get()`, for modifications — `post/put/patch/delete`.
- [ ] All parameters have constraints via `->where(...)`.
- [ ] Route names are unique and assigned to all "important" URLs.
- [ ] URLs in code and templates are generated via `router()->route('name', [...])`, not string concatenation.
- [ ] For AJAX endpoints on public URLs, the `Csrf` filter in the controller is not forgotten.
