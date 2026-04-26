---
name: bitrix-http-client
description: Покрывает HTTP-клиент Bitrix\Main\Web\HttpClient — legacy-режим и PSR-18 (sendRequest), асинхронные запросы через sendAsyncRequest и Promise, прокси и таймауты, глобальные http_client_options в .settings.php, логгер main.HttpClient, SSRF-защита и редиректы. Применяется при интеграциях с внешними API, webhook-клиентах, асинхронных обращениях и настройке общего поведения HTTP-клиента на проекте. Ключевые термины — HttpClient, PSR-18, sendAsyncRequest, Promise, proxy, SSRF, webhook, http_client_options.
---

# HttpClient

`Bitrix\Main\Web\HttpClient` — встроенный клиент для внешних HTTP-запросов. Работает в двух режимах: **legacy** (удобный `get/post/download`) и **PSR-18** (полный контроль, совместимость с PSR-7/18, асинхронность).

## Глобальная конфигурация

Значения по умолчанию — в `/local/.settings.php`, секция `http_client_options`:

```php
'http_client_options' => [
    'value' => [
        'socketTimeout'  => 10,
        'streamTimeout'  => 30,
        'useCurl'        => true,
        'compress'       => true,
        'redirect'       => true,
        'redirectMax'    => 5,
        'bodyLengthMax'  => 10 * 1024 * 1024,
        'disableSslVerification' => false,
        'privateIp'      => false,         // блокировать обращения к приватным IP
    ],
    'readonly' => false,
],
```

Проверка: `\Bitrix\Main\Config\Configuration::getValue('http_client_options')`.

Эти же ключи принимает конструктор `new HttpClient([...])` — конструктор перекрывает глобальные.

## Основные опции

- `socketTimeout` — таймаут коннекта (сек), по умолчанию 30.
- `streamTimeout` — таймаут чтения данных (сек).
- `compress` — принимать gzip.
- `redirect`, `redirectMax` — следование редиректам (только legacy).
- `useCurl` — использовать cURL вместо сокетов (быстрее для асинхронки и https).
- `disableSslVerification` — выключить проверку SSL (используй только для отладки).
- `privateIp` — разрешать запросы к приватным IP (SSRF-защита: выключи для клиентских URL).
- `bodyLengthMax` — ограничение размера тела ответа.
- `waitResponse` — `false`, если нужно только разобрать заголовки и закрыть соединение.
- `proxyHost`, `proxyPort`, `proxyUser`, `proxyPassword` — прокси.
- `debugLevel` — `HttpDebug::NONE|REQUEST_HEADERS|RESPONSE_HEADERS|ALL`.
- `headers`, `cookies` — словари дефолтов (только legacy).

## Legacy-режим

### GET

```php
use Bitrix\Main\Web\HttpClient;

$http = new HttpClient([
    'compress' => true,
    'headers'  => ['User-Agent' => 'VendorBot/1.0'],
    'socketTimeout' => 5,
    'streamTimeout' => 15,
]);

$body = $http->get('https://api.example.com/items');

if ($body === false)
{
    throw new \RuntimeException('HTTP error: ' . $http->getError()[0] ?? 'unknown');
}

$status  = $http->getStatus();        // int
$headers = $http->getHeaders();       // HttpHeaders
$data    = \Bitrix\Main\Web\Json::decode($body);
```

### POST формы

```php
$http->post('https://api.example.com/form', ['login' => 'admin', 'pass' => '***']);
```

### POST JSON

```php
$http->setHeader('Content-Type', 'application/json');
$http->setHeader('Authorization', 'Bearer ' . $token);
$response = $http->post('https://api.example.com/users', \Bitrix\Main\Web\Json::encode(['name' => 'Ivan']));
```

### Скачивание файла

```php
$http->download(
    'https://files.example.com/report.csv',
    $_SERVER['DOCUMENT_ROOT'] . '/upload/tmp/report.csv',
);
```

### Сессия по cookie

```php
$http->query('GET', $loginUrl);
$cookies = $http->getCookies()->toArray();
$http->setCookies($cookies);
$http->post($apiUrl, $payload);
```

### Условное чтение тела (с 23.300.0)

Чтобы не качать мегабайты для «разведки»:

```php
$http->shouldFetchBody(
    fn (\Bitrix\Main\Web\Http\Response $r) =>
        str_starts_with($r->getHeadersCollection()->getContentType() ?? '', 'application/json')
);
```

## PSR-18 режим

Построй `Request` и отправь `sendRequest`:

```php
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Web\Http\{Request, Method, Stream, ClientException, NetworkException, RequestException};

$http = new HttpClient(['compress' => true, 'useCurl' => true]);

$body = new Stream('php://temp', 'r+');
$body->write(\Bitrix\Main\Web\Json::encode(['id' => 42]));
$body->rewind();

$request = (new Request(
    Method::POST,
    new Uri('https://api.example.com/items'),
    ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
    $body,
));

try
{
    $response = $http->sendRequest($request);

    $status = $response->getStatusCode();
    $payload = \Bitrix\Main\Web\Json::decode((string)$response->getBody());
}
catch (NetworkException $e) { /* не достучались */ }
catch (RequestException $e) { /* запрос некорректен */ }
catch (ClientException $e) { /* общая ошибка клиента */ }
```

