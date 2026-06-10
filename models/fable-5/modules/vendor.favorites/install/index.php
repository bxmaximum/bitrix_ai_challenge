<?php

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

/**
 * Установщик модуля «Избранное» (vendor.favorites).
 */
final class vendor_favorites extends CModule
{
    public $MODULE_ID = 'vendor.favorites';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_GROUP_RIGHTS = 'N';
    public $PARTNER_NAME = 'Vendor';
    public $PARTNER_URI = 'https://vendor.example.com';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'] ?? '';
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'] ?? '';

        $this->MODULE_NAME = (string)Loc::getMessage('VENDOR_FAVORITES_MODULE_NAME');
        $this->MODULE_DESCRIPTION = (string)Loc::getMessage('VENDOR_FAVORITES_MODULE_DESCRIPTION');
    }

    public function DoInstall(): void
    {
        global $USER, $APPLICATION;

        if (!$USER->IsAdmin()) {
            $APPLICATION->ThrowException(Loc::getMessage('VENDOR_FAVORITES_ACCESS_DENIED'));

            return;
        }

        ModuleManager::registerModule($this->MODULE_ID);

        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();
    }

    public function DoUninstall(): void
    {
        global $USER;

        if (!$USER->IsAdmin()) {
            return;
        }

        $this->UnInstallEvents();
        $this->UnInstallFiles();
        $this->UnInstallDB();

        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function InstallDB()
    {
        Loader::includeModule($this->MODULE_ID);

        $entity = \Vendor\Favorites\Model\FavoritesTable::getEntity();
        $connection = Application::getConnection();
        $tableName = \Vendor\Favorites\Model\FavoritesTable::getTableName();

        if (!$connection->isTableExists($tableName)) {
            $entity->createDbTable();
            $connection->createIndex(
                $tableName,
                'ux_vendor_favorites_user_product',
                ['USER_ID', 'PRODUCT_ID'],
                null,
                \Bitrix\Main\DB\Connection::INDEX_UNIQUE,
            );
        }
    }

    public function UnInstallDB()
    {
        Loader::includeModule($this->MODULE_ID);

        $connection = Application::getConnection();
        $tableName = \Vendor\Favorites\Model\FavoritesTable::getTableName();

        if ($connection->isTableExists($tableName)) {
            $connection->dropTable($tableName);
        }

        \Bitrix\Main\Config\Option::delete($this->MODULE_ID);
    }

    public function InstallEvents()
    {
        $eventManager = EventManager::getInstance();

        $eventManager->registerEventHandlerCompatible(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterUserAuthorize',
        );

        $eventManager->registerEventHandlerCompatible(
            'iblock',
            'OnAfterIBlockElementDelete',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterIBlockElementDelete',
        );

        $eventManager->registerEventHandlerCompatible(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterIBlockElementUpdate',
        );
    }

    public function UnInstallEvents()
    {
        $eventManager = EventManager::getInstance();

        $eventManager->unRegisterEventHandler(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterUserAuthorize',
        );

        $eventManager->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementDelete',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterIBlockElementDelete',
        );

        $eventManager->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterIBlockElementUpdate',
        );
    }

    public function InstallFiles()
    {
        CopyDirFiles(
            __DIR__ . '/components',
            $_SERVER['DOCUMENT_ROOT'] . '/local/components',
            true,
            true,
        );
    }

    public function UnInstallFiles()
    {
        DeleteDirFilesEx('/local/components/vendor/favorites.button');
    }
}
