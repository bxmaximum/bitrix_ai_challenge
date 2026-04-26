---
name: bitrix-sessions
description: Покрывает сессии Bitrix — Application::getSession(), getKernelSession(), getLocalSession(), режимы BX_SECURITY_SESSION_READONLY и BX_SECURITY_SESSION_VIRTUAL, хранилища (cache, database, redis, null session handler), separated-режим секции session в .settings.php. Применяется вместо прямого обращения к $_SESSION, при оптимизации AJAX-блокировок по сессии, настройке альтернативных хранилищ и разделении kernel/local-сессий. Ключевые термины — session, getSession, session storage, BX_SECURITY_SESSION_READONLY, separated session, getKernelSession, getLocalSession.
---

# Сессии Bitrix

Прямое обращение к `$_SESSION` ломает нефункциональные режимы (`readonly`, виртуальная сессия, разделённая сессия) и тесты. Используй **Session API**.

```php
use Bitrix\Main\Application;

$session = Application::getInstance()->getSession();

if (!$session->has('cart'))
{
    $session->set('cart', ['items' => []]);
}

$session['cart']['items'][] = $productId;
$session['cart'] = $cart; // set через ArrayAccess

$session->remove('flash_message');
$session->clear();          // всё удалить
```

Интерфейс — `Bitrix\Main\Session\SessionInterface` + `ArrayAccess`.

## Kernel-сессия (hot)

Для малого объёма быстрых данных, к которым ядро обращается почти каждый хит:

```php
$kernelSession = Application::getInstance()->getKernelSession();
$kernelSession->set('UF_LAST_LOGIN', time());
```

В `separated`-режиме kernel хранит горячий фрагмент в зашифрованных cookies — делает авторизацию/CSRF быстрыми без обращения к backend-хранилищу.

## SessionLocalStorage — «сессионный кеш»

Использовать `$session->set(...)` для кеша корзины или временных расчётов плохо: длинные значения блокируют хит, замедляют параллельные AJAX. С `main 20.5.400` есть изолированный контейнер, привязанный к `session_id()`:

```php
$local = Application::getInstance()->getLocalSession('cart');

if (!isset($local['productIds']))
{
    $local->set('productIds', [1, 2, 3]);
    $local->set('total', 42);
}

$ids = $local->get('productIds');
```

- Хранится в кеше из секции `cache` в `.settings.php` (а не в `$_SESSION`).
- Автоматически сохраняется в конце хита.
- При файловом кеше внутри используется `$_SESSION`, чтобы GC корректно чистил устаревшее.

Используй для: корзины, временных фильтров, wizard-ов, UI-черновиков.

## Режимы сессии

### Read-only (неблокирующая)

Подходит для AJAX, где запись не нужна — убирает блокировку записи:

```php
// до подключения prolog
define('BX_SECURITY_SESSION_READONLY', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
```

После этого:

- Сессия читается из redis/memcache/db без `flock`/SETNX — параллельные AJAX не ждут друг друга.
- Изменения **не сохранятся** в конце хита.

Хорошо для вычитывающих эндпоинтов (поиск, подсказки, счётчики).

### Virtual (в памяти)

```php
define('BX_SECURITY_SESSION_VIRTUAL', true);
```

- Сессия создаётся в памяти, в конце хита не сохраняется.
- Применяется для REST-API с авторизацией по токену — авторизация проходит, а сессия не мусорит хранилище.

### Separated — разделённый режим

«Hot» kernel-данные → cookies, «cold» — в backend-хранилище. Включается в `.settings.php`:

```php
'session' => [
    'value' => [
        'mode'     => 'separated',
        'lifetime' => 14400,
        'handlers' => [
            'kernel'  => 'encrypted_cookies',
            'general' => ['type' => 'redis', 'host' => '127.0.0.1', 'port' => 6379],
        ],
    ],
],
```

- Меньше обращений к Redis/БД.
- Подходит для highload: «горячая» часть (`$_SESSION['BX']`) едет в cookie/отдельном kernel-хранилище, «холодная» — в общем бэкенде (Redis/БД).

## Хранилища

