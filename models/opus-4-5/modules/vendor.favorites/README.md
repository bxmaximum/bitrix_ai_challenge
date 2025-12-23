# Модуль «Избранное» для каталога товаров

Модуль для 1С-Битрикс, реализующий функционал добавления товаров в избранное (Wishlist).

## Возможности

- ✅ Добавление/удаление товаров в избранное
- ✅ Поддержка авторизованных пользователей (хранение в БД)
- ✅ Поддержка гостей (хранение в зашифрованных Cookie)
- ✅ Автоматическая миграция избранного при авторизации
- ✅ REST API через контроллеры D7
- ✅ Публичный компонент с современным дизайном
- ✅ Тегированное кэширование
- ✅ CSRF-защита
- ✅ Административный интерфейс настроек

## Требования

- Bitrix 20.x+
- PHP 8.1+
- Модуль `iblock`

## Установка

### Автоматическая установка

1. Скопируйте папку модуля в `/local/modules/vendor.favorites/`
2. Перейдите в административную панель: **Настройки → Модули**
3. Найдите модуль «Избранное для каталога» и нажмите «Установить»

### Ручная установка

```bash
# Копирование модуля
cp -r vendor.favorites /path/to/site/local/modules/

# Установка через CLI (если доступен)
php bitrix/bitrix.php module:install vendor.favorites
```

## Настройка

После установки перейдите в **Настройки → Настройки модулей → Избранное для каталога**:

| Параметр | Описание |
|----------|----------|
| Включить модуль | Активация/деактивация функционала |
| ID инфоблока каталога | Инфоблок, из которого можно добавлять товары |
| Время жизни cookie | Срок хранения избранного для гостей (дни) |

## Использование

### Компонент кнопки

Разместите компонент на странице товара:

```php
<?php
$APPLICATION->IncludeComponent(
    'vendor:favorites.button',
    '.default',
    [
        'PRODUCT_ID' => $arResult['ID'],      // ID товара (обязательно)
        'SHOW_COUNTER' => 'N',                 // Показывать счётчик (Y/N)
        'BUTTON_SIZE' => 'medium',             // Размер: small, medium, large
    ]
);
?>
```

### REST API

Все методы защищены CSRF-токеном.

#### Добавить в избранное

```javascript
BX.ajax.runAction('vendor:favorites.Favorites.add', {
    data: { productId: 123 }
}).then(response => {
    console.log('Добавлено:', response.data);
});
```

#### Удалить из избранного

```javascript
BX.ajax.runAction('vendor:favorites.Favorites.remove', {
    data: { productId: 123 }
}).then(response => {
    console.log('Удалено:', response.data);
});
```

#### Получить список ID

```javascript
BX.ajax.runAction('vendor:favorites.Favorites.list')
    .then(response => {
        console.log('ID товаров:', response.data.productIds);
        console.log('Количество:', response.data.count);
    });
```

#### Получить товары с данными

```javascript
BX.ajax.runAction('vendor:favorites.Favorites.getProducts')
    .then(response => {
        response.data.products.forEach(product => {
            console.log(product.NAME, product.DETAIL_PAGE_URL);
        });
    });
```

#### Проверить наличие в избранном

```javascript
BX.ajax.runAction('vendor:favorites.Favorites.check', {
    data: { productId: 123 }
}).then(response => {
    console.log('В избранном:', response.data.isInFavorites);
});
```

### JavaScript события

Компонент генерирует события для интеграции:

```javascript
// Изменение избранного
document.addEventListener('vendorFavoritesChange', function(e) {
    console.log('Товар:', e.detail.productId);
    console.log('В избранном:', e.detail.isInFavorites);
    console.log('Всего в избранном:', e.detail.totalCount);
});

// Ошибка
document.addEventListener('vendorFavoritesError', function(e) {
    console.error('Ошибка:', e.detail.message);
});
```

## Архитектура

### Структура модуля

```
vendor.favorites/
├── install/
│   ├── index.php              # Установщик модуля
│   ├── version.php            # Версия
│   └── components/            # Компоненты
│       └── vendor/
│           └── favorites.button/
├── lib/
│   ├── Controller/
│   │   └── Favorites.php      # REST API контроллер
│   ├── Service/
│   │   ├── FavoritesService.php   # Бизнес-логика
│   │   └── CookieService.php      # Работа с Cookie
│   ├── Repository/
│   │   └── FavoritesRepository.php
│   ├── Model/
│   │   └── FavoritesTable.php     # ORM таблица
│   └── EventHandler.php           # Обработчики событий
├── lang/ru/                   # Локализация
├── .settings.php              # Конфигурация контроллеров
├── include.php                # Точка входа
├── options.php                # Страница настроек
└── README.md
```

### Таблица БД

```sql
CREATE TABLE vendor_favorites (
    ID INT(11) NOT NULL AUTO_INCREMENT,
    USER_ID INT(11) NOT NULL,
    PRODUCT_ID INT(11) NOT NULL,
    IBLOCK_ID INT(11) NOT NULL,
    DATE_CREATED DATETIME NOT NULL,
    PRIMARY KEY (ID),
    UNIQUE KEY ux_user_product (USER_ID, PRODUCT_ID),
    KEY ix_user_id (USER_ID),
    KEY ix_product_id (PRODUCT_ID)
);
```

### Обработчики событий

| Событие | Действие |
|---------|----------|
| `OnAfterUserAuthorize` | Миграция избранного из cookie в БД |
| `OnAfterIBlockElementDelete` | Удаление товара из избранного всех пользователей |
| `OnAfterIBlockElementUpdate` | Инвалидация кэша при изменении товара |

## Кэширование

Модуль использует тегированный кэш D7:

- Кэш формируется на уровне пользователя
- Теги: `vendor_favorites_user_{USER_ID}`
- TTL: 3600 секунд (1 час)
- Автоматическая инвалидация при изменении/удалении товаров

## Безопасность

- **CSRF-защита** — все POST-запросы защищены токеном `sessid`
- **Шифрование cookie** — используется `CryptoCookie` для гостей
- **Валидация** — проверка ID товара и существования в инфоблоке
- **Права доступа** — настройки доступны только администраторам

## Кастомизация

### CSS переменные

```css
:root {
    --vendor-favorites-color-inactive: #9ca3af;
    --vendor-favorites-color-active: #ef4444;
    --vendor-favorites-color-hover: #f87171;
    --vendor-favorites-bg: transparent;
    --vendor-favorites-bg-hover: rgba(239, 68, 68, 0.1);
    --vendor-favorites-transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
```

### Создание собственного шаблона

1. Скопируйте шаблон `.default` в `/local/templates/ваш_шаблон/components/vendor/favorites.button/`
2. Измените `template.php`, `style.css`, `script.js` по необходимости

## Лицензия

MIT

## Автор

Vendor Team





