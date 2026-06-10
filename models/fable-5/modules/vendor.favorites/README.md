# Модуль «Избранное» (vendor.favorites)

Функционал Wishlist для каталога товаров на 1С-Битрикс (D7).

## Возможности

- Добавление/удаление товаров в избранное, список избранного.
- Хранение: **авторизованные** — собственная таблица `vendor_favorites` (ORM DataManager); **гости** — шифрованная cookie (`CryptoCookie`).
- Автоматическая миграция избранного из cookie в БД при авторизации (без дубликатов).
- REST API на `\Bitrix\Main\Engine\Controller` с CSRF-защитой и валидацией.
- Публичный компонент `vendor:favorites.button` с AJAX, анимацией, тёмной темой и адаптивностью.
- Тегированный кэш D7 с инвалидацией при изменении/удалении элементов инфоблока.
- Страница настроек модуля в админке.

## Требования

- Bitrix 23.x+ (main), модуль `iblock`;
- PHP 8.2+;
- `crypto_key` в `bitrix/.settings.php` (нужен для `CryptoCookie`).

## Установка

1. Скопируйте папку модуля в `/local/modules/vendor.favorites/` (уже сделано).
2. Админка → **Marketplace → Установленные решения** → «Избранное (Wishlist)» → **Установить**.
   При установке: регистрируется модуль, создаётся таблица `vendor_favorites`
   с уникальным индексом `(USER_ID, PRODUCT_ID)`, регистрируются обработчики событий,
   компонент копируется в `/local/components/vendor/favorites.button/`.
3. Админка → **Настройки → Настройки продукта → Настройки модулей → Избранное (Wishlist)**:
   - выберите инфоблок каталога товаров (обязательно);
   - задайте время жизни cookie гостя (дней);
   - включите функционал.

## REST API

Вызов через `/bitrix/services/main/ajax.php?action=...` или `BX.ajax.runAction`.

| Метод | Action | Параметры | Описание |
|-------|--------|-----------|----------|
| POST | `vendor:favorites.Favorites.add` | `productId` (int > 0) | Добавить товар в избранное |
| POST | `vendor:favorites.Favorites.remove` | `productId` (int > 0) | Удалить товар из избранного |
| GET | `vendor:favorites.Favorites.list` | — | Список ID избранных товаров |
| GET | `vendor:favorites.Favorites.getProducts` | — | Список товаров с данными (NAME, картинка и т.д.) |

POST-методы защищены CSRF (`X-Bitrix-Csrf-Token` подставляется `BX.ajax.runAction` автоматически).
Перед добавлением проверяется существование активного элемента в настроенном инфоблоке.

```js
BX.ajax.runAction('vendor:favorites.Favorites.add', {
    data: { productId: 42 }
}).then((response) => console.log(response.data)); // { added: true, count: 7 }
```

## Компонент

```php
<?php
// На странице товара (detail.php)
$APPLICATION->IncludeComponent(
    'vendor:favorites.button',
    '.default',
    [
        'PRODUCT_ID' => $arResult['ID'],
        'SHOW_COUNTER' => 'N',          // Y — показать счётчик добавлений
        'BUTTON_SIZE' => 'medium',      // small | medium | large
    ]
);
```

Кнопка-«сердечко» работает через AJAX без перезагрузки, показывает текущее состояние,
анимируется при клике, поддерживает светлую/тёмную темы (`prefers-color-scheme`,
а также классы `.dark` / `[data-theme="dark"]`) и адаптивна под мобильные.

## Архитектура

```
lib/
├── Controller/Favorites.php      # REST-контроллер (тонкий)
├── Service/FavoritesService.php  # Бизнес-логика, кэширование, валидация
├── Service/CookieService.php     # CryptoCookie гостя (JSON-список ID)
├── Repository/FavoritesRepository.php  # Доступ к БД (только ORM)
├── Model/FavoritesTable.php      # ORM DataManager (vendor_favorites)
└── EventHandler.php              # Обработчики событий
```

Сервисы зарегистрированы в `ServiceLocator` (`.settings.php`):
`vendor.favorites.favoritesService`, `vendor.favorites.favoritesRepository`, `vendor.favorites.cookieService`.

### Кэширование

- Список ID авторизованного пользователя и данные товаров кэшируются `Bitrix\Main\Data\Cache` (TTL 1 час).
- Теги: `vendor_favorites_user_{USER_ID}` (сброс при изменении избранного) и
  `vendor_favorites_iblock_{IBLOCK_ID}` (сброс при изменении/удалении элемента инфоблока).

### События

| Событие | Действие |
|---------|----------|
| `main:OnAfterUserAuthorize` | Миграция избранного из cookie в БД, очистка cookie |
| `iblock:OnAfterIBlockElementDelete` | Удаление товара из избранного всех пользователей + сброс кэша |
| `iblock:OnAfterIBlockElementUpdate` | Инвалидация тегированного кэша |

## Безопасность

- Cookie гостя шифруется ядром (`CryptoCookie` + `crypto_key`), httpOnly.
- `productId` валидируется (положительное целое) и проверяется на существование в каталоге.
- CSRF-фильтр для POST-методов, `HttpMethod`-фильтры для всех действий.
- Прямых SQL-запросов нет — только ORM; пользовательский ввод не попадает в `filter` без приведения типов.

## Удаление

Деинсталляция модуля удаляет обработчики событий, компонент, таблицу `vendor_favorites`
и настройки модуля.