Задаются в `/local/.settings.php` (или `/bitrix/.settings.php`) в секции `session.value.handlers.general.type`:

| type | Когда | Замечание |
| --- | --- | --- |
| `file` | Dev, маленькие проекты | Блокировка по `flock` → AJAX тормозит |
| `redis` | Highload, кластеры | Поддерживает `servers` (cluster/single), сериализацию |
| `memcache` | Legacy-проекты | Нет персистентности |
| `database` | Когда нет кеш-серверов | Таблица `b_user_session`, не для highload |

### Пример Redis-кластера (мультимастер)

```php
'session' => [
    'value' => [
        'mode' => 'default',
        'handlers' => [
            'general' => [
                'type' => 'redis',
                'servers' => [
                    ['host' => '10.0.0.1', 'port' => 6379],
                    ['host' => '10.0.0.2', 'port' => 6379],
                    ['host' => '10.0.0.3', 'port' => 6379],
                ],
                'serializer' => \Redis::SERIALIZER_IGBINARY,
                'persistent' => false,
                'failover'   => \RedisCluster::FAILOVER_DISTRIBUTE,
                'timeout'      => null,
                'read_timeout' => null,
            ],
        ],
    ],
],
```

### Пример Memcache-кластера

```php
'handlers' => [
    'general' => [
        'type' => 'memcache',
        'servers' => [
            ['host' => '10.0.0.1', 'port' => 11211, 'weight' => 1],
            ['host' => '10.0.0.2', 'port' => 11211],
        ],
    ],
],
```

### Database

```php
'handlers' => [
    'general' => ['type' => 'database'], // таблица b_user_session
],
```

## Общие опции

```php
'session' => [
    'value' => [
        'lifetime'                 => 14400,  // секунд
        'mode'                     => 'default',
        'regenerateIdAfterLogin'   => true,   // рекомендуется: защита от fixation
        'ignoreSessionStartErrors' => false,  // в true — хит продолжится даже если Redis недоступен
        'handlers' => [ ... ],
    ],
],
```

## Flash-сообщения (частый паттерн)

```php
$session = Application::getInstance()->getSession();
$session->set('flash.success', 'Пост сохранён');

// следующий запрос:
if ($msg = $session->get('flash.success'))
{
    $session->remove('flash.success');
    echo htmlspecialcharsbx($msg);
}
```

## Безопасность

- После успешного логина/смены пароля — `$session->regenerateId()`. Либо `regenerateIdAfterLogin = true` в конфиге.
- Куки сессии должны быть `HttpOnly`, `Secure`, `SameSite=Lax|Strict` — настраивается в модуле main или через `session.cookie_*` php.ini. См. `bitrix-security`.
- Не храни в сессии токены третьих сервисов в чистом виде — используй `Cipher`/`SecretField`.

## Антипаттерны

- `$_SESSION['x'] = ...` вместо `Application::getSession()->set('x', ...)` → ломает `BX_SECURITY_SESSION_READONLY` и тесты.
- Хранение десятков МБ данных (кеш каталога) в `general`-сессии → блокировки AJAX.
- Сессия на `file` для highload с AJAX — всегда упирается в `flock`.
- `session_start()`/`session_write_close()` руками — конфликтует с жизненным циклом ядра.
- Использование `BX_SECURITY_SESSION_READONLY` в эндпоинтах, которые должны писать в сессию → данные молча не сохранятся.

## Чек-лист

- [ ] Работа с сессией — через `Application::getSession()`, а не `$_SESSION`.
- [ ] Кеш-подобные данные — в `getLocalSession('category')`, а не в основной сессии.
- [ ] Highload-окружение использует Redis/Memcache с `mode: separated` и разумным `lifetime`.
- [ ] `regenerateIdAfterLogin = true` включен (в `session.value`).
- [ ] AJAX-эндпоинты без записи помечены `BX_SECURITY_SESSION_READONLY`.
- [ ] REST-API использует `BX_SECURITY_SESSION_VIRTUAL`.
- [ ] Секреты в сессии зашифрованы или вынесены во внешнее хранилище.
