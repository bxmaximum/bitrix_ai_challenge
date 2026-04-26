---
name: bitrix-caching
description: Покрывает кеширование в Bitrix — Cache (неуправляемый), ManagedCache, TaggedCache, автокеш ORM, кеш компонентов через startResultCache/endResultCache, композитный сайт, настройка cache engine (files, memcached, redis) в .settings.php. Применяется при оптимизации производительности, инвалидации по тегам и событиям, настройке TTL, cache warm-up и отладке попаданий в кеш. Ключевые термины — cache, invalidate, TaggedCache, ManagedCache, startResultCache, cacheDir, clean.
---

# Кеширование в Bitrix

## Уровни кеша

1. **Неуправляемый кеш** (`Bitrix\Main\Data\Cache`) — с TTL, ключом и путём. Сбрасывается сам по TTL и вручную.
2. **Управляемый кеш** (`ManagedCache`) — живёт до явной инвалидации, удобен для «редко меняющихся» данных.
3. **Теговый кеш** (`TaggedCache`) — ключи группируются тегами; инвалидация одного тега сбрасывает все привязанные записи.
4. **Кеш ORM** — автоматический: `isCacheable()` у таблета + `['cache' => ['ttl' => ...]]` в `getList`.
5. **Кеш компонентов** — через `startResultCache()` / `endResultCache()` и параметры `CACHE_TYPE`, `CACHE_TIME`, `CACHE_GROUPS`.
6. **Composite-кеш** — HTML-кеш страницы целиком (`Bitrix\Main\Composite\Engine`).

## Конфигурация `.settings.php`

```php
'cache' => [
    'value' => [
        'type' => [
            // 'class_name' => \Bitrix\Main\Data\CacheEngineRedis::class, // одно из
            'type' => 'redis',     // files|memcache|redis|apc|xcache|none
            'host' => '127.0.0.1',
            'port' => 6379,
            'serializer' => \Redis::SERIALIZER_IGBINARY,
        ],
        'sid' => 'PROJECT_',       // префикс ключей
        'cache_flags' => [
            'config_options' => 3600,
            'site_template' => 3600,
            'iblock_include' => 3600,
        ],
    ],
    'readonly' => false,
],
```

Разные секции (`config_options`, `menu`, `site_template`, ) задают TTL для внутренних кешей ядра.

## Неуправляемый кеш — шаблон

```php
$cache = \Bitrix\Main\Data\Cache::createInstance();
$ttl = 3600;
$cacheId = 'posts_list_' . md5(serialize($filter));
$cacheDir = '/vendor_blog/posts';

if ($cache->initCache($ttl, $cacheId, $cacheDir))
{
    $data = $cache->getVars();
}
elseif ($cache->startDataCache())
{
    $data = PostTable::getList([
        'filter' => $filter,
        'select' => ['ID', 'TITLE'],
    ])->fetchAll();

    // Если условия не подходят — прекратить записывать кеш:
    if (empty($data))
    {
        $cache->abortDataCache();
    }
    else
    {
        $cache->endDataCache($data);
    }
}
```

- `cacheId` — уникальный ключ, включает все переменные, влияющие на результат.
- `cacheDir` — «папка» кеша; удобно сбрасывать по директории `$cache->cleanDir($cacheDir)`.

## Управляемый кеш

```php
$managed = \Bitrix\Main\Application::getInstance()->getManagedCache();

if ($managed->read(86400, $cacheId, 'posts'))
{
    $data = $managed->get($cacheId);
}
else
{
    $data = $this->fetchExpensive();
    $managed->setImmediate($cacheId, $data); // или set() — запись в конце запроса
}

// Инвалидация:
$managed->clean($cacheId, 'posts');
$managed->cleanDir('posts');
```

## Теги

```php
use Bitrix\Main\Application;

$taggedCache = Application::getInstance()->getTaggedCache();

$taggedCache->startTagCache('/vendor_blog/posts');
$taggedCache->registerTag('posts_list');
$taggedCache->registerTag('post_42');
$taggedCache->endTagCache();

// Инвалидация тега — сбросит все записи, зарегистрированные под этим тегом:
$taggedCache->clearByTag('posts_list');
```

### Теги ORM

Любой `*Table`-класс с `isCacheable() === true` автоматически публикует тег `ORM_<TABLE_NAME>` при записи. Это позволяет привязать зависимые HTML-кеши к таблице:

```php
$taggedCache->registerTag('ORM_VENDOR_MODULE_POST'); // при изменении таблицы кеш сбросится
```

## Кеш запросов ORM

```php
PostTable::getList([
    'select' => ['*'],
    'filter' => ['=ACTIVE' => 'Y'],
    'cache'  => [
        'ttl' => 3600,
        'cache_joins' => true, // кешировать JOIN-запросы
    ],
]);
```

Сброс: `PostTable::cleanCache()`.

## Кеш компонентов

В `class.php` / `component.php`:

```php
if ($this->startResultCache(false, [
    $USER->IsAuthorized(),
    $arParams['SECTION_ID'],
]))
{
    $this->arResult['ITEMS'] = $this->fetchItems();
    $this->includeComponentTemplate();
}
```

Параметры компонента, управляющие кешем:

- `CACHE_TYPE`: `A` (автокеш), `Y`, `N`.
- `CACHE_TIME`: TTL в секундах.
- `CACHE_GROUPS`: `Y` — ключ зависит от групп пользователя.

## Composite-кеш

Для композита — включи модуль `compression` и добавь в нужные компоненты:

```php
\CBitrixComponent::includeComponentClass($componentName);
\Bitrix\Main\Page\Asset::getInstance()->addString(...);
```

Учитывай ограничения композита: динамические блоки помечаются через `$APPLICATION->SetPageProperty('composite_frame_mode', 'Y')` / `setFrameMode`, личные данные не должны попадать в статическую часть, AJAX-компоненты (`composite:banner` и пр.) вычитываются отдельным запросом.

## Инвалидация по событиям

Типичная схема: обработчик `OnAfterUpdate`/`OnAfter*` у таблета вызывает `$taggedCache->clearByTag(...)`. Используй новые события ORM через `EventResult` вместо `$GLOBALS['USER_FIELD_MANAGER']->...`.

## Антипаттерны

- Кеширование «живых» данных (балансы, остатки) с большим TTL без инвалидации.
- Использование глобальных `$_SESSION`/`$USER` внутри ключа кеша вместо явных переменных.
- Один общий `cacheDir` на все модули — трудно чистить прицельно.
- Отсутствие `abortDataCache()` при пустых/ошибочных результатах.
- Включение `composite` без тестирования динамических блоков.

## Чек-лист

- [ ] Подобран уровень кеша: краткоживущий → неуправляемый; редко меняющийся и критичный → управляемый + теги.
- [ ] Ключ кеша включает все параметры, влияющие на результат (фильтры, язык, права).
- [ ] Инвалидация кеша автоматизирована через теги/события, а не `cleanDir('/')` вручную.
- [ ] Компонентный кеш учитывает группы пользователя, где это важно.
- [ ] Production-окружение использует Redis/Memcached, а не файловый кеш.
