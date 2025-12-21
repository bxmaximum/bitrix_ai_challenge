<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Vendor\Favorites\Model\FavoritesTable;

Loc::loadMessages(__FILE__);

class vendor_favorites extends CModule
{
    public $MODULE_ID = 'vendor.favorites';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . '/version.php');

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('VENDOR_FAVORITES_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('VENDOR_FAVORITES_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('VENDOR_FAVORITES_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('VENDOR_FAVORITES_PARTNER_URI');
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installDB();
        $this->installEvents();
        $this->installFiles();
    }

    public function DoUninstall()
    {
        $this->uninstallFiles();
        $this->uninstallEvents();
        $this->uninstallDB();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function installDB()
    {
        if (\Bitrix\Main\Loader::includeModule($this->MODULE_ID)) {
            if (!Application::getConnection()->isTableExists(FavoritesTable::getTableName())) {
                FavoritesTable::getEntity()->createDbTable();
            }
        }
    }

    public function uninstallDB()
    {
        if (\Bitrix\Main\Loader::includeModule($this->MODULE_ID)) {
            if (Application::getConnection()->isTableExists(FavoritesTable::getTableName())) {
                Application::getConnection()->dropTable(FavoritesTable::getTableName());
            }
        }
    }

    public function installEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            '\Vendor\Favorites\EventHandler',
            'onAfterUserAuthorize'
        );
        $eventManager->registerEventHandler(
            'iblock',
            'OnAfterIBlockElementDelete',
            $this->MODULE_ID,
            '\Vendor\Favorites\EventHandler',
            'onAfterIBlockElementDelete'
        );
        $eventManager->registerEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            '\Vendor\Favorites\EventHandler',
            'onAfterIBlockElementUpdate'
        );
    }

    public function uninstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            '\Vendor\Favorites\EventHandler',
            'onAfterUserAuthorize'
        );
        $eventManager->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementDelete',
            $this->MODULE_ID,
            '\Vendor\Favorites\EventHandler',
            'onAfterIBlockElementDelete'
        );
        $eventManager->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            '\Vendor\Favorites\EventHandler',
            'onAfterIBlockElementUpdate'
        );
    }

    public function installFiles()
    {
        CopyDirFiles(
            __DIR__ . '/components',
            Application::getDocumentRoot() . '/local/components',
            true,
            true
        );
    }

    public function uninstallFiles()
    {
        DeleteDirFilesEx('/local/components/vendor/favorites.button');
    }
}

