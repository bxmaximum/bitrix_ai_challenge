---
name: bitrix-components
description: Покрывает разработку компонентов Bitrix — class.php, .parameters.php, .description.php, шаблоны template.php, result_modifier.php, component_epilog.php, кеш через startResultCache/endResultCache, комплексные компоненты с ЧПУ и SEF, Controllerable и AJAX через runComponentAction. Применяется при создании и правке компонентов и их шаблонов, добавлении AJAX-экшенов, настройке кеша компонента и SEF-маршрутов. Ключевые термины — component, template, arParams, arResult, SEF, Controllerable, runComponentAction, CBitrixComponent.
---

# Компоненты Bitrix

Компонент = виджет, который берёт данные через API модуля и преобразует их в HTML. Основная единица отображения в CMS-части Bitrix. Для целых разделов (каталог, личный кабинет) лучше контроллер + маршруты; комплексные компоненты с ЧПУ используются, когда нужна интеграция с древовидным визуальным редактором.

## Где размещать

- Системные: `/bitrix/components/bitrix/` — **не трогаем**.
- Пользовательские: `/local/components/<vendor>/<name>/`.
- Имя компонента: `<vendor>:<name>` (`vendor:catalog.list`). Namespace-папка — ваша, в неё не должны попадать чужие компоненты.

Быстрый скаффолд:

```bash
php bitrix/bitrix.php make:component Vendor:Catalog.List --local
php bitrix/bitrix.php make:component Vendor:Catalog.List --module=vendor.catalog
```

## Структура папки

```
/local/components/vendor/catalog.list/
├── class.php              # логика (CBitrixComponent)
├── .description.php       # имя/иконка/место в дереве визуального редактора
├── .parameters.php        # описание параметров для админки
├── ajax.php               # опционально: легковесный контроллер AJAX
├── lang/ru/
│   ├── class.php
│   ├── .description.php
│   ├── .parameters.php
│   └── component_epilog.php
└── templates/
    ├── .default/
    │   ├── template.php
    │   ├── result_modifier.php
    │   ├── component_epilog.php
    │   ├── style.css
    │   ├── script.js
    │   ├── .description.php
    │   ├── .parameters.php
    │   └── lang/ru/template.php
    └── <other_template>/
```

## `class.php` — минимум

```php
<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

final class VendorCatalogListComponent extends \CBitrixComponent
{
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['IBLOCK_ID'] = (int)($arParams['IBLOCK_ID'] ?? 0);
        $arParams['COUNT']     = max(1, (int)($arParams['COUNT'] ?? 20));
        $arParams['CACHE_TIME'] = (int)($arParams['CACHE_TIME'] ?? 3600);

        return $arParams;
    }

    public function executeComponent(): void
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock'))
        {
            ShowError('Module iblock is not installed');
            return;
        }

        if ($this->startResultCache(false, [$GLOBALS['USER']->GetUserGroupArray()]))
        {
            $this->arResult['ITEMS'] = $this->fetchItems();
            $this->setResultCacheKeys(['ITEMS', 'SECTION_NAME']);
            $this->includeComponentTemplate();
        }
    }

    private function fetchItems(): array
    {
        // чтение данных
        return [];
    }
}
```

## Подключение

```php
$APPLICATION->IncludeComponent(
    'vendor:catalog.list',
    '.default',
    [
        'IBLOCK_ID' => 12,
        'COUNT'     => 10,
        'CACHE_TIME' => 3600,
        'CACHE_TYPE' => 'A',
    ],
    /* parent */ $component ?? false,
);
```

В комплексных компонентах **всегда** передавай `$component` четвёртым параметром — это позволяет вложенным компонентам найти шаблоны в папке родителя и кешировать эпилоги.

## `$arParams` и `$arResult`

- `$arParams` — входные параметры. Значения автоматически проходят через `htmlspecialcharsEx`; исходник доступен с префиксом `~`: `$arParams['~NAME']`.
- `$arResult` — данные для шаблона. Инициализируется `[]`.
- Обе — ссылки на поля компонента. Не переназначай через `$arParams = &$other` и не делай `unset($arParams)` — связь с шаблоном порвётся.

## `.description.php`

