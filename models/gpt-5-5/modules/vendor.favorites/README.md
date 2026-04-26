# Vendor Favorites

Модуль `vendor.favorites` добавляет избранное для товаров каталога:

- авторизованные пользователи хранят избранное в ORM-таблице `vendor_favorites_favorite`;
- гости хранят список товаров в шифрованной `CryptoCookie`;
- при авторизации гостевое избранное переносится в базу без дублей;
- API доступно через D7 controller;
- компонент `vendor:favorites.button` выводит современную AJAX-кнопку для карточки товара.

## Установка

1. Скопируйте модуль в `/local/modules/vendor.favorites/`.
2. Установите модуль в административном разделе: `Настройки -> Настройки продукта -> Модули`.
3. Откройте настройки модуля и выберите инфоблок каталога товаров.

При установке модуль создаёт таблицу, индексы и копирует компонент в `/local/components/vendor/favorites.button/`.

## API

Все методы вызываются через `/bitrix/services/main/ajax.php`.

```js
BX.ajax.runAction('vendor:favorites.favorites.add', {
    data: { productId: 123 }
});

BX.ajax.runAction('vendor:favorites.favorites.remove', {
    data: { productId: 123 }
});

BX.ajax.runAction('vendor:favorites.favorites.list');
BX.ajax.runAction('vendor:favorites.favorites.getProducts');
```

`POST`-методы защищены стандартным CSRF-фильтром Bitrix.

## Компонент

```php
$APPLICATION->IncludeComponent(
    'vendor:favorites.button',
    '.default',
    [
        'PRODUCT_ID' => $arResult['ID'],
        'SHOW_COUNTER' => 'N',
        'BUTTON_SIZE' => 'medium',
    ]
);
```

Параметры:

- `PRODUCT_ID` — ID товара.
- `SHOW_COUNTER` — `Y`/`N`, показывать счётчик добавлений авторизованными пользователями.
- `BUTTON_SIZE` — `small`, `medium` или `large`.

## События

Модуль регистрирует обработчики:

- `main:OnAfterUserAuthorize` — переносит товары из cookie в БД;
- `iblock:OnAfterIBlockElementDelete` — удаляет товар из избранного всех пользователей;
- `iblock:OnAfterIBlockElementUpdate` — сбрасывает тегированный кеш товара.
