# vendor.favorites — модуль «Избранное» (Wishlist)

## Установка

1. Скопируй модуль в `local/modules/vendor.favorites/` (в этом проекте: `www/local/modules/vendor.favorites/`).
2. В админке Битрикс: **Marketplace → Установленные решения → vendor.favorites → Установить**.
3. Открой настройки модуля: **Настройки → Настройки продукта → Настройки модулей → Избранное (Wishlist)** и:
   - выбери инфоблок каталога;
   - укажи TTL cookie для гостей;
   - включи модуль.

## REST API (D7 Controller)

Экшены доступны через `BX.ajax.runAction`:

- `vendor:favorites.favorites.add` (POST, CSRF) — добавить в избранное
  - `productId` (int > 0)
- `vendor:favorites.favorites.remove` (POST, CSRF) — удалить из избранного
  - `productId` (int > 0)
- `vendor:favorites.favorites.list` (GET) — список ID
- `vendor:favorites.favorites.getProducts` (GET) — список товаров (ID/NAME/DETAIL_PAGE_URL)

## Публичный компонент

`vendor:favorites.button` — кнопка на странице товара.

Пример:

```php
<?php
$APPLICATION->IncludeComponent(
    'vendor:favorites.button',
    '.default',
    [
        'PRODUCT_ID' => $arResult['ID'],
        'SHOW_COUNTER' => 'N',
        'BUTTON_SIZE' => 'medium',
    ]
);
?>
```

## Хранение данных

- **Гость**: зашифрованная cookie через `\Bitrix\Main\Web\CryptoCookie`
- **Авторизованный**: таблица `b_vendor_favorites` (ORM `Vendor\Favorites\Model\FavoritesTable`)

## События

- `main:OnAfterUserAuthorize` — миграция избранного из cookie в БД (без дублей)
- `iblock:OnAfterIBlockElementDelete` — удаление товара из избранного всех пользователей + сброс кэша
- `iblock:OnAfterIBlockElementUpdate` — инвалидация кэша по тегу инфоблока


