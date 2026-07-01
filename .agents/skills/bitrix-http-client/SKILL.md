---
name: bitrix-http-client
description: Covers Bitrix\Main\Web\HttpClient HTTP client — legacy mode and PSR-18 (sendRequest), asynchronous requests via sendAsyncRequest and Promise, proxies and timeouts, global http_client_options in .settings.php, main.HttpClient logger, SSRF protection and redirects. Applied in integrations with external APIs, webhook clients, asynchronous calls and configuring general HTTP client behavior in a project. Key terms — HttpClient, PSR-18, sendAsyncRequest, Promise, proxy, SSRF, webhook, http_client_options.
---

# HttpClient

`Bitrix\Main\Web\HttpClient` is a built-in client for external HTTP requests. It works in two modes: **legacy** (convenient `get/post/download`) and **PSR-18** (full control, PSR-7/18 compatibility, asynchrony).

## Global Configuration

Default values are in `/local/.settings.php`, `http_client_options` section:

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
        'privateIp'      => false,         // block requests to private IPs
    ],
    'readonly' => false,
],
```

Check: `\Bitrix\Main\Config\Configuration::getValue('http_client_options')`.

The same keys are accepted by `new HttpClient([...])` constructor — constructor overrides global ones.

## Basic Options

- `socketTimeout` — connection timeout (sec), default 30.
- `streamTimeout` — data reading timeout (sec).
- `compress` — accept gzip.
- `redirect`, `redirectMax` — follow redirects (legacy only).
- `useCurl` — use cURL instead of sockets (faster for asynchrony and https).
- `disableSslVerification` — disable SSL verification (use only for debugging).
- `privateIp` — allow requests to private IPs (SSRF protection: disable for client URLs).
- `bodyLengthMax` — response body size limit.
- `waitResponse` — `false` if only headers need to be parsed and connection closed.
- `proxyHost`, `proxyPort`, `proxyUser`, `proxyPassword` — proxy settings.
- `debugLevel` — `HttpDebug::NONE|REQUEST_HEADERS|RESPONSE_HEADERS|ALL`.
- `headers`, `cookies` — default dictionaries (legacy only).

## Legacy Mode

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

### POST Form

```php
$http->post('https://api.example.com/form', ['login' => 'admin', 'pass' => '***']);
```

### POST JSON

```php
$http->setHeader('Content-Type', 'application/json');
$http->setHeader('Authorization', 'Bearer ' . $token);
$response = $http->post('https://api.example.com/users', \Bitrix\Main\Web\Json::encode(['name' => 'Ivan']));
```

### Downloading File

```php
$http->download(
    'https://files.example.com/report.csv',
    $_SERVER['DOCUMENT_ROOT'] . '/upload/tmp/report.csv',
);
```

### Session via Cookie

```php
$http->query('GET', $loginUrl);
$cookies = $http->getCookies()->toArray();
$http->setCookies($cookies);
$http->post($apiUrl, $payload);
```

### Conditional Body Fetch (from 23.300.0)

To avoid downloading megabytes for "reconnaissance":

```php
$http->shouldFetchBody(
    fn (\Bitrix\Main\Web\Http\Response $r) =>
        str_starts_with($r->getHeadersCollection()->getContentType() ?? '', 'application/json')
);
```

## PSR-18 Mode

Build a `Request` and call `sendRequest`:

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
catch (NetworkException $e) { /* connection failed */ }
catch (RequestException $e) { /* incorrect request */ }
catch (ClientException $e) { /* general client error */ }
```

PSR-7 objects are **immutable** — `withHeader`, `withUri`, `withMethod` return a copy.

### File Upload (multipart)

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

### Manual Redirects

In PSR-18, redirects are not followed automatically:

```php
do {
    $response = $http->sendRequest($request);
    if ($response->hasHeader('Location'))
    {
        $request = $request->withUri(new Uri($response->getHeader('Location')[0]));
    }
} while ($response->hasHeader('Location'));
```

## Asynchronous Requests

```php
$promises = [];
foreach ($urls as $url)
{
    $promises[$url] = $http->sendAsyncRequest(new Request(Method::GET, new Uri($url)));
}

foreach ($promises as $url => $promise)
{
    try {
        $response = $promise->wait();
        // ...
    } catch (\Throwable $e) { /* ... */ }
}
```

Wait for all:

```php
use Bitrix\Main\Web\Http\Promise;

Promise::all($promises)->then(
    fn (array $responses) => /* ... */,
    fn (array $errors) => /* ... */
)->wait();
```

## Logging

`HttpClient` uses the PSR-3 logger `main.HttpClient`. Configure in `.settings.php`:

```php
'loggers' => [
    'value' => [
        'main.HttpClient' => [
            'className' => \Bitrix\Main\Diag\FileLogger::class,
            'level'     => \Psr\Log\LogLevel::DEBUG,
            'settings'  => ['file' => 'http_client.log'],
        ],
    ],
],
```

## SSRF Protection

Enabled by default in cloud and modern installations.
- `privateIp` = `false` blocks requests to `127.0.0.1`, `192.168.*`, `10.*`, `169.254.*` (AWS/GCP metadata).
- If your integration requires calling an internal service — explicitly set `'privateIp' => true` in the constructor.

## Checklist

- [ ] `useCurl` is enabled (requires `php-curl`).
- [ ] Timeouts (`socketTimeout`, `streamTimeout`) are set and reasonable.
- [ ] Response status is checked for `2xx` before decoding.
- [ ] SSL verification is **not** disabled in production.
- [ ] SSRF protection is considered when working with user-provided URLs.
- [ ] Binary data/files are downloaded via `download()` or streams, not read entirely into memory.

See skill `bitrix-security` for SSRF protection details.
