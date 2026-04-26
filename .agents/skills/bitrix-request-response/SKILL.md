---
name: bitrix-request-response
description: Покрывает HTTP-слой Bitrix — Application, Context, HttpRequest, HttpResponse и наследники (Json, Redirect, BFile, Html, AjaxJson), работа с куками и заголовками, snake_case↔camelCase Converter, отдача файлов, редиректы, статусы и заголовки ответа. Применяется при чтении входных параметров через Context вместо прямого $_GET/$_POST, построении endpoints, отдаче JSON и файлов, установке куки и редиректах. Ключевые термины — HttpRequest, HttpResponse, Json, Redirect, Context, Application, Cookie, headers.
---

# Application, Context, Request, Response

## Application

Singleton на хит, конфигурирует ядро, даёт доступ к общим сервисам.

```php
use Bitrix\Main\Application;

$app = Application::getInstance();

$app->getContext();           // текущий контекст
$app->getManagedCache();      // управляемый кеш
$app->getTaggedCache();       // теговый кеш
$app->getSession();           // объект сессии (см. bitrix-sessions)
$app->getConnection();        // основное соединение с БД
$app->getConnection('log');   // соединение по имени из connections
$app->getKernelSession();     // сессия ядра
$app->addBackgroundJob(fn () => /* ... */);     // см. bitrix-background-jobs
```

Наследники: `HttpApplication` (HTTP-хит), `CliApplication` (CLI-хит — `bitrix.php`).

## Context

«Конверт» одного запроса: `Request`, `Response`, `Server`, язык, `Culture`, сайт.

```php
use Bitrix\Main\Context;

$ctx = Context::getCurrent();

$ctx->getRequest();    // HttpRequest
$ctx->getResponse();   // HttpResponse
$ctx->getServer();     // Server (обёртка над $_SERVER)
$ctx->getCulture();    // региональные форматы
$ctx->getLanguage();   // 'ru'
$ctx->getSite();       // 's1'
$ctx->getEnvironment();
```

`Context::getCurrent()` — более короткий синоним для `Application::getInstance()->getContext()`.

## HttpRequest

Наследуется от `ParameterDictionary`: доступ `$request['id']` отфильтрованный, `$request->get('id')` — тоже.

### Параметры

```php
$request = Context::getCurrent()->getRequest();

$id    = (int)$request->get('id');
$title = (string)$request->getQuery('title');   // только GET
$body  = (string)$request->getPost('body');     // только POST
$file  = $request->getFile('upload');           // массив как в $_FILES
$token = $request->getHeader('X-Auth-Token');   // заголовок
$cookie = $request->getCookie('BITRIX_SM_GUEST_ID');

$all   = $request->toArray();      // все GET+POST
$query = $request->getQueryList(); // ParameterDictionary GET
$post  = $request->getPostList();
$files = $request->getFileList();
```

- `$request['x']` возвращает значение, прошедшее системные фильтры (proactive). Это **не** защищает от SQL-инъекций/XSS — экранируй сам.
- Для типизированного ввода предпочтительнее Request-DTO + `#[ValidationParameter]` (см. `bitrix-validation`).

### О самом запросе

```php
$request->getRequestMethod();       // GET|POST|PUT|DELETE
$request->isGet();
$request->isPost();
$request->isPut();
$request->isDelete();
$request->isAjaxRequest();          // заголовок X-Requested-With: XMLHttpRequest
$request->isHttps();
$request->isAdminSection();         // /bitrix/admin/*
$request->getRequestUri();          // '/news/?id=1'
$request->getRequestedPage();       // '/news/index.php'
$request->getRequestedPageDirectory();
$request->getScriptFile();
$request->getUserAgent();
$request->getAcceptedLanguages();
```

### Сервер

```php
$server = Context::getCurrent()->getServer();
$server->get('REMOTE_ADDR');
$server->getHttpHost();
$server->getDocumentRoot();
```

## HttpResponse

```php
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Web\Cookie;

$response = new HttpResponse();
$response->setStatus('201 Created');
$response->addHeader('Content-Type', 'application/json; charset=UTF-8');
$response->addCookie(
    (new Cookie('VENDOR_TOKEN', $jwt, time() + 3600))
        ->setHttpOnly(true)
        ->setSecure(true)
);
$response->setContent(\Bitrix\Main\Web\Json::encode(['ok' => true]));
return $response;
```

Методы:

- `setStatus(string)`, `getStatus()`.
- `addHeader(name, value)`, `setHeaders(HttpHeaders)`, `getHeaders()`.
- `addCookie(Cookie $c, bool $replace = true, bool $checkExpires = true)`, `getCookies()`.
- `setContent($body)`, `getContent()`.
- `flush($text = '')` — отправить заголовки и текущий буфер.
- `send($body = null)` — финализация.

## Готовые Response-классы

Все лежат в `Bitrix\Main\Engine\Response\*`. Возвращать из действия контроллера или маршрута.

