<?php

use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
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
    public $PARTNER_URI = 'https://example.com';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = (string)($arModuleVersion['VERSION'] ?? '');
        $this->MODULE_VERSION_DATE = (string)($arModuleVersion['VERSION_DATE'] ?? '');
        $this->MODULE_NAME = (string)Loc::getMessage('VENDOR_FAVORITES_MODULE_NAME');
        $this->MODULE_DESCRIPTION = (string)Loc::getMessage('VENDOR_FAVORITES_MODULE_DESCRIPTION');
    }

    public function DoInstall(): void
    {
        global $APPLICATION, $USER;

        if (!$USER->IsAdmin()) {
            $APPLICATION->ThrowException((string)Loc::getMessage('VENDOR_FAVORITES_ACCESS_DENIED'));
            return;
        }

        ModuleManager::registerModule($this->MODULE_ID);
        Loader::includeModule($this->MODULE_ID);

        $this->installDb();
        $this->installEvents();
        $this->installFiles();
    }

    public function DoUninstall(): void
    {
        global $USER;

        if (!$USER->IsAdmin()) {
            return;
        }

        Loader::includeModule($this->MODULE_ID);

        $this->uninstallEvents();
        $this->uninstallFiles();
        $this->uninstallDb();

        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function installDb(): void
    {
        $connection = Application::getConnection();
        if ($connection->isTableExists(FavoritesTable::getTableName())) {
            return;
        }

        FavoritesTable::getEntity()->createDbTable();
        $this->createIndexes($connection);
    }

    public function uninstallDb(): void
    {
        $connection = Application::getConnection();
        if ($connection->isTableExists(FavoritesTable::getTableName())) {
            $connection->dropTable(FavoritesTable::getTableName());
        }
    }

    public function installEvents(): void
    {
        $eventManager = EventManager::getInstance();

        $eventManager->registerEventHandlerCompatible(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            EventHandler::class,
            'onAfterUserAuthorize',
        );
        $eventManager->registerEventHandlerCompatible(
            'iblock',
            'OnAfterIBlockElementDelete',
            $this->MODULE_ID,
            EventHandler::class,
            'onAfterIBlockElementDelete',
        );
        $eventManager->registerEventHandlerCompatible(
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

    private function createIndexes(Connection $connection): void
    {
        $tableName = FavoritesTable::getTableName();

        $connection->createIndex($tableName, 'UX_VENDOR_FAVORITES_USER_PRODUCT', ['USER_ID', 'PRODUCT_ID'], null, Connection::INDEX_UNIQUE);
        $connection->createIndex($tableName, 'IX_VENDOR_FAVORITES_PRODUCT', ['PRODUCT_ID']);
    }
}
