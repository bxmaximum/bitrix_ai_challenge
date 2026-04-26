---
name: bitrix-localization
description: Покрывает локализацию Bitrix — Bitrix\Main\Localization\Loc, языковые файлы lang/<код>/, loadMessages, плейсхолдеры в getMessage, Context::getCulture() и форматы культур, JS-локализация через BX.message и $Bitrix.Loc, translate:index для индексации фраз. Применяется при добавлении и переводе фраз, работе с несколькими языками сайта, JS-переводах компонентов и шаблонов. Ключевые термины — Loc, getMessage, lang file, Culture, BX.message, loadMessages, i18n.
---

# Локализация

## Языковой файл

- Кодировка **UTF-8 без BOM**.
- Имя файла перевода **совпадает** с именем PHP-файла, рядом с которым лежит.
- Папка: `.../lang/<lang>/<...>` зеркалит структуру основного кода.

```
/local/modules/vendor.module/lib/Application/Service/PostService.php
/local/modules/vendor.module/lib/Application/Service/lang/ru/PostService.php
/local/modules/vendor.module/lib/Application/Service/lang/en/PostService.php
```

Содержимое:

```php
<?php
$MESS['VENDOR_MODULE_POST_PUBLISHED'] = 'Пост #NAME# опубликован';
$MESS['VENDOR_MODULE_POST_EMPTY_TITLE'] = 'Не заполнено название поста';
```

Правила префиксов: `<VENDOR>_<MODULE>_<CONTEXT>_<CODE>` — короткий уникальный ключ. Без префикса легко получить конфликт с другими модулями.

## `Loc::getMessage` и `Loc::loadMessages`

```php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__); // зная, где мы — ядро найдёт файл перевода

echo Loc::getMessage('VENDOR_MODULE_POST_PUBLISHED', ['#NAME#' => $post->getTitle()]);
echo Loc::getMessage('VENDOR_MODULE_POST_PUBLISHED', ['#NAME#' => 'x'], 'en');
```

- Сигнатура: `Loc::getMessage(string $code, ?array $replace = null, ?string $language = null)`.
- Подстановки — по шаблонам `#PLACEHOLDER#` (историческая конвенция). Ключи в `$replace` — с решётками.
- `$language` — ID языка (`ru`, `en`). Если не передать — текущий язык сайта.

### Когда нужно явно подключать

Для компонентов, шаблонов компонентов, шаблонов сайта, admin-файлов Bitrix сам подключит соседние `lang/<lang>/<тот_же_файл>.php`. Вручную вызывай `Loc::loadMessages(__FILE__)` если:

- Файл лежит вне стандартной структуры (например, `/local/php_interface/`).
- У тебя собственный загрузчик/класс — каждый файл должен **сам** подключать свои переводы, иначе сломается отложенная загрузка.

### Произвольный файл

```php
Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/custom.php');
```

### Язык по умолчанию модуля

```php
$lang = Loc::getDefaultLang(LANGUAGE_ID); // fallback из настроек языка: 'ru' → 'ru', 'ua' → 'ru'
```

Используй при формировании имён языковых пакетов, если язык проекта шире, чем поддерживаемые в модуле (`ua`, `kz` → «опустить» до `ru`).

## Отложенная загрузка и `BX_MESS_LOG`

Ядро подгружает языковой файл **только при первом** `getMessage(...)` из него — соответствие «PHP-файл ↔ языковой файл» должно быть строгим. Если вызов `getMessage('FOO_BAR')` идёт из одного файла, а фраза определена в соседнем, ядро начнёт сканировать все подряд файлы — это тормозит.

Диагностика: включи в `/local/php_interface/init.php`:

```php
define('BX_MESS_LOG', $_SERVER['DOCUMENT_ROOT'] . '/var/log/bitrix/mess.log');
```

В лог попадут записи формата:

```
[ru]SOME_MESSAGE: not found for /path/to/file.php
CTranslateUtils::CopyMessage('DEMO_CODE', '/path/a.php', '/path/b.php');
```

Как исправлять:

- **Скопировать** фразу в языковой файл того модуля/кода, откуда идёт `getMessage`.
- **Переименовать** код на уникальный, если пересекается с ядром.
- **Перенести код** в правильный файл, если физически оказался не там.

Не копируй автоматически — можно задвоить, изучи причину.

## Региональные настройки (`Culture`)

Форматы даты/времени/имени берутся из `Bitrix\Main\Context\Culture`:

```php
$culture = \Bitrix\Main\Context::getCurrent()->getCulture();
$culture->getDateTimeFormat();   // 'DD.MM.YYYY HH:MI:SS'
$culture->getDateFormat();       // 'DD.MM.YYYY'
$culture->getShortTimeFormat();  // 'HH:MI'
$culture->getNameFormat();       // '#LAST_NAME# #NAME# #SECOND_NAME#'
$culture->getNumberDecimals();
$culture->getNumberDecSeparator();
$culture->getNumberThousandsSeparator();
```