Объекты PSR-7 **immutable** — `withHeader`, `withUri`, `withMethod` возвращают копию.

### Загрузка файла (multipart)

```php
use Bitrix\Main\Web\Http\MultipartStream;

$fh = fopen('/tmp/report.pdf', 'r');
$body = new MultipartStream([
    'title' => 'Monthly report',
    'file'  => ['resource' => $fh, 'filename' => 'report.pdf'],
]);

$request = new Request(
    Method::POST,
    new Uri('https://api.example.com/upload'),
    ['Content-Type' => 'multipart/form-data; boundary=' . $body->getBoundary()],
    $body,
);

$response = $http->sendRequest($request);
fclose($fh);
```

### Редиректы вручную

В PSR-18 редиректы не следуются автоматически:

```php
do {
    $response = $http->sendRequest($request);
    if ($response->hasHeader('Location'))
    {
        $request = $request->withUri(new Uri($response->getHeader('Location')[0]));
    }
} while ($response->hasHeader('Location'));
```

## Асинхронные запросы

```php
$promises = [];
foreach ($urls as $url)
{
    $promises[$url] = $http->sendAsyncRequest(new Request(Method::GET, new Uri($url)));
}

foreach ($promises as $url => $promise)
{
    try
    {
        $response = $promise->wait();
        $this->logger->info("$url => {$response->getStatusCode()}");
    }
    catch (\Bitrix\Main\Web\Http\ClientException $e)
    {
        $this->logger->warning("$url failed: {$e->getMessage()}");
    }
}
```

С callback-цепочками:

```php
foreach ($urls as $url)
{
    $http->sendAsyncRequest(new Request(Method::GET, new Uri($url)))
        ->then(
            fn ($r) => $this->logger->info((string)$r->getStatusCode()),
            fn (\Throwable $e) => $this->logger->error($e->getMessage()),
        );
}
$http->wait();
```

Без `wait()` очередь выполнится в фоновом задании ядра (`addBackgroundJob` автоматически) — удобно для «выстрелил и забыл» (вебхуки, аналитика).

## Прокси и cURL

```php
$http = new HttpClient([
    'useCurl'   => true,
    'proxyHost' => 'proxy.internal',
    'proxyPort' => 8080,
    'proxyUser' => 'login',
    'proxyPassword' => 'secret',
    'curlLogFile' => '/var/log/httpclient-curl.log',
]);
```

- HTTP прокси — прямой запрос с полным URI.
- HTTPS прокси — `CONNECT`-туннель, затем TLS.

Для нестабильных прокси предпочтителен `useCurl = true`.

## Событие `OnHttpClientBuildRequest`

Срабатывает перед каждой отправкой (PSR-18 и legacy) в рамках одного процесса. Удобно вешать общие заголовки/подписи:

```php
\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'main',
    'OnHttpClientBuildRequest',
    static function (\Bitrix\Main\Web\Http\RequestEvent $event): void
    {
        $request = $event->getRequest()->withHeader('X-Project', 'Bitrix24');
        $event->addResult(new \Bitrix\Main\Web\Http\RequestEventResult($request));
    },
);
```

Событие включено, пока у клиента не снят флаг `sendEvents` (по умолчанию `true`).

## Логирование (PSR-3 через `loggers`)

В `.settings.php`:

```php
'loggers' => [
    'value' => [
        'main.HttpClient' => [
            'constructor' => static function (
                \Bitrix\Main\Web\Http\DebugInterface $debug,
                \Psr\Http\Message\RequestInterface $request,
            ): \Psr\Log\LoggerInterface {
                $debug->setDebugLevel(\Bitrix\Main\Web\HttpDebug::ALL);

                return new \Bitrix\Main\Diag\FileLogger(
                    '/var/log/bitrix/http-' . spl_object_hash($request) . '.log',
                );
            },
            'level' => \Psr\Log\LogLevel::DEBUG,
        ],
    ],
    'readonly' => false,
],
```

См. `bitrix-logger` для общего формата логгеров.

## Безопасность (SSRF и SSL)

- Для клиентских URL **обязательно** `privateIp => false` и белый список доменов.
- Никогда не выключай `disableSslVerification` в проде.
- Ограничь `bodyLengthMax`, если принимаешь произвольные URL.
- Не передавай секреты через query-string — только заголовки/тело.
- Для URL из пользовательского ввода валидируй схему (`http/https` только), хост и порт ДО отправки.

См. `bitrix-security`.

## Чек-лист

- [ ] Для каждого клиента заданы `socketTimeout` и `streamTimeout`.
- [ ] Для запросов к внешним сервисам используется PSR-18 + типизированные исключения (`NetworkException`, `RequestException`, `ClientException`).
- [ ] Большие/нестабильные запросы идут через `useCurl => true`.
- [ ] Пользовательские URL проходят валидацию схемы/хоста, `privateIp = false`.
- [ ] Секреты в заголовках, не в URL.
- [ ] Ошибки логируются через PSR-3 (`main.HttpClient` или собственный `LoggerInterface` в сервисе).
- [ ] Для массовых запросов — `sendAsyncRequest` + `Promise::wait()`.
