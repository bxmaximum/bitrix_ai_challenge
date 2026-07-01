---
name: bitrix-components
description: Covers Bitrix component development — class.php, .parameters.php, .description.php, template.php templates, result_modifier.php, component_epilog.php, caching via startResultCache/endResultCache, complex components with SEF routes, Controllerable and AJAX via runComponentAction. Applied when creating and editing components and their templates, adding AJAX actions, configuring component cache and SEF routes. Key terms — component, template, arParams, arResult, SEF, Controllerable, runComponentAction, CBitrixComponent.
---

# Bitrix Components

Component = a widget that fetches data via module API and transforms it into HTML. The primary display unit in the CMS part of Bitrix. For entire sections (catalog, personal area), it's better to use a controller + routes; complex components with SEF are used when integration with the tree-like visual editor is required.

## Where to Place

- System: `/bitrix/components/bitrix/` — **do not touch**.
- User: `/local/components/<vendor>/<name>/`.
- Component Name: `<vendor>:<name>` (`vendor:catalog.list`). The namespace folder is yours; other vendors' components should not go there.

Quick scaffold:

```bash
php bitrix/bitrix.php make:component Vendor:Catalog.List --local
php bitrix/bitrix.php make:component Vendor:Catalog.List --module=vendor.catalog
```

## Folder Structure

```
/local/components/vendor/catalog.list/
├── class.php              # logic (CBitrixComponent)
├── .description.php       # name/icon/place in visual editor tree
├── .parameters.php        # parameters description for admin panel
├── ajax.php               # optional: lightweight AJAX controller
├── lang/en/
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
    │   └── lang/en/template.php
    └── <other_template>/
```

## `class.php` — Minimum

```php
<?php declare(strict_types=1);

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
        // data reading
        return [];
    }
}
```

## Usage

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

In complex components **always** pass `$component` as the fourth parameter — this allows nested components to find templates in the parent's folder and cache epilogs.

## `$arParams` and `$arResult`

- `$arParams` — input parameters. Values automatically go through `htmlspecialcharsEx`; raw source is available with `~` prefix: `$arParams['~NAME']`.
- `$arResult` — template data. Initialized as `[]`.
- Both are references to component fields. Do not reassign via `$arParams = &$other` and do not `unset($arParams)` — the link to the template will break.

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
        'CHILD' => ['ID' => 'catalog', 'NAME' => 'Catalog'],
    ],
    'CACHE_PATH' => 'Y',
    'COMPLEX' => 'N',
];
```

Without `PATH`, the component won't appear in the visual editor. Tree roots are reserved: `content`, `service`, `communication`, `e-store`, `utility`.

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
        'SET_TITLE'  => [],  // special — enables title
        'CACHE_TIME' => [],  // special — enables caching block
    ],
];
```

`TYPE` types: `LIST`, `STRING`, `CHECKBOX`, `FILE`, `COLORPICKER`, `CUSTOM` (for custom JS widgets). Hints are `<PARAM>_TIP` constants in `lang/en/.parameters.php`.

## Template

### Template Search

The kernel looks for a template in this order:

1. `/local/templates/<current_template>/components/<ns>/<name>/<tpl>/`
2. `/local/templates/.default/components/...`
3. `/bitrix/templates/<current_template>/components/...`
4. `/bitrix/templates/.default/components/...`
5. System template inside the component itself.

If you want a custom one — copy the whole thing to `/local/templates/<site>/components/...` and edit it there. Kernel updates won't affect it.

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

### Available Variables

`$arResult`, `$arParams`, `$templateName`, `$templateFolder`, `$templateFile`, `$componentPath`, `$component`, `$this`, `$templateData`, `$APPLICATION`, `$USER`.

## `result_modifier.php`

Runs **before** the template. Use to enrich `$arResult` without modifying the component class.

- When caching is **enabled**, the template (and modifier) are skipped on cache hit — modifier does not run.
- Cannot set dynamic page properties (`title`, `keywords`, `description`) when cache is on.
- `$arParams` changes affect the template but not the component member.
- `$arResult` changes affect the component member.

