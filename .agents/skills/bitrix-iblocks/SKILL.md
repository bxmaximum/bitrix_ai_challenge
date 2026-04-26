---
name: bitrix-iblocks
description: Покрывает модуль iblock — типы инфоблоков, инфоблоки, разделы, элементы, пользовательские свойства (включая файловые и Highload), ORM через IblockTable::compileEntity и ElementTable/SectionTable, классические API CIBlockElement/CIBlockSection/CIBlockProperty, SEO-шаблоны (IPROPERTY_TEMPLATES), права доступа и группы свойств. Применяется при любых задачах по каталогу, новостям, контент-инфоблокам, импорту/экспорту элементов и построении выборок по значениям свойств. Ключевые термины — iblock, IblockTable, CIBlockElement, property, section, SEO template, compileEntity, HL-блок.
---

# Инфоблоки (`iblock`)

Модуль `iblock` — основной инструмент Bitrix для структурированного динамического контента: каталоги, новости, справочники. В новых проектах работаем через **ORM** (типизировано, автодополнение, меньше багов). **Классическое API** нужно там, где ORM не покрывает весь граф таблиц.

```php
\Bitrix\Main\Loader::includeModule('iblock');
```

## Иерархия

- **Тип инфоблоков** (`b_iblock_type`) — семейство инфоблоков с общей структурой: «новости», «каталог».
- **Инфоблок** (`b_iblock`) — таблица элементов определённого типа, привязана к сайтам.
- **Раздел** (`b_iblock_section`) — группа элементов, образует дерево.
- **Элемент** (`b_iblock_element`) — единица контента (новость, товар).
- **Свойство** (`b_iblock_property`) — дополнительная характеристика элемента.

## Ключевые идентификаторы

- `CODE` — символьный код (latin+digits+`-`), используется в URL и в коде.
- `API_CODE` — 1–50 символов, начинается с буквы, **CamelCase рекомендуется**. Только при его наличии работает объектная ORM. Задаётся в настройках инфоблока.
- `XML_ID` — внешний идентификатор (для обменов, Highload-справочников).

## Где проходит граница ORM / классическое API

| Задача | API |
| --- | --- |
| Создать тип инфоблока | `CIBlockType::Add` (ORM не добавит переводы названий) |
| Создать инфоблок | `CIBlock::Add` (ORM не привязывает к сайту, правам, SEO) |
| Добавить/изменить свойство | `CIBlockProperty::Add/Update/Delete` |
| Простые права на инфоблок | `CIBlock::SetPermission` / поле `GROUP_ID` в `Add` |
| Расширенные права | `CIBlockRights` / `CIBlockSectionRights` / `CIBlockElementRights` |
| Ресайз картинок | `CFile::ResizeImage` |
| Полнотекстовый поиск | `CIBlockElement::UpdateSearch($id)` |
| Ежедневное чтение/запись элементов и разделов | ORM |

## Компиляция классов ORM

ORM генерирует классы «на лету» по `API_CODE` инфоблока (`News` в примерах ниже):

```php
\Bitrix\Iblock\IblockTable::compileEntity('News');
// Класс элементов: \Bitrix\Iblock\Elements\ElementNewsTable
// Класс разделов: \Bitrix\Iblock\Model\Section::compileEntityByIblock('News')

$elementClass = \Bitrix\Iblock\Elements\ElementNewsTable::class;
$sectionClass = \Bitrix\Iblock\Model\Section::compileEntityByIblock('News');
```

Не используй `\Bitrix\Iblock\ElementTable` и `\Bitrix\Iblock\SectionTable` — они работают **только** с базовыми полями и **не знают о свойствах/UF**.

IDE-аннотации: `php bitrix/bitrix.php orm:annotate`.

## Создание инфоблока программно

```php
$iblock = new \CIBlock();
$iblockId = $iblock->Add([
    'IBLOCK_TYPE_ID' => 'mynews',
    'NAME'           => 'Новости',
    'CODE'           => 'mycompany_news',
    'API_CODE'       => 'News',           // обязательно для ORM
    'ACTIVE'         => 'Y',
    'LID'            => ['s1'],           // привязка к сайту
    'GROUP_ID'       => [
        2 => \CIBlockRights::PUBLIC_READ,
        8 => \CIBlockRights::EDIT_ACCESS,
    ],
    'VERSION'        => 2,                // версия хранения свойств (обычно 2)
]);
if (!$iblockId) { throw new \RuntimeException($iblock->getLastError()->getMessage()); }
```

### Версии хранения свойств

- **Версия 1** — отдельная строка в общей `b_iblock_element_property`. Медленная выборка, выигрывает при сотнях свойств.
- **Версия 2** (по умолчанию для новых) — значения элемента в одной строке таблицы `b_iblock_element_prop_s{IBLOCK_ID}`. Быстрая выборка, при ≤50 свойств. Для v2 `PropertyTable::add/update/delete` **не работает** — только классическое API.

