# vendor.favorites — модуль «Избранное» для каталога

Модуль Wishlist для 1С-Битрикс: избранные товары для авторизованных пользователей (БД + ORM) и гостей (шифрованные `CryptoCookie`), REST API, публичный компонент кнопки.

## Требования

- Bitrix 23.x+, PHP 8.2+
- Модуль `iblock`
- В настройках инфоблока каталога указан **Символьный код API** (для `\Bitrix\Iblock\Elements`)
- В `/bitrix/.settings.php` задан `crypto_key` (для `CryptoCookie`)

## Установка

1. Скопируйте модуль в `/local/modules/vendor.favorites/`.
2. **Настройки → Настройки продукта → Модули** → установите **«Избранное для каталога»**.
3. **Настройки → Настройки продукта → Настройки модулей → vendor.favorites**:
   - выберите инфоблок каталога;
   - задайте TTL cookie для гостей;
   - включите модуль.

Компонент `vendor:favorites.button` копируется в `/local/components/vendor/favorites.button/`.

## REST API (Engine\Controller)

| Метод | Действие `BX.ajax.runAction` | Параметры |
|-------|------------------------------|-----------|
| POST | `vendor:favorites.favorites.add` | `productId` (int) |
| POST | `vendor:favorites.favorites.remove` | `productId` (int) |
| GET | `vendor:favorites.favorites.list` | — |
| GET | `vendor:favorites.favorites.getProducts` | — |

POST-методы защищены CSRF (`sessid`). Для гостей фильтр авторизации не требуется.

### Пример

```javascript
BX.ajax.runAction('vendor:favorites.favorites.add', {
    data: { productId: 123 },
}).then((response) => console.log(response.data));
```

## Компонент на странице товара

```php
<?php
$APPLICATION->IncludeComponent(
    'vendor:favorites.button',
    '.default',
    [
        'PRODUCT_ID' => (int) $arResult['ID'],
        'SHOW_COUNTER' => 'N',
        'BUTTON_SIZE' => 'medium',
    ],
);
```

Параметры: `PRODUCT_ID`, `SHOW_COUNTER` (Y/N), `BUTTON_SIZE` (`small` | `medium` | `large`).

## События

| Событие | Действие |
|---------|----------|
| `main:OnAfterUserAuthorize` | перенос избранного из cookie в БД без дублей |
| `iblock:OnAfterIBlockElementDelete` | удаление товара из избранного всех пользователей |
| `iblock:OnAfterIBlockElementUpdate` | сброс тегированного кэша товара |

## Структура

```
local/modules/vendor.favorites/
├── install/          # установщик, компонент
├── lib/
│   ├── Controller/   # REST
│   ├── Service/      # бизнес-логика
│   ├── Repository/   # БД + кэш
│   ├── Model/        # FavoritesTable (ORM)
│   └── EventHandler.php
├── options.php
└── .settings.php     # DI, контроллеры
```

## Таблица БД

`b_vendor_favorites`: `USER_ID`, `PRODUCT_ID`, `DATE_CREATE`, уникальный индекс `(USER_ID, PRODUCT_ID)`.
