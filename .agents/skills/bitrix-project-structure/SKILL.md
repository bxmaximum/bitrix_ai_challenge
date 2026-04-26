---
name: bitrix-project-structure
description: Покрывает структуру Bitrix-проекта — /local vs /bitrix, PSR-4 автозагрузка, .settings.php и .settings_extra.php, Loader::includeModule, расположение компонентов, шаблонов, модулей, маршрутов и php_interface, неймспейсы вида Vendor\Module. Применяется при вопросах «куда класть код», первичной настройке нового модуля или компонента, переезде кода из /bitrix в /local и настройке автозагрузки. Ключевые термины — /local, /bitrix, PSR-4, .settings.php, Loader, includeModule, autoload, vendor.module.
---

# Структура проекта и автозагрузка в Bitrix

## Три корневых раздела

- `/bitrix/` — **системные файлы**. Никогда не правь их напрямую: любой хотфикс будет потерян при обновлении.
- `/local/` — **весь пользовательский код**. Если файла нет — создай его вручную. При одинаковом пути файл из `/local/` имеет приоритет над `/bitrix/`.
- `/upload/` — загруженные пользователями и модулями файлы.

## Что класть в `/local/`

```
/local/
├── modules/<vendor>.<module>/   # Пользовательские модули (PSR-4 автозагрузка)
├── components/<vendor>/<name>/  # Компоненты (class.php, templates/.default/)
├── templates/<id>/              # Шаблоны сайтов + /components/, /page_templates/
├── routes/web.php               # Маршруты роутинга
├── activities/                  # Действия бизнес-процессов
├── gadgets/                     # Гаджеты рабочего стола
├── blocks/                      # Блоки Сайтов24
├── js/                          # Кастомные JS
├── php_interface/
│   ├── init.php                 # Загружается на каждом хите
│   ├── dbconn.php               # С main 24.100 — можно держать здесь
│   └── user_lang/               # Переводы пользовательского интерфейса
├── .settings.php                # Конфигурация ядра (с main 24.100)
└── .settings_extra.php          # Оверрайды (с main 24.100)
```

Папке `/local/php_interface/` выставь те же права, что и `/bitrix/php_interface/` — там могут быть чувствительные файлы.

## Подключение модуля

Перед обращением к классам любого модуля:

```php
if (!\Bitrix\Main\Loader::includeModule('vendor.module'))
{
    throw new \Bitrix\Main\SystemException('Module vendor.module is not installed');
}
```

Метод:

- Подключает `include.php` и `/lib/autoload.php` модуля.
- Регистрирует неймспейс модуля для PSR-4-автозагрузки.
- Возвращает `false`, если модуль не установлен или отсутствует — всегда проверяй результат.

## PSR-4 автозагрузка классов в `/lib/`

Правило простое: **имя папки = часть неймспейса, имя файла = имя класса** (оба в PascalCase).

```
/local/modules/vendor.module/lib/
├── Application/Service/PostService.php        # \Vendor\Module\Application\Service\PostService
├── Infrastructure/Controller/Post.php         # \Vendor\Module\Infrastructure\Controller\Post
├── Model/PostTable.php                         # \Vendor\Module\Model\PostTable
└── Cli/Command/Feature/RebuildCommand.php      # \Vendor\Module\Cli\Command\Feature\RebuildCommand
```

Неймспейс модуля формируется из идентификатора: `vendor.module` → `\Vendor\Module`. Если идентификатор состоит из одного слова (`mymodule`), то и неймспейс — `\Mymodule`, но такие модули считаются «собственными» (не партнёрскими).

Если структура PSR-4 соблюдена — **ничего регистрировать вручную не нужно**.

## Ручная регистрация (когда нужна)

В редких случаях (смешанные папки, не-PSR-4 наследие) можно прописать в `/local/modules/vendor.module/include.php`:

```php
\Bitrix\Main\Loader::registerNamespace(
    'Vendor\\Module\\Legacy',
    $_SERVER['DOCUMENT_ROOT'] . '/local/modules/vendor.module/legacy',
);

\Bitrix\Main\Loader::registerAutoLoadClasses('vendor.module', [
    'Vendor\\Module\\OldClass' => 'classes/old_class.php',
]);
```

Предпочитай `registerNamespace` для папки с PSR-4 структурой. `registerAutoLoadClasses` — крайний случай.

## Composer

Composer-зависимости кладутся в `/local/vendor/` (`/local/composer.json`). Это нужно и для работы `bitrix/bitrix.php` (команд `make:*`). Не ставь пакеты в `/bitrix/vendor/` — они исчезнут при обновлении ядра.

## Файлы конфигурации

- `/bitrix/.settings.php` или `/local/.settings.php` — основной конфиг ядра D7.
- `/bitrix/.settings_extra.php` или `/local/.settings_extra.php` — оверрайды без API.
- `/bitrix/php_interface/dbconn.php` или `/local/php_interface/dbconn.php` — константы для старого ядра и совместимости.

В `.settings.php` модуля (`/local/modules/vendor.module/.settings.php`) прописываются секции `services`, `controllers`, `routing`, `console`. Его содержимое автоматически подмешивается в глобальный контейнер после `includeModule`.

## Приоритет файлов

- Компоненты: `/local/components/<vendor>/<name>/` перекрывают `/bitrix/components/<vendor>/<name>/`.
- Шаблоны компонентов в шаблоне сайта: `/local/templates/<id>/components/...` перекрывают всё остальное.
- Системные файлы (например, `header.php`) ищутся сначала в `/local/`, затем в `/bitrix/`.

## Когда нужно `php_interface/init.php`

Только для:

- Регистрации **динамических** обработчиков событий (`registerEventHandler`), которые нельзя привязать к установке конкретного модуля.
- Констант проекта, которые должны быть доступны до подключения модулей.
- Совместимостных хуков.

Для всего остального — создавай модуль и используй его `install/index.php`, `include.php`, `.settings.php`.
