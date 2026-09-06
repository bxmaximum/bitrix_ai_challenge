# vendor.favorites

Модуль реализует wishlist для каталога товаров на D7:

- для авторизованных пользователей хранит избранное в ORM-таблице;
- для гостей хранит избранное в зашифрованных cookie через `CryptoCookie`;
- при авторизации переносит избранное гостя в БД без дублей;
- предоставляет контроллер `\Vendor\Favorites\Controller\Favorites`;
- устанавливает публичный компонент `vendor:favorites.button`.

## Установка

1. Поместите модуль в `local/modules/vendor.favorites`.
2. Установите модуль в административном разделе Битрикс.
3. Откройте страницу настроек модуля и:
   - включите модуль;
   - выберите инфоблок каталога;
   - задайте срок жизни cookie для гостей.
4. Убедитесь, что у выбранного инфоблока заполнено поле `API_CODE`, иначе D7 ORM для элементов не скомпилируется.

## API

Контроллер вызывается через `BX.ajax.runAction`:

- `vendor:favorites.favorites.add`
- `vendor:favorites.favorites.remove`
- `vendor:favorites.favorites.list`
- `vendor:favorites.favorites.getProducts`

### Пример добавления

```javascript
BX.ajax.runAction('vendor:favorites.favorites.add', {
    data: {
        productId: 123,
        sessid: BX.bitrix_sessid()
    }
});
```

### Формат ответов

- `list` возвращает массив `productIds`;
- `getProducts` возвращает массив `items` c полями `id`, `name`, `code`, `previewText`, `detailText`, `previewPictureSrc`, `detailPictureSrc`;
- `add` и `remove` возвращают текущее состояние кнопки: `isFavorite`, `favoriteIds`, `totalCount`.

## Компонент

```php
<?php
$APPLICATION->IncludeComponent(
    'vendor:favorites.button',
    '.default',
    [
        'PRODUCT_ID' => $arResult['ID'],
        'SHOW_COUNTER' => 'Y',
        'BUTTON_SIZE' => 'medium',
    ]
);
```

## События

При установке модуль регистрирует обработчики:

- `main: OnAfterUserAuthorize` — перенос гостевого избранного в БД;
- `iblock: OnAfterIBlockElementDelete` — удаление товара из избранного всех пользователей;
- `iblock: OnAfterIBlockElementUpdate` — инвалидация тегированного кеша.
