---
name: bitrix-project-structure
description: Covers Bitrix project structure — /local vs /bitrix, PSR-4 autoloading, .settings.php and .settings_extra.php, Loader::includeModule, placement of components, templates, modules, routes and php_interface, namespaces like Vendor\Module. Applied for "where to put code" questions, initial setup of a new module or component, moving code from /bitrix to /local and configuring autoloading. Key terms — /local, /bitrix, PSR-4, .settings.php, Loader, includeModule, autoload, vendor.module.
---

# Project Structure and Autoloading in Bitrix

## Three Root Sections

- `/bitrix/` — **system files**. Never edit them directly: any hotfix will be lost during an update.
- `/local/` — **all user code**. If a file doesn't exist — create it manually. With the same path, a file in `/local/` takes precedence over `/bitrix/`.
- `/upload/` — files uploaded by users and modules.

## What to Put in `/local/`

```
/local/
├── modules/<vendor>.<module>/   # Custom modules (PSR-4 autoloading)
├── components/<vendor>/<name>/  # Components (class.php, templates/.default/)
├── templates/<id>/              # Site templates + /components/, /page_templates/
├── routes/web.php               # Routing routes
├── activities/                  # Business process actions
├── gadgets/                     # Desktop gadgets
├── blocks/                      # Sites24 blocks
├── js/                          # Custom JS
├── php_interface/
│   ├── init.php                 # Loaded on every hit
│   ├── dbconn.php               # From main 24.100 — can be kept here
│   └── user_lang/               # User interface translations
├── .settings.php                # Kernel configuration (from main 24.100)
└── .settings_extra.php          # Overrides (from main 24.100)
```

Set the same permissions for `/local/php_interface/` as for `/bitrix/php_interface/` — it may contain sensitive files.

## Including a Module

Before accessing classes of any module:

```php
if (!\Bitrix\Main\Loader::includeModule('vendor.module'))
{
    throw new \Bitrix\Main\SystemException('Module vendor.module is not installed');
}
```

The method:

- Includes `include.php` and `/lib/autoload.php` of the module.
- Registers the module namespace for PSR-4 autoloading.
- Returns `false` if the module is not installed or missing — always check the result.

## PSR-4 Autoloading of Classes in `/lib/`

The rule is simple: **folder name = namespace part, file name = class name** (both in PascalCase).

```
/local/modules/vendor.module/lib/
├── Application/Service/PostService.php        # \Vendor\Module\Application\Service\PostService
├── Infrastructure/Controller/Post.php         # \Vendor\Module\Infrastructure\Controller\Post
├── Model/PostTable.php                         # \Vendor\Module\Model\PostTable
└── Cli/Command/Feature/RebuildCommand.php      # \Vendor\Module\Cli\Command\Feature\RebuildCommand
```

The module namespace is formed from the identifier: `vendor.module` → `\Vendor\Module`. If the identifier consists of one word (`mymodule`), then the namespace is `\Mymodule`, but such modules are considered "own" (not partner).

If the PSR-4 structure is followed — **nothing needs to be registered manually**.

## Manual Registration (When Needed)

In rare cases (mixed folders, non-PSR-4 legacy), you can specify in `/local/modules/vendor.module/include.php`:

```php
\Bitrix\Main\Loader::registerNamespace(
    'Vendor\\Module\\Legacy',
    $_SERVER['DOCUMENT_ROOT'] . '/local/modules/vendor.module/legacy',
);

\Bitrix\Main\Loader::registerAutoLoadClasses('vendor.module', [
    'Vendor\\Module\\OldClass' => 'classes/old_class.php',
]);
```

Prefer `registerNamespace` for a folder with PSR-4 structure. `registerAutoLoadClasses` is a last resort.

## Composer

Composer dependencies are placed in `/local/vendor/` (`/local/composer.json`). Keep `composer.json` **outside** `DOCUMENT_ROOT` when possible.

In `.settings.php`:

```php
'composer' => [
    'value' => ['config_path' => '../composer.json'], // path relative to DOCUMENT_ROOT
    'readonly' => true,
],
```

Required for `bitrix/bitrix.php` (`make:*` commands). Do not install packages in `/bitrix/vendor/` — they disappear on kernel update.

## Additional Files

- `/bitrix/routing_index.php` — entry point for new routing (configure web server to forward here).
- `/local/php_interface/after_connect_d7.php` — runs after DB connection (migrations, session tweaks).
- `/local/php_interface/virtual_file_system.php` — virtual filesystem overrides.

## JS Extensions

Custom frontend code lives in `/local/js/<module>/<extension>/`. Load via `Extension::load('module.extension')`. See skill `bitrix-extensions`.

## Configuration Files

- `/bitrix/.settings.php` or `/local/.settings.php` — primary D7 kernel config.
- `/bitrix/.settings_extra.php` or `/local/.settings_extra.php` — overrides without API.
- `/bitrix/php_interface/dbconn.php` or `/local/php_interface/dbconn.php` — constants for old kernel and compatibility.

In a module's `.settings.php` (`/local/modules/vendor.module/.settings.php`), the `services`, `controllers`, `routing`, and `console` sections are specified. Its content is automatically merged into the global container after `includeModule`.

## File Priority

- Components: `/local/components/<vendor>/<name>/` override `/bitrix/components/<vendor>/<name>/`.
- Component templates in a site template: `/local/templates/<id>/components/...` override everything else.
- System files (e.g., `header.php`) are searched first in `/local/`, then in `/bitrix/`.

## When `php_interface/init.php` is Needed

Only for:

- Registering **dynamic** event handlers (`registerEventHandler`) that cannot be tied to the installation of a specific module.
- Project constants that must be available before modules are included.
- Compatibility hooks.

For everything else — create a module and use its `install/index.php`, `include.php`, and `.settings.php`.