## Свойства

### Базовые типы

```php
(new \CIBlockProperty)->Add([
    'IBLOCK_ID'     => $iblockId,
    'NAME'          => 'Автор',
    'CODE'          => 'AUTHOR',      // обязательно! без CODE ORM не увидит
    'PROPERTY_TYPE' => 'S',            // S-строка, N-число, L-список, F-файл, E-элемент, G-раздел
    'MULTIPLE'      => 'N',
]);
```

### Список (`L`)

```php
$propId = (new \CIBlockProperty)->Add([
    'IBLOCK_ID' => $iblockId, 'NAME' => 'Источник', 'CODE' => 'SOURCE',
    'PROPERTY_TYPE' => 'L', 'MULTIPLE' => 'N',
]);

$enum = new \CIBlockPropertyEnum();
$enum->Add(['PROPERTY_ID' => $propId, 'VALUE' => 'ТАСС', 'XML_ID' => 'tass', 'SORT' => 10]);
```

### Пользовательские типы (`USER_TYPE`)

- `USER_TYPE = 'HTML'`, `PROPERTY_TYPE = 'S'` — редактор HTML.
- `USER_TYPE = 'directory'`, `PROPERTY_TYPE = 'S'` + `USER_TYPE_SETTINGS = ['TABLE_NAME' => 'b_<hl_table>']` — значение из Highload-блока, хранится `UF_XML_ID`.
- `USER_TYPE = 'DateTime'`, `PROPERTY_TYPE = 'S'` — дата-время.

## Разделы через ORM

```php
$sectionClass = \Bitrix\Iblock\Model\Section::compileEntityByIblock('News');

$parent = $sectionClass::createObject()
    ->setIblockId($iblockId)
    ->setName('Мероприятия')
    ->setCode('events')
    ->set('UF_MANAGER', 'Иван Иванов') // UF-поля — через set('UF_*', ...)
    ->setActive(true)
    ->save();

$child = $sectionClass::createObject()
    ->setIblockId($iblockId)
    ->setName('Выставки')
    ->setCode('exhibitions')
    ->setIblockSectionId($parent->getObject()->getId())
    ->save();
```

Чтение с родителем:

```php
$section = $sectionClass::query()
    ->setSelect(['*', 'PARENT_SECTION', 'UF_*'])
    ->where('CODE', 'exhibitions')
    ->fetchObject();

$section->getParentSection()?->getName();
```

Удаление:

- **`CIBlockSection::Delete($id)`** — рекурсивно удаляет подразделы и элементы, чистит кеш и поиск.
- `$section->delete()` — удаляет только сам раздел (дети осиротеют). Используй с пониманием.

## Элементы через ORM

### Создание

```php
$elementClass = \Bitrix\Iblock\Elements\ElementNewsTable::class;

$element = $elementClass::createObject()
    ->setName('Обновление безопасности')
    ->setCode('security-update')
    ->setActive(true)
    ->setIblockSectionId($parentSectionId)
    ->set('AUTHOR', 'Ирина Петрова')  // строка
    ->set('SOURCE', $enumId);          // список — ID значения из CIBlockPropertyEnum

$result = $element->save();
if (!$result->isSuccess()) { /* errors */ }
```

### Множественные свойства

```php
$element
    ->addTo('TAGS', 'безопасность')
    ->addTo('TAGS', '2026');

$element->removeAll('TAGS');      // очистить всё
$element->removeAllBy('TAGS', 'безопасность');
```

### Файловые свойства

ORM требует `PropertyValue` — в нём `ID` файла + описание.

```php
use Bitrix\Iblock\ORM\PropertyValue;

$fileId = \CFile::SaveFile(
    \CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'] . '/upload/img.png'),
    'iblock',
);

\CFile::ResizeImage($fileId, ['width' => 300, 'height' => 300], BX_RESIZE_IMAGE_PROPORTIONAL, true);

$element
    ->set('PHOTO',   new PropertyValue($fileId, 'Главное фото'))
    ->addTo('GALLERY', new PropertyValue($otherId, 'Второй снимок'));
```

### Справочник (Highload)

```php
$element->set('CATEGORY', 'cybersec'); // UF_XML_ID из Highload-блока, не ID
```

## Чтение элементов

