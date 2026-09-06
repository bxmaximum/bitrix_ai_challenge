# Модуль `vendor.favorites`

Избранное (wishlist) для товаров каталога: REST API на `\Bitrix\Main\Engine\Controller`, ORM-таблица для авторизованных пользователей, зашифрованные cookie для гостей (`\Bitrix\Main\Web\CryptoCookie`), тегированный кэш, публичный компонент `vendor:favorites.button`.

## Установка

1. Скопируйте каталог `vendor.favorites` в `/local/modules/`.
2. В админке: **Настройки → Настройки продукта → Модули** — установите модуль **Избранное (каталог)**.  
   Требуется модуль **Инфоблоки** (`iblock`).
3. На странице настроек модуля укажите **ID инфоблока каталога**, при необходимости измените TTL cookie и кэша.
4. Убедитесь, что в `/bitrix/.settings.php` задан `crypto_key` (для `CryptoCookie`).

## Настройки

| Опция | Описание |
|--------|----------|
| ID инфоблока каталога | Только элементы этого инфоблока можно добавлять в избранное |
| Время жизни cookie гостя | Секунды |
| TTL кэша списка | Секунды, кэш списка ID для авторизованных |
| Включить модуль | Глобальное отключение API и компонента |

## REST / AJAX

Вызов через `BX.ajax.runAction` (префикс действия строится по классу контроллера):

| Действие | HTTP | Параметры |
|----------|------|-----------|
| `vendor:favorites.Controller.Favorites.add` | POST | `productId` |
| `vendor:favorites.Controller.Favorites.remove` | POST | `productId` |
| `vendor:favorites.Controller.Favorites.list` | GET | — |
| `vendor:favorites.Controller.Favorites.getProducts` | GET | — |

В `/local/routes/web.php` заданы URL: `/api/favorites/add/`, `/api/favorites/remove/`, `/api/favorites/`, `/api/favorites/products/`.

Проверяются CSRF-фильтром (`sessid`), для гостей авторизация не требуется.

## Компонент

```php
$APPLICATION->IncludeComponent(
    'vendor:favorites.button',
    '',
    [
        'PRODUCT_ID' => (int) $arResult['ID'],
        'SHOW_COUNTER' => 'N',
        'BUTTON_SIZE' => 'medium',
    ]
);
```

После установки модуля файлы копируются в `/local/components/vendor/favorites.button/`.

### Кэш родительского компонента

Если кнопка подключается внутри шаблона **другого закэшированного** компонента, в HTML попадает «запечённое» состояние избранного. По умолчанию включено **`SYNC_STATE_ON_CLIENT = Y`**: в разметке выводится нейтральное состояние (не в избранном, счётчик 0), после загрузки страницы **одним запросом** `favorites.list` подтягивается актуальный список ID и обновляются все кнопки `.vendor-fav-wrap` на странице. Отключайте только если родительский кэш выключен и нужен чисто серверный рендер (`SYNC_STATE_ON_CLIENT = N`).

## События

Обработчики регистрируются при установке модуля:

- `main` / `OnAfterUserAuthorize` — перенос избранного из cookie в БД без дубликатов.
- `iblock` / `OnAfterIBlockElementDelete` — удаление записей с этим товаром.
- `iblock` / `OnAfterIBlockElementUpdate` — инвалидация тега кэша по элементу.

## Технические детали

- Таблица БД: `b_vendor_favorites` (поля `USER_ID`, `PRODUCT_ID`, уникальный индекс по паре).
- Проверка товара: `\Bitrix\Iblock\ElementTable` в инфоблоке из настроек, только активные элементы.
- Кэш: теги `vendor_favorites_u_{userId}` и `vendor_fav_el_{elementId}`.
