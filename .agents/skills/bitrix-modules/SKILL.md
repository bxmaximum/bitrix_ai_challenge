---
name: bitrix-modules
description: Покрывает создание и сопровождение собственного модуля Bitrix в /local/modules/<vendor>.<module>/ — класс CModule, install/index.php, DoInstall и DoUninstall, install/version.php с $arModuleVersion, регистрация событий и агентов на установке, опции модуля (options.php), генерация через make:module. Применяется при создании нового модуля, доработке установки/удаления, регистрации обработчиков событий и публикации опций модуля в админке. Ключевые термины — CModule, DoInstall, DoUninstall, module manifest, install/index.php, make:module, vendor.module.
---

# Модули Bitrix

## Идентификатор и неймспейс

- Идентификатор: `<vendor>.<module>` (нижний регистр, без `_`, без цифры в начале).
- Класс установщика: `<vendor>_<module>` (точка → `_`).
- Неймспейс: `\<Vendor>\<Module>\...` (точка → `\`, CamelCase).
- «Собственный» модуль без партнёрства — одно слово, например `mytest`, класс — `mytest`, неймспейс — `\Mytest`.

## Быстрое создание

```bash
php bitrix/bitrix.php make:module vendor.module
```

Команда создаст скелет с `install/index.php`, `version.php`, `lang/ru/install/index.php`, `.settings.php` и папкой `/lib/`. Доступно с main 25.900.0.

## Минимальный каркас

```
/local/modules/vendor.module/
├── install/
│   ├── index.php
│   └── version.php
├── lang/ru/install/index.php
├── lib/                         # PSR-4, Vendor\Module\...
├── .settings.php                # controllers, services, console, routing
└── include.php                  # опционально, для registerNamespace/registerAutoLoadClasses
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

Наследуемся от `CModule`, реализуем `DoInstall`/`DoUninstall`. Базовый шаблон:

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
        // Создание таблиц через ORM Entity:
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

## Языковые файлы

`/local/modules/vendor.module/lang/ru/install/index.php`:

```php
<?php
$MESS['VENDOR_MODULE_NAME'] = 'Vendor. Module';
$MESS['VENDOR_MODULE_DESCRIPTION'] = 'Описание модуля';
```

## Таблицы БД

Не пиши сырой SQL для создания таблиц. Опиши сущность в `/lib/Model/PostTable.php` и создавай таблицу через ORM:

```php
\Bitrix\Main\Loader::includeModule('vendor.module');
\Vendor\Module\Model\PostTable::getEntity()->createDbTable();
```

Для удаления:

```php
\Bitrix\Main\Application::getConnection()->dropTable(
    \Vendor\Module\Model\PostTable::getTableName()
);
```

## `.settings.php` модуля

Минимум, чтобы работали контроллеры:

```php
<?php
return [
    'controllers' => [
        'value' => [
            'defaultNamespace' => '\\Vendor\\Module\\Infrastructure\\Controller',
        ],
        'readonly' => true,
    ],
];
```

Добавляй секции `services`, `console`, `routing` по мере необходимости — см. скиллы `bitrix-service-locator`, `bitrix-console-commands`, `bitrix-routing`.

## Установка и удаление

- **Собственные модули**: *Настройки → Настройки продукта → Модули*.
- **Партнёрские**: *Marketplace → Установленные решения*.
- Программно: `\Bitrix\Main\ModuleManager::registerModule('vendor.module')` / `unRegisterModule`.

## Чеклист качественного модуля

- [ ] `install/index.php` идемпотентен: повторная установка не ломает систему.
- [ ] В `DoUninstall` снимаются **все** обработчики событий, добавленные на `DoInstall`.
- [ ] Таблицы создаются через ORM, колонки — через `addField`/миграции.
- [ ] Неймспейс соответствует идентификатору и PSR-4-структуре папок `/lib/`.
- [ ] Добавлен `.settings.php` с нужными секциями.
- [ ] Публикуемые сущности (события, сервисы) выделены в `Public/`, внутренние — в `Internals/`.
