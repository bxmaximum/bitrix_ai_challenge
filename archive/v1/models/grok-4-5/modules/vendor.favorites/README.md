# vendor.favorites — Избранное товаров

Модуль wishlist для каталога 1С-Битрикс: БД для авторизованных пользователей, `CryptoCookie` для гостей, REST/AJAX API и публичный компонент кнопки.

## Требования

- Bitrix 23.x+ (рекомендуется 25.x)
- PHP 8.2+
- Модули: `main`, `iblock` (опционально `catalog` для цен)
- В `/bitrix/.settings.php` должен быть задан `crypto.crypto_key` (для `CryptoCookie`)

## Установка

1. Скопируйте модуль в `/local/modules/vendor.favorites/` (уже в проекте).
2. В админке: **Настройки → Настройки продукта → Модули** → установите **«Избранное товаров»**.
3. Установщик:
   - создаёт таблицу `b_vendor_favorites` через ORM;
   - регистрирует события;
   - копирует компонент в `/local/components/vendor/favorites.button/`.
4. Откройте **Настройки → Настройки модулей → Избранное товаров**:
   - включите функционал;
   - выберите инфоблок каталога;
   - при необходимости задайте TTL cookie гостя.

Рекомендуется указать **API_CODE** у инфоблока — тогда выборка товаров идёт через `\Bitrix\Iblock\Elements\Element{ApiCode}Table`.

## API (Engine Controller)

Базовый URL:

```
/bitrix/services/main/ajax.php?action=vendor:favorites.favorites.<action>
```

| Action | Метод | Описание |
|--------|-------|----------|
| `add` | POST | Добавить товар (`productId`) |
| `remove` | POST | Удалить товар (`productId`) |
| `list` | GET/POST | Список ID избранного |
| `getProducts` | GET/POST | Список товаров с данными |
| `status` | GET/POST | Состояние кнопки (`inFavorites`, `count`) |

Все методы защищены CSRF (`sessid`). Пример:

```js
BX.ajax.runAction('vendor:favorites.favorites.add', {
  data: { productId: 42 }
}).then((response) => console.log(response.data));
```

## Компонент

```php
$APPLICATION->IncludeComponent(
    'vendor:favorites.button',
    '.default',
    [
        'PRODUCT_ID' => (int)$arResult['ID'],
        'SHOW_COUNTER' => 'N',
        'BUTTON_SIZE' => 'medium', // small|medium|large
    ]
);
```

Компонент использует AJAX без перезагрузки, анимации и поддержку светлой/тёмной темы (`prefers-color-scheme`).

## Хранение и миграция

| Пользователь | Хранилище |
|--------------|-----------|
| Авторизованный | ORM `FavoritesTable` (`b_vendor_favorites`) |
| Гость | `CryptoCookie` `VENDOR_FAVORITES` (JSON-массив ID) |

При событии `OnAfterUserAuthorize` избранное из cookie переносится в БД без дубликатов, cookie очищается.

## События

| Событие | Действие |
|---------|----------|
| `main:OnAfterUserAuthorize` | Миграция cookie → БД (только в эту сторону) |
| `iblock:OnAfterIBlockElementDelete` | Удаление товара из избранного всех пользователей |
| `iblock:OnAfterIBlockElementUpdate` | Инвалидация тегированного кэша |

## Кэширование

- Список ID авторизованного пользователя — тегированный кэш (`vendor_favorites_user_{id}`, `iblock_id_{id}`, `vendor_favorites_product_{id}`).
- `getProducts` — отдельный тегированный кэш списка карточек товаров.
- При изменении/удалении элемента инфоблока кэш сбрасывается обработчиками событий.

## Структура

```
vendor.favorites/
├── install/
│   ├── components/vendor/favorites.button/
│   ├── index.php
│   ├── unstep1.php
│   └── version.php
├── lib/
│   ├── Config/ModuleOptions.php
│   ├── Controller/Favorites.php
│   ├── Model/FavoritesTable.php
│   ├── Repository/FavoritesRepository.php
│   ├── Service/
│   │   ├── CookieService.php
│   │   ├── FavoritesService.php
│   │   └── ProductService.php
│   └── EventHandler.php
├── lang/
├── options.php
├── include.php
├── .settings.php
└── README.md
```

## Удаление

При удалении модуля можно сохранить таблицу и настройки (чекбокс на шаге uninstall). Компонент удаляется из `/local/components/vendor/favorites.button/`.