```php
<?php
use Bitrix\Main\Localization\Loc;

$arComponentDescription = [
    'NAME' => Loc::getMessage('VENDOR_CATALOG_LIST_NAME'),
    'DESCRIPTION' => Loc::getMessage('VENDOR_CATALOG_LIST_DESC'),
    'ICON' => '/images/icon.gif',
    'PATH' => [
        'ID' => 'content',
        'CHILD' => ['ID' => 'catalog', 'NAME' => 'Каталог'],
    ],
    'CACHE_PATH' => 'Y',
    'COMPLEX' => 'N',
];
```

Без `PATH` компонент не появится в визуальном редакторе. Корни дерева зарезервированы: `content`, `service`, `communication`, `e-store`, `utility`.

## `.parameters.php`

```php
<?php
use Bitrix\Main\Localization\Loc;

$arComponentParameters = [
    'GROUPS' => [
        'SETTINGS' => ['NAME' => Loc::getMessage('SETTINGS'), 'SORT' => 100],
    ],
    'PARAMETERS' => [
        'IBLOCK_ID' => [
            'PARENT' => 'SETTINGS',
            'NAME' => Loc::getMessage('IBLOCK_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'COUNT' => [
            'PARENT' => 'SETTINGS',
            'NAME' => Loc::getMessage('COUNT'),
            'TYPE' => 'STRING',
            'DEFAULT' => '20',
        ],
        'SET_TITLE'  => [],  // особый — включает заголовок
        'CACHE_TIME' => [],  // особый — включает блок кеширования
    ],
];
```

Типы `TYPE`: `LIST`, `STRING`, `CHECKBOX`, `FILE`, `COLORPICKER`, `CUSTOM` (для своих JS-виджетов). Подсказки — константы `<PARAM>_TIP` в `lang/ru/.parameters.php`.

## Шаблон

### Поиск шаблона

Ядро ищет шаблон в порядке:

1. `/local/templates/<current_template>/components/<ns>/<name>/<tpl>/`
2. `/local/templates/.default/components/...`
3. `/bitrix/templates/<current_template>/components/...`
4. `/bitrix/templates/.default/components/...`
5. Системный шаблон внутри самого компонента.

Хочешь кастом — копируй целиком в `/local/templates/<site>/components/...` и правь там. Обновления ядра не затронут.

### `template.php`

```php
<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); } ?>

<div class="catalog-list">
    <?php foreach ($arResult['ITEMS'] as $item): ?>
        <a href="<?= htmlspecialcharsbx($item['URL']) ?>">
            <?= htmlspecialcharsbx($item['NAME']) ?>
        </a>
    <?php endforeach; ?>
</div>
```

### Доступные переменные

`$arResult`, `$arParams`, `$templateName`, `$templateFolder`, `$templateFile`, `$componentPath`, `$component`, `$this`, `$templateData`, `$APPLICATION`, `$USER`.

## `result_modifier.php`

Выполняется **только если кеша нет**, перед `template.php`. Идеально для донастройки данных перед выводом.

```php
<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

foreach ($arResult['ITEMS'] as &$item)
{
    $item['URL'] = '/catalog/' . $item['CODE'] . '/';
}
unset($item);
```

Не трогай `SetTitle`, `SetMeta*` — они не сработают при попадании в кеш.

## `component_epilog.php`

Выполняется **после шаблона на каждом хите** (и при попадании в кеш). Там меняем метаданные и счётчики.

```php
<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }
global $APPLICATION;

if (!empty($arResult['META_TITLE']))
{
    $APPLICATION->SetTitle($arResult['META_TITLE']);
}
```

Доступ к `$arResult` — только ключей, перечисленных в `$this->setResultCacheKeys([...])` из `class.php`. Плюс есть `$templateData` для прокидывания данных из `template.php` в эпилог (кешируется).

## Кеш компонента

- Включается через `$this->startResultCache($cacheTime, $additionalKey, $cacheId?, $cachePath?)`.
- `$additionalKey` должен содержать всё, что влияет на результат (группы пользователя, язык, фильтры из запроса).
- Отменить запись кеша можно через `$this->abortResultCache()`.
- Сбросить кеш вручную: `$cache = \Bitrix\Main\Data\Cache::createInstance(); $cache->cleanDir('/cache_path_from_cache');`.
- `CACHE_TYPE` параметра: `A` — автокеш (включается глобально в админке), `Y` — всегда, `N` — выключить.
- `CACHE_GROUPS = 'Y'` — учитывать группы пользователя (важно, иначе разные группы увидят один HTML).