## `component_epilog.php`

Runs **after** the template on **every hit**, even with cache enabled. Use for dynamic page properties, counters, or logic that must execute per request.

Limit cached `$arResult` keys via `setResultCacheKeys()` in `class.php`:

```php
$this->setResultCacheKeys(['ITEMS', 'SECTION_NAME', 'NAV_CACHED_DATA']);
```

Pass data from template to epilog via `$templateData` (cached):

```php
// template.php
$templateData = ['ITEM_COUNT' => count($arResult['ITEMS'])];
```

Epilog lang phrases: create `/lang/en/component_epilog.php` and `Loc::loadLanguageFile(__FILE__)`.

> Code in `class.php` after `includeComponentTemplate()` runs **after** epilog and overrides epilog changes (e.g. `SetTitle`).

## Caching Details

Cache ID is built from: site ID, component name, template name, parameters, external conditions (e.g. user groups).

- Pass user groups as cache key when content differs by group: `$this->startResultCache(false, [$GLOBALS['USER']->GetUserGroupArray()])`.
- Avoid deferred functions in templates when caching is on.
- Autocache can be disabled globally in Admin → Autocache settings.

## SEF (Search-Friendly URLs)

For complex components, define in `.parameters.php`:

```php
'SEF_MODE' => 'Y',
'SEF_FOLDER' => '/catalog/',
'SEF_URL_TEMPLATES' => [
    'sections' => '',
    'section'  => '#SECTION_ID#/',
    'element'  => '#SECTION_ID#/#ELEMENT_ID#/',
],
'VARIABLE_ALIASES' => [
    'SECTION_ID' => ['NAME' => 'Section ID'],
    'ELEMENT_ID' => ['NAME' => 'Element ID'],
],
```

In `class.php`, parse SEF variables and build URLs. Prefer controllers + routes for new full sections; use complex SEF components only when visual editor integration is required.

## Controllerable and AJAX

Implement `\Bitrix\Main\Engine\Contract\Controllerable` (+ `\Bitrix\Main\Errorable` for errors):

```php
final class VendorCatalogListComponent extends \CBitrixComponent
    implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
    protected \Bitrix\Main\ErrorCollection $errorCollection;

    public function configureActions(): array
    {
        return [
            'addToCart' => [
                '+prefilters' => [new \Bitrix\Main\Engine\ActionFilter\Authentication()],
            ],
        ];
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->errorCollection = new \Bitrix\Main\ErrorCollection();
        return parent::onPrepareComponentParams($arParams);
    }

    public function addToCartAction(int $productId): array
    {
        // executeComponent() is NOT called during AJAX
        return ['success' => true];
    }

    public function getErrors(): array { return $this->errorCollection->toArray(); }
    public function getErrorByCode($code) { return $this->errorCollection->getErrorByCode($code); }

    protected function listKeysSignedParameters(): array
    {
        return ['IBLOCK_ID', 'STORAGE_ID'];
    }
}
```

Alternative: lightweight `ajax.php` with a class extending `\Bitrix\Main\Engine\Controller`.

### JavaScript

```javascript
BX.ajax.runComponentAction('vendor:catalog.list', 'addToCart', {
    mode: 'class',
    signedParameters: '<?= $this->getComponent()->getSignedParameters() ?>',
    data: { productId: 42 },
});
```

For AJAX page updates, include `id="pagetitle"` and `id="navigation"` in the template.

## Checklist

- [ ] Logic in `class.php`; display in template; heavy work in services.
- [ ] `onPrepareComponentParams` normalizes and casts all `$arParams`.
- [ ] `setResultCacheKeys` limits epilog cache size.
- [ ] Nested components pass `$component` as 4th argument to `IncludeComponent`.
- [ ] `Controllerable` actions have proper filters; signed parameters listed in `listKeysSignedParameters`.
- [ ] Templates in `/local/templates/<site>/components/` for site-specific overrides.
