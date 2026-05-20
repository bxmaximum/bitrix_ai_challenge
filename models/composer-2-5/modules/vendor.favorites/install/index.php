<?php

declare(strict_types=1);

use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Vendor\Favorites\EventHandler;
use Vendor\Favorites\Model\FavoritesTable;

Loc::loadMessages(__FILE__);

final class vendor_favorites extends CModule
{
    public $MODULE_ID = 'vendor.favorites';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME = 'Vendor';
    public $PARTNER_URI = '';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'] ?? '1.0.0';
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'] ?? '';

        $this->MODULE_NAME = (string) Loc::getMessage('VENDOR_FAVORITES_MODULE_NAME');
        $this->MODULE_DESCRIPTION = (string) Loc::getMessage('VENDOR_FAVORITES_MODULE_DESCRIPTION');
    }

    public function DoInstall(): void
    {
        global $APPLICATION, $USER;

        if (!$USER->IsAdmin()) {
            $APPLICATION->ThrowException(Loc::getMessage('VENDOR_FAVORITES_INSTALL_DENIED'));

            return;
        }

        ModuleManager::registerModule($this->MODULE_ID);

        $this->installDb();
        $this->installEvents();
        $this->installFiles();
    }

    public function DoUninstall(): void
    {
        global $APPLICATION, $USER;

        if (!$USER->IsAdmin()) {
            $APPLICATION->ThrowException(Loc::getMessage('VENDOR_FAVORITES_INSTALL_DENIED'));

            return;
        }

        $this->uninstallEvents();
        $this->uninstallDb();
        $this->uninstallFiles();

        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function installDb(): void
    {
        if (!\Bitrix\Main\Loader::includeModule($this->MODULE_ID)) {
            return;
        }

        $connection = \Bitrix\Main\Application::getConnection();
        $tableName = FavoritesTable::getTableName();

        if (!$connection->isTableExists($tableName)) {
            FavoritesTable::getEntity()->createDbTable();

            $connection->queryExecute(
                'CREATE UNIQUE INDEX ux_vendor_favorites_user_product ON ' . $tableName . ' (USER_ID, PRODUCT_ID)',
            );
        }
    }

    public function uninstallDb(): void
    {
        if (!\Bitrix\Main\Loader::includeModule($this->MODULE_ID)) {
            return;
        }

        $connection = \Bitrix\Main\Application::getConnection();
        $tableName = FavoritesTable::getTableName();

        if ($connection->isTableExists($tableName)) {
            $connection->dropTable($tableName);
        }
    }

    public function installEvents(): void
    {
        $eventManager = EventManager::getInstance();

        $eventManager->registerEventHandler(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            EventHandler::class,
            'onAfterUserAuthorize',
        );

        $eventManager->registerEventHandler(
            'iblock',
            'OnAfterIBlockElementDelete',
            $this->MODULE_ID,
            EventHandler::class,
            'onAfterIBlockElementDelete',
        );

        $eventManager->registerEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            EventHandler::class,
            'onAfterIBlockElementUpdate',
        );
    }

    public function uninstallEvents(): void
    {
        $eventManager = EventManager::getInstance();

        $eventManager->unRegisterEventHandler(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            EventHandler::class,
            'onAfterUserAuthorize',
        );

        $eventManager->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementDelete',
            $this->MODULE_ID,
            EventHandler::class,
            'onAfterIBlockElementDelete',
        );

        $eventManager->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            EventHandler::class,
            'onAfterIBlockElementUpdate',
        );
    }

    public function installFiles(): void
    {
        CopyDirFiles(
            __DIR__ . '/components',
            $_SERVER['DOCUMENT_ROOT'] . '/local/components',
            true,
            true,
        );
    }

    public function uninstallFiles(): void
    {
        DeleteDirFilesEx('/local/components/vendor/favorites.button');
    }
}