См. скилл `bitrix-caching` для тегов/инвалидации.

## Комплексные компоненты (ЧПУ)

Комплексный компонент (`COMPLEX => 'Y'`) может иметь несколько страниц под своим корневым URL. Шаблоны путей описываются в `.parameters.php`:

```php
'SEF_MODE' => [
    'list'    => ['NAME' => '', 'DEFAULT' => 'index.php',       'VARIABLES' => []],
    'section' => ['NAME' => '', 'DEFAULT' => '#SECTION_CODE#/', 'VARIABLES' => ['SECTION_CODE']],
    'detail'  => ['NAME' => '', 'DEFAULT' => '#SECTION_CODE#/#ELEMENT_CODE#/', 'VARIABLES' => ['SECTION_CODE', 'ELEMENT_CODE']],
],
'VARIABLE_ALIASES' => [
    'SECTION_ID' => ['NAME' => 'ID раздела'],
    'ELEMENT_ID' => ['NAME' => 'ID элемента'],
],
```

В логике:

```php
$engine = new \CComponentEngine($this);
$arVariables = [];
$pageId = $engine->guessComponentPath($arParams['SEF_FOLDER'], $arParams['SEF_URL_TEMPLATES'], $arVariables);
// $pageId = 'list' | 'section' | 'detail'
```

Для новых проектов проще использовать обычный `routing` (см. `bitrix-routing`) и контроллеры — комплексные компоненты оставляй для проектов, где нужен редактор/Маркетплейс.

## Контроллер внутри компонента

Чтобы `BX.ajax.runComponentAction(...)` мог вызывать методы компонента:

```php
final class VendorCatalogListComponent extends \CBitrixComponent
    implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
    private \Bitrix\Main\ErrorCollection $errors;

    public function onPrepareComponentParams($arParams): array
    {
        $this->errors = new \Bitrix\Main\ErrorCollection();
        return $arParams;
    }

    public function configureActions(): array
    {
        return [];
    }

    public function loadMoreAction(int $page = 1): array
    {
        return ['items' => $this->fetchItems($page)];
    }

    public function getErrors(): array { return $this->errors->toArray(); }
    public function getErrorByCode($code): ?\Bitrix\Main\Error { return $this->errors->getErrorByCode($code); }
}
```

Отдай подписанные параметры в JS:

```php
protected function listKeysSignedParameters(): array
{
    return ['IBLOCK_ID', 'COUNT'];
}
```

```php
<script>
new BX.Vendor.CatalogList({
    componentName: '<?= $this->getComponent()->getName() ?>',
    signedParameters: '<?= $this->getComponent()->getSignedParameters() ?>',
});
</script>
```

```js
BX.ajax.runComponentAction(this.componentName, 'loadMore', {
    mode: 'class',
    signedParameters: this.signedParameters,
    data: { page: 2 },
}).then(r => { /* ... */ });
```

Альтернативно — вынеси логику в `ajax.php` как обычный контроллер `extends \Bitrix\Main\Engine\Controller` (см. `bitrix-controllers`).

## Чек-лист

- [ ] Компонент лежит в `/local/components/<vendor>/<name>/`, namespace-папка занята только твоими компонентами.
- [ ] `class.php` начинается с `B_PROLOG_INCLUDED`-заглушки; параметры нормализованы в `onPrepareComponentParams`.
- [ ] Обязательный модуль подключается через `Loader::includeModule`; ошибки выводятся через `ShowError`.
- [ ] `startResultCache(...)` учитывает группы пользователя, язык и все параметры, влияющие на результат.
- [ ] `SetResultCacheKeys` ограничивает, что попадёт в `$arResult` при кешировании.
- [ ] Динамические `SetTitle`/метатеги — в `component_epilog.php`, а не в `template.php`.
- [ ] В шаблоне всё из `$arResult` экранируется через `htmlspecialcharsbx`.
- [ ] Для AJAX — либо `Controllerable` + подписанные параметры, либо отдельный `ajax.php`-контроллер.
- [ ] Пользовательский шаблон лежит в `/local/templates/<site>/components/<ns>/<name>/<tpl>/`, а не правится в `/bitrix/`.