```php
$elements = $elementClass::query()
    ->setSelect([
        'ID', 'NAME', 'DATE_ACTIVE_FROM',
        'AUTHOR',               // строка
        'SOURCE.VALUE',         // значение списка — через .VALUE
        'SOURCE.ITEM.XML_ID',   // XML_ID варианта списка
        'PHOTO.VALUE', 'PHOTO.DESCRIPTION',
        'SECTION_' => 'IBLOCK_SECTION',
    ])
    ->where('ACTIVE', 'Y')
    ->where('SOURCE.VALUE', $enumId)  // фильтр по значению списка — через .VALUE
    ->setOrder(['DATE_ACTIVE_FROM' => 'DESC'])
    ->setLimit(10)
    ->fetchCollection();

foreach ($elements as $e)
{
    $e->getName();
    $e->get('AUTHOR');
    $e->get('SOURCE')?->getItem()?->getValue(); // label варианта
    foreach ($e->get('TAGS')?->getAll() ?? [] as $tag) { $tag->getValue(); }

    $photo = $e->get('PHOTO');
    $photo?->getValue();       // fileId
    $photo?->getDescription(); // описание
}
```

Фильтры по свойствам:

- Строка/число/HTML/справочник → `where('AUTHOR', 'Иван')`.
- Список → `where('SOURCE.VALUE', $id)` или `.ITEM.XML_ID` / `.ITEM.VALUE`.
- Файл → `where('PHOTO.VALUE', $fileId)`.
- Множественные → `where('TAGS.VALUE', 'tag')`.

## Обновление и удаление элемента

```php
$element = $elementClass::query()->where('CODE', 'security-update')->fetchObject();
$element
    ->setName('Критическое обновление')
    ->removeAll('TAGS')
    ->addTo('TAGS', 'критическое')
    ->save();

$element->delete();
\CIBlockElement::UpdateSearch($element->getId()); // обязательно — ORM не трогает поиск
```

Статические `::update($id, [...])`, `::delete($id)` работают **только с базовыми полями** — свойства через объект.

## SEO-шаблоны

Значения `ELEMENT_META_TITLE`, `ELEMENT_META_KEYWORDS`, `ELEMENT_META_DESCRIPTION`, `SECTION_PAGE_TITLE` не хранятся — вычисляются по шаблонам `{=this.NAME} — #SITE_NAME#` с цепочкой наследования «элемент → раздел → инфоблок».

```php
use Bitrix\Iblock\InheritedProperty;

(new InheritedProperty\IblockTemplates($iblockId))->set([
    'ELEMENT_META_TITLE' => '{=this.NAME} — #SITE_NAME#',
]);

(new InheritedProperty\SectionTemplates($iblockId, $sectionId))->set([
    'ELEMENT_META_TITLE' => 'Мероприятие: {=this.NAME}',
]);

$values = new InheritedProperty\ElementValues($iblockId, $elementId);
$seo = $values->getValues();

$values->clearValues();  // после изменений
```

`IPROPERTY_TEMPLATES` поддерживается только в `CIBlockElement::Add/Update`, не в ORM.

## Производительность

- `VERSION = 2` для инфоблоков с ≤50 свойств — даёт один JOIN вместо N.
- Работай через ORM с `setSelect(['*', 'SOURCE.VALUE', 'IBLOCK_SECTION'])`, а не получай всё подряд.
- Кешируй выборки: `['cache' => ['ttl' => 3600]]` или `startResultCache()` в компоненте.
- Тегированный кеш: при изменении элемента/раздела модуль сам публикует теги `iblock_id_{IBLOCK_ID}` и `iblock_id_new` — привязывай к ним HTML-кеши компонентов.
- Не читай свойства «в цикле» для каждой записи: добавь их сразу в `setSelect`.

## Антипаттерны

- Работа через `CIBlockElement::GetList` там, где есть ORM: теряется типизация, сложнее рефакторить.
- Запись свойств без `CODE`: такие свойства невидимы в ORM, нужно задним числом «проставлять CODE всем».
- Сохранение файла в свойство как `set('PHOTO', $fileId)` — нужно `PropertyValue`.
- Удаление раздела через `$section->delete()` вместо `CIBlockSection::Delete` (остаются осиротевшие элементы, старые записи в поиске).
- Забыть `UpdateSearch` после удаления элемента.
- Использование `ElementTable`/`SectionTable` вместо скомпилированных классов.

## Чек-лист

- [ ] У инфоблока задан `API_CODE` в CamelCase.
- [ ] У всех свойств есть `CODE`.
- [ ] Для версии 2 управление свойствами идёт только через `CIBlockProperty`.
- [ ] Запросы используют `setSelect` с нужными свойствами и связями за один запрос.
- [ ] Файлы сохраняются через `CFile::SaveFile` + `PropertyValue`; миниатюры — `CFile::ResizeImage`.
- [ ] `Highload`-справочник → свойство `USER_TYPE = directory`, хранение по `UF_XML_ID`.
- [ ] `orm:annotate` выполнен после изменения состава инфоблоков — IDE видит `Elements\Element<ApiCode>Table`.
- [ ] Удаление раздела выполняется `CIBlockSection::Delete`; после удаления элемента — `UpdateSearch`.
