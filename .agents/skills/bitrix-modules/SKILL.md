---
name: bitrix-modules
description: Covers creation and maintenance of a custom Bitrix module in /local/modules/<vendor>.<module>/ — CModule class, install/index.php, DoInstall and DoUninstall, install/version.php with $arModuleVersion, registration of events and agents during installation, module options (options.php), generation via make:module. Applied when creating a new module, refining installation/uninstallation, registering event handlers and publishing module options in the Admin Panel. Key terms — CModule, DoInstall, DoUninstall, module manifest, install/index.php, make:module, vendor.module.
---

# Bitrix Modules

## Identifier and Namespace

- Identifier: `<vendor>.<module>` (lowercase, no `_`, no digit at start).
- Installer class: `<vendor>_<module>` (dot → `_`).
- Namespace: `\<Vendor>\<Module>\...` (dot → `\`, CamelCase).
- "Own" module without partnership — a single word, e.g., `mytest`, class — `mytest`, namespace — `\Mytest`.

## Quick Creation

```bash
php bitrix/bitrix.php make:module vendor.module
```

The command creates a skeleton with `install/index.php`, `version.php`, `lang/en/install/index.php`, `.settings.php`, and a `/lib/` folder. Available from main 25.900.0.

## Minimal Structure

```
/local/modules/vendor.module/
├── install/
│   ├── index.php
│   └── version.php
├── lang/en/install/index.php
├── lib/                         # PSR-4, Vendor\Module\...
├── views/                       # PHP views for renderView() in controllers
├── routes/                      # Module routing files
├── .settings.php                # controllers, services, console, routing
└── include.php                  # optional, for registerNamespace/registerAutoLoadClasses
```

## `install/version.php`

```php
<?php
$arModuleVersion = [
    'VERSION' => '1.0.0',
    'VERSION_DATE' => '2026-04-16 12:00:00',
];
```

## `install/index.php`

Inherit from `CModule`, implement `DoInstall`/`DoUninstall`. Base template:

```php
<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;

Loc::loadMessages(__FILE__);

final class vendor_module extends CModule
{
    public $MODULE_ID = 'vendor.module';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME = 'Vendor';
    public $PARTNER_URI = 'https://vendor.example.com';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'] ?? '';
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'] ?? '';

        $this->MODULE_NAME = (string)Loc::getMessage('VENDOR_MODULE_NAME');
        $this->MODULE_DESCRIPTION = (string)Loc::getMessage('VENDOR_MODULE_DESCRIPTION');
    }

    public function DoInstall(): void
    {
        global $USER, $APPLICATION;

        if (!$USER->IsAdmin())
        {
            $APPLICATION->ThrowException('Access denied');
            return;
        }

        ModuleManager::registerModule($this->MODULE_ID);

        $this->installDb();
        $this->installEvents();
        $this->installAgents();
        $this->installFiles();
    }

    public function DoUninstall(): void
    {
        global $USER;
        if (!$USER->IsAdmin()) return;

        $this->uninstallAgents();
        $this->uninstallEvents();
        $this->uninstallDb();
        $this->uninstallFiles();

        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    private function installDb(): void
    {
        // Table creation via ORM Entity:
        // \Vendor\Module\Model\PostTable::getEntity()->createDbTable();
    }

    private function uninstallDb(): void
    {
        // Application::getConnection()->dropTable(PostTable::getTableName());
    }

    private function installEvents(): void
    {
        EventManager::getInstance()->registerEventHandler(
            fromModule: 'main',
            eventType: 'OnAfterUserAdd',
            toModuleId: $this->MODULE_ID,
            toClass: \Vendor\Module\Internals\Integration\Main\EventHandler\OnAfterUserAddHandler::class,
            toMethod: 'handle',
        );
    }

    private function uninstallEvents(): void
    {
        EventManager::getInstance()->unRegisterEventHandler(
            fromModule: 'main',
            eventType: 'OnAfterUserAdd',
            toModuleId: $this->MODULE_ID,
            toClass: \Vendor\Module\Internals\Integration\Main\EventHandler\OnAfterUserAddHandler::class,
            toMethod: 'handle',
        );
    }

    private function installAgents(): void
    {
        \CAgent::AddAgent(
            \Vendor\Module\Cli\Agent\QueueAgent::class . '::run();',
            $this->MODULE_ID,
            'N',
            300,
            '',
            'Y',
            '',
            100,
        );
    }

    private function uninstallAgents(): void
    {
        \CAgent::RemoveModuleAgents($this->MODULE_ID);
    }

    private function installFiles(): void
    {
        CopyDirFiles(
            __DIR__ . '/components',
            $_SERVER['DOCUMENT_ROOT'] . '/local/components',
            true,
            true,
        );
    }

    private function uninstallFiles(): void
    {
        DeleteDirFilesEx('/local/components/vendor');
    }
}
```

## Language Files

`/local/modules/vendor.module/lang/en/install/index.php`:

```php
<?php
$MESS['VENDOR_MODULE_NAME'] = 'Vendor Module';
$MESS['VENDOR_MODULE_DESCRIPTION'] = 'Module description';
```

## DB Tables

Do not use raw SQL for table creation. Describe the entity in `/lib/Model/PostTable.php` and create the table via ORM:

```php
\Bitrix\Main\Loader::includeModule('vendor.module');
\Vendor\Module\Model\PostTable::getEntity()->createDbTable();
```

For deletion:

```php
\Bitrix\Main\Application::getConnection()->dropTable(PostTable::getTableName());
```

## Module Options (`options.php`)

If you need a settings page in Admin Panel (*Settings → Module Settings → Vendor Module*):

```php
<?php
/** @var CMain $APPLICATION */
/** @var string $mid */ // module id

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

$options = [
    ['api_key', Loc::getMessage('VENDOR_API_KEY'), '', ['text', 40]],
    ['debug_mode', Loc::getMessage('VENDOR_DEBUG'), 'N', ['checkbox', 'Y']],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid())
{
    foreach ($options as $opt)
    {
        $val = $_POST[$opt[0]] ?? $opt[2];
        Option::set($mid, $opt[0], $val);
    }
}

// ... display via CAdminTabControl
```

## PSR-4 Autoloading

Nothing needs to be registered manually in `include.php` if:
1. Module is in `/local/modules/vendor.module/`.
2. Classes are in `/lib/`.
3. Namespace follows `\Vendor\Module\...`.

Bitrix `Loader` handles this automatically when `includeModule` is called.

## Checklist

- [ ] Module identifier follows `vendor.module` format.
- [ ] `DoInstall`/`DoUninstall` are implemented and idempotent.
- [ ] Event handlers and agents are registered upon installation and removed upon uninstallation.
- [ ] DB tables are managed via ORM or `SqlHelper` (DDL).
- [ ] Language files are mirrored in `lang/ru/` and `lang/en/`.
- [ ] Services and controllers are registered in `.settings.php`.
- [ ] No hardcoded strings in `index.php` (use `Loc`).
- [ ] Module is compatible with PSR-4.
- [ ] Files are copied to `/local/`, not `/bitrix/`.
