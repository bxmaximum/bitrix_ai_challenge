---
name: bitrix-extensions
description: Covers Bitrix JS/CSS extensions — /local/js/ structure, bundle.config.js, config.php, Extension::load, @bitrix/cli build, CoreJS imports. Applied when adding frontend code to modules, components, or admin pages. Key terms — extension, bundle, Extension::load, bundle.config.js, config.php, CoreJS.
---

# Bitrix JS/CSS Extensions

Extensions organize JavaScript and CSS into bundles loaded by the kernel.

## Location

- System: `/bitrix/js/<module>/<extension>/`
- User: `/local/js/<module>/<extension>/`

`/local/` takes precedence over `/bitrix/`.

## Structure

```
/local/js/vendor.module/myextension/
├── src/              # ES6 source files
├── dist/             # Built bundles (ES5)
├── bundle.config.js  # Build configuration
├── config.php        # Extension manifest
├── lang/             # Optional localization
└── test/             # Optional tests
```

Required: `src`, `dist`, `bundle.config.js`, `config.php`.

Scaffold with `@bitrix/cli`: `bitrix create` (in extension directory).

## bundle.config.js

```javascript
module.exports = {
    input: './src/app.js',
    output: './dist/app.bundle.js',
    namespace: 'BX.Vendor.Module.MyExtension',
    treeshake: true,
    adjustConfigPhp: true,
};
```

Import CSS in the entry file: `import './style.css';`

Import CoreJS extensions:

```javascript
import { Loader } from 'main.loader';
import 'main.date';
```

## config.php

```php
<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) { die(); }

return [
    'js' => './dist/app.bundle.js',
    'css' => './dist/app.bundle.css',
    'rel' => ['main.core', 'ui.buttons'],
    'skip_core' => false,
];
```

## Loading

PHP:

```php
\Bitrix\Main\UI\Extension::load('vendor.module.myextension');
```

JavaScript:

```javascript
BX.Runtime.loadExtension('vendor.module.myextension').then(() => {
    // BX.Vendor.Module.MyExtension is available
});
```

In component templates — load before inline scripts that use the extension.

## Build

From extension directory (with `@bitrix/cli` installed):

```bash
npx bitrix build
```

`dist/` bundles are committed and deployed. Source lives in `src/`.

## Controller Integration

```php
return $this->renderExtension('vendor.module.myextension', ['items' => $items]);
```

## Checklist

- [ ] Extension ID follows `module.extension` naming.
- [ ] `config.php` lists all dependencies in `rel`.
- [ ] Bundles rebuilt after `src/` changes.
- [ ] No direct `<script src="/local/js/...">` — use `Extension::load`.
- [ ] Localization in `lang/` with `BX.message` for JS strings.