### JSON

```php
use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\Engine\Response\AjaxJson;

return new Json(['id' => 42]);
// Content-Type: application/json; charset=UTF-8

return AjaxJson::createSuccess(['id' => 42]);
// {"status":"success","data":{"id":42},"errors":[]}

return AjaxJson::createError(new \Bitrix\Main\Error('Forbidden', 'ACCESS_DENIED'));
// {"status":"error","errors":[...]}
```

Контроллер, возвращающий массив, автоматически оборачивается в `AjaxJson` — вручную использовать нужно в замыканиях маршрутов или нестандартных эндпоинтах.

### Редирект

```php
use Bitrix\Main\Engine\Response\Redirect;

return new Redirect('/auth/', skipSecurity: false, status: 302);

$redirect = new Redirect('/auth/');
$redirect->setStatus('301 Moved Permanently');
return $redirect;
```

`Redirect` проверяет URL через `CHTTP` и блокирует явные XSS-редиректы.

### Компонент

```php
use Bitrix\Main\Engine\Response\Component;

return new Component('vendor:post.list', '.default', ['SECTION_ID' => 12]);
// Ответ с html компонента + js/css assets — понимает BX.ajax.runAction
```

### Файлы

```php
use Bitrix\Main\Engine\Response\BFile;          // из таблицы b_file
return BFile::createByFileId($fileId);

use Bitrix\Main\Engine\Response\ResizedImage;
return ResizedImage::createByImageId($fileId, 300, 300);

use Bitrix\Main\Engine\Response\Zip\Archive;
use Bitrix\Main\Engine\Response\Zip\ArchiveEntry;

$archive = new Archive('report.zip');
$archive->addEntry(ArchiveEntry::createFromFileId($fileId));
return $archive;
// Для nginx с mod_zip — отдача без нагрузки на PHP
```

### HTML-страница

```php
use Bitrix\Main\Engine\Response\Html;
return new Html('<h1>Hi</h1>');
```

## ParameterDictionary

И `HttpRequest`, и `getQueryList/getPostList` — наследники `ParameterDictionary`:

```php
$params = new \Bitrix\Main\Type\ParameterDictionary(['foo' => 'bar']);

$params->get('foo');            // 'bar'
$params['foo'];                 // 'bar'
$params->set('baz', 42);
$params->setValues(['a' => 1]); // массовое
$params->delete('foo');
$params->toArray();
$params->offsetExists('foo');
```

## Converter — snake↔camel для API

```php
use Bitrix\Main\Engine\Response\Converter;

$toCamel = new Converter(Converter::OUTPUT_JSON_FORMAT);
// эквивалентно TO_CAMEL | KEYS | RECURSIVE

$data = $toCamel->process([
    'CATEGORIES' => [['ID' => 1, 'NAME' => 'Foods']],
]);
// ['categories' => [['id' => 1, 'name' => 'Foods']]]

$toSnake = new Converter(
    Converter::TO_SNAKE_DIGIT | Converter::KEYS | Converter::RECURSIVE
);
$normalized = $toSnake->process(['elementOne' => 1]); // ['element_one' => 1]
```

Используй при отдаче API внешним потребителям (camelCase) — внутри держать БД-стиль UPPER_SNAKE.

## Доступ к `$_SESSION`, `$_COOKIE`, `$_GET` — только через API

- `$_GET`/`$_POST`/`$_COOKIE` — через `$request->getQuery/getPost/getCookie`.
- `$_SESSION` — через `Application::getInstance()->getSession()` (см. `bitrix-sessions`).
- `header()`, `setcookie()` — через `HttpResponse::addHeader`/`addCookie`.

Прямая работа с суперглобалами ломает композитный кеш и тесты.

## Антипаттерны

- `$_GET['id']` в контроллере/компоненте вместо `$request->getQuery('id')`.
- `die(json_encode(...))` из action — используй `Json`/`AjaxJson` или просто `return ['...']`.
- `header('Location: /auth')` + `exit` — используй `Redirect` (он проверяет URL).
- Строковые редиректы на URL из `$_GET` без валидации — открытый редирект.
- `setcookie(...)` вместо `addCookie(new Cookie(...))` — не проставятся `HttpOnly`/`Secure`.

## Чек-лист

- [ ] Доступ к входным параметрам — только через `HttpRequest`.
- [ ] Типизация и проверка полей — через Request-DTO + валидацию (см. `bitrix-validation`).
- [ ] Ответы возвращают типизированные `Response`-классы, не `echo`/`die`.
- [ ] Редиректы — через `Response\Redirect`, статус задан явно (301 vs 302).
- [ ] Файлы отдаются через `BFile`/`ResizedImage`/`Zip\Archive`, а не `readfile`.
- [ ] `Converter::OUTPUT_JSON_FORMAT` применяется к данным внешнего API (camelCase).