Форматирование:

```php
use Bitrix\Main\Type\DateTime;

$date = new DateTime();
echo $date->format($culture->getDateTimeFormat());

// Через классические хелперы:
echo \FormatDate($culture->getDateFormat(), $date->getTimestamp());
echo \CurrencyFormat(1234.5, 'RUB');
```

Настройка языков: *Настройки → Настройки продукта → Языковые параметры* (формат даты/времени/имени задаётся для языка сайта). Если компонент настроен на собственный формат — он выиграет.

## Установка фраз в JavaScript

PHP-код публикует фразы в `BX.message(...)`:

```php
\Bitrix\Main\Page\Asset::getInstance()->addString(
    '<script>' . \Bitrix\Main\Web\Json::encode([
        'VENDOR_POST_SAVE'   => Loc::getMessage('VENDOR_POST_SAVE'),
        'VENDOR_POST_CANCEL' => Loc::getMessage('VENDOR_POST_CANCEL'),
    ]) . '</script>'
);
```

Или — более идиоматично — через JS-расширение (extension) в `config.php`:

```php
return [
    'js'       => 'script.js',
    'css'      => 'style.css',
    'rel'      => ['main.core'],
    'lang_additional' => [
        'VENDOR_POST_SAVE', 'VENDOR_POST_CANCEL',
    ],
];
```

```js
BX.message('VENDOR_POST_SAVE');

BX.message({ VENDOR_POST_DYNAMIC: 'Загружено асинхронно' });

const welcome = BX.message('WELCOME_TEXT').replace('#NAME#', userName);
```

## BitrixVue 3

```js
// template
<button>{{ $Bitrix.Loc.getMessage('UI_BUTTON_SAVE') }}</button>

// с заменой + реактивность
{{ $Bitrix.Loc.getMessage('DEMO_COUNTER', { '#COUNTER#': this.counter }) }}

// программно
this.$Bitrix.Loc.setMessage({ DEMO_COUNTER: 'Счётчик: #COUNTER#' });

// оптимизация для тяжёлых шаблонов
import { BitrixVue } from 'ui.vue3';

computed: {
    localize() { return BitrixVue.getFilteredPhrases('MYCOMP_'); }
}
```

## Команда `translate:index`

```bash
php bitrix/bitrix.php translate:index
```

Индексирует языковые файлы, чтобы работала страница *Настройки → Локализация → Просмотр файлов* (экспорт/импорт CSV, сборка пакетов). Запускай после массового добавления новых фраз в модуль.

## Мультиязычные модули

- Храни фразы `lang/ru/`, `lang/en/`, `lang/de/` — в корне PHP-файла, который их использует.
- В `install/index.php` модуля подключай переводы: `Loc::loadMessages(__FILE__)`.
- `Loc::getDefaultLang(LANGUAGE_ID)` используй, чтобы «упасть» на базовый язык модуля (`ru`), если нет `kz`/`ua`.
- Публикуй названия языков в системе через форму *Языки интерфейса* — её нельзя задать из `.settings.php`.

## Антипаттерны

- Хардкод русских строк в сервисе/контроллере. Тексты ошибок `Error` — через `Loc::getMessage`.
- `getMessage('CODE')` в одном файле, когда фраза определена в соседнем → сканирование всех файлов, замедление.
- Копирование фраз между модулями по `BX_MESS_LOG` без анализа — рискуешь задвоить и запутать.
- Вывод дат через `date('d.m.Y')` вместо `Culture::getDateFormat()` — ломает мультиязычные проекты.
- Смешение UTF-8 и CP1251 в `lang/` — Bitrix «пере-конвертирует», и ты получаешь кракозябры.
- JS-фразы, прописываемые строкой из PHP без `htmlspecialcharsbx` для заголовков, которые идут в атрибуты.

## Чек-лист

- [ ] Для каждого PHP-файла с `Loc::getMessage` есть соседний `lang/<lang>/<file>.php`.
- [ ] `Loc::loadMessages(__FILE__)` стоит в начале файла, если он вне стандартной структуры компонентов/модулей.
- [ ] Коды фраз начинаются с уникального префикса `VENDOR_MODULE_`.
- [ ] Замены в переводах используют формат `#PLACEHOLDER#`.
- [ ] На dev-окружении включена `BX_MESS_LOG`; проблемные фразы перенесены/переименованы.
- [ ] Форматы даты/чисел/имени в UI идут через `Context::getCulture()`, а не захардкожены.
- [ ] JS-фразы публикуются через `lang_additional` extension'а или ручной `BX.message({...})`.
- [ ] После массового добавления переводов запускается `php bitrix/bitrix.php translate:index`.
