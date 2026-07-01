---
name: bitrix-request-response
description: Covers Bitrix HTTP layer — Application, Context, HttpRequest, HttpResponse and descendants (Json, Redirect, BFile, Html, AjaxJson), working with cookies and headers, snake_case↔camelCase Converter, file delivery, redirects, statuses, and response headers. Applied when reading input parameters via Context instead of direct $_GET/$_POST, building endpoints, delivering JSON and files, setting cookies, and redirects. Key terms — HttpRequest, HttpResponse, Json, Redirect, Context, Application, Cookie, headers.
---

# Application, Context, Request, Response

## Application

A singleton per hit, configures the kernel, provides access to general services.

```php
use Bitrix\Main\Application;

$app = Application::getInstance();

$app->getContext();           // current context
$app->getManagedCache();      // managed cache
$app->getTaggedCache();       // tagged cache
$app->getSession();           // session object (see bitrix-sessions)
$app->getConnection();        // primary DB connection
$app->getConnection('log');   // connection by name from connections
$app->getKernelSession();     // kernel session
$app->addBackgroundJob(fn () => /* ... */);     // see bitrix-background-jobs
```

Descendants: `HttpApplication` (HTTP hit), `CliApplication` (CLI hit — `bitrix.php`).

## Context

An "envelope" for a single request: `Request`, `Response`, `Server`, language, `Culture`, site.

```php
use Bitrix\Main\Context;

$ctx = Context::getCurrent();

$ctx->getRequest();    // HttpRequest
$ctx->getResponse();   // HttpResponse
$ctx->getServer();     // Server (wrapper over $_SERVER)
$ctx->getCulture();    // regional formats
$ctx->getLanguage();   // 'ru'
$ctx->getSite();       // 's1'
$ctx->getEnvironment();
```

`Context::getCurrent()` is a shorter alias for `Application::getInstance()->getContext()`.

## HttpRequest

Inherits from `ParameterDictionary`: `$request['id']` is filtered, `$request->get('id')` is too.

### Parameters

```php
$request = Context::getCurrent()->getRequest();

$id    = (int)$request->get('id');
$title = (string)$request->getQuery('title');   // GET only
$body  = (string)$request->getPost('body');     // POST only
$file  = $request->getFile('upload');           // array as in $_FILES
$token = $request->getHeader('X-Auth-Token');   // header
$cookie = $request->getCookie('BITRIX_SM_GUEST_ID');

$all   = $request->toArray();      // all GET+POST
$query = $request->getQueryList(); // ParameterDictionary GET
$post  = $request->getPostList();
$files = $request->getFileList();
```

- `$request['x']` returns a value processed by system filters (proactive). This **does not** protect against SQL injections/XSS — escape yourself.
- For typed input, Request-DTO + `#[ValidationParameter]` is preferred (see `bitrix-validation`).

### About the Request

```php
$request->getRequestMethod();       // GET|POST|PUT|DELETE
$request->isGet();
$request->isPost();
$request->isPut();
$request->isDelete();
$request->isAjaxRequest();          // X-Requested-With: XMLHttpRequest header
$request->isHttps();
$request->isAdminSection();         // /bitrix/admin/*
$request->getRequestUri();          // '/news/?id=1'
$request->getRequestedPage();       // '/news/index.php'
$request->getRequestedPageDirectory();
$request->getScriptFile();
$request->getUserAgent();
$request->getAcceptedLanguages();
```

### Server

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

Methods:

- `setStatus(string)`, `getStatus()`.
- `addHeader(name, value)`, `setHeaders(HttpHeaders)`, `getHeaders()`.
- `addCookie(Cookie $c, bool $replace = true, bool $checkExpires = true)`, `getCookies()`.
- `setContent($body)`, `getContent()`.
- `flush($text = '')` — send headers and current buffer.
- `send($body = null)` — finalization.

## Built-in Response Classes

All live in `Bitrix\Main\Engine\Response\*`. Return from controller action or route.

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

A controller returning an array is automatically wrapped in `AjaxJson` — manual use is needed in route closures or non-standard endpoints.

### Redirect

```php
use Bitrix\Main\Engine\Response\Redirect;

return new Redirect('/auth/', skipSecurity: false, status: 302);

$redirect = new Redirect('/auth/');
$redirect->setStatus('301 Moved Permanently');
return $redirect;
```

`Redirect` checks the URL via `CHTTP` and blocks obvious XSS redirects.

### Component

```php
use Bitrix\Main\Engine\Response\Component;

return new Component('vendor:post.list', '.default', ['SECTION_ID' => 12]);
// Response with component HTML + js/css assets — understood by BX.ajax.runAction
```

### Files

```php
use Bitrix\Main\Engine\Response\BFile;          // from b_file table
return BFile::createByFileId($fileId);

use Bitrix\Main\Engine\Response\ResizedImage;
return ResizedImage::createByImageId($fileId, 300, 300);

use Bitrix\Main\Engine\Response\Zip\Archive;
use Bitrix\Main\Engine\Response\Zip\ArchiveEntry;

$archive = new Archive('report.zip');
$archive->addEntry(ArchiveEntry::createFromFileId($fileId));
return $archive;
// For nginx with mod_zip — delivery without PHP overhead
```

### HTML Page

```php
use Bitrix\Main\Engine\Response\Html;
return new Html('<h1>Hi</h1>');
```

## ParameterDictionary

`HttpRequest::getQueryList()`, `getPostList()`, `getFileList()` return this object.

```php
$params = $request->getPostList();

$params->get('id');             // value
$params->getRaw('id');          // value before filters
$params->getValues();           // array
$params->isEmpty();             // bool
$params->offsetExists('id');    // ArrayAccess
```

## Checklist

- [ ] `Context` is used instead of direct `$_GET`/`$_POST`/`$_SERVER`.
- [ ] Response uses typed classes (`Json`, `Redirect`, `BFile`).
- [ ] Cookies are set via `Cookie` object with `HttpOnly` and `Secure`.
- [ ] Headers are set via `HttpResponse::addHeader`.
- [ ] Input data is treated as untrusted (filtered by dictionary but needs validation).
- [ ] For large files, `BFile` response or `Archive` (mod_zip) is used.

## Encrypted Cookies

`Bitrix\Main\Web\CryptoCookie` stores values encrypted on the client. Requires `crypto` key in `.settings.php`:

```php
'crypto' => [
    'value' => ['crypto_key' => '...'],  // generate a strong random key; keep outside git
    'readonly' => true,
],
```

```php
use Bitrix\Main\Web\Cookie;
use Bitrix\Main\Web\CryptoCookie;
use Bitrix\Main\Context;

$cookie = new CryptoCookie('vendor_token', $token, time() + 86400);
$cookie->setHttpOnly(true);
$cookie->setSecure(true);
$cookie->setSameSite('Lax');

Context::getCurrent()->getResponse()->addCookie($cookie);
```

Reading: `$request->getCookie('vendor_token')` — kernel decrypts automatically when `crypto_key` is configured.

For regular (non-encrypted) cookies use `Bitrix\Main\Web\Cookie` with the same security flags. CSRF and cookie policy details: skill `bitrix-security`. Kernel reference: `bitrix/modules/main/lib/web/cookie.php`, `cryptocookie.php` (if present in the project).
