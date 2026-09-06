<?php

declare(strict_types=1);

use Bitrix\Main\EventManager;
use Bitrix\Main\ModuleManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

class vendor_favorites extends CModule
{
    public $MODULE_ID = 'vendor.favorites';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_GROUP_RIGHTS = 'Y';
    public $MODULE_SORT = 100;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        IncludeModuleLangFile(__DIR__ . '/index.php');

        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        if (isset($arModuleVersion['VERSION'], $arModuleVersion['VERSION_DATE'])) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = GetMessage('VENDOR_FAVORITES_MODULE_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('VENDOR_FAVORITES_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = GetMessage('VENDOR_FAVORITES_PARTNER_NAME');
        $this->PARTNER_URI = 'https://1c-bitrix.ru';
    }

    public function DoInstall(): void
    {
        global $USER, $APPLICATION;

        if (!$USER->IsAdmin()) {
            return;
        }

        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            $APPLICATION->ThrowException(GetMessage('VENDOR_FAVORITES_NEED_IBLOCK'));

            return;
        }

        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();

        $APPLICATION->IncludeAdminFile(
            GetMessage('VENDOR_FAVORITES_INSTALL_TITLE'),
            __DIR__ . '/step.php'
        );
    }

    public function DoUninstall(): void
    {
        global $USER, $APPLICATION;

        if (!$USER->IsAdmin()) {
            return;
        }

        $this->UnInstallEvents();
        $this->UnInstallDB();
        $this->UnInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            GetMessage('VENDOR_FAVORITES_UNINSTALL_TITLE'),
            __DIR__ . '/unstep.php'
        );
    }

    public function InstallDB(): bool
    {
        global $DB;

        $connection = \Bitrix\Main\Application::getConnection();
        if (!$connection->isTableExists('b_vendor_favorites')) {
            $DB->Query("
                CREATE TABLE b_vendor_favorites (
                    ID INT NOT NULL AUTO_INCREMENT,
                    USER_ID INT NOT NULL,
                    PRODUCT_ID INT NOT NULL,
                    DATE_INSERT DATETIME NOT NULL,
                    PRIMARY KEY (ID),
                    UNIQUE KEY UX_VENDOR_FAV_USER_PRODUCT (USER_ID, PRODUCT_ID),
                    KEY IX_VENDOR_FAV_PRODUCT (PRODUCT_ID)
                ) ENGINE=InnoDB
            ");
        }

        COption::SetOptionString($this->MODULE_ID, 'catalog_iblock_id', '0');
        COption::SetOptionString($this->MODULE_ID, 'guest_cookie_ttl', '2592000');
        COption::SetOptionString($this->MODULE_ID, 'list_cache_ttl', '3600');
        COption::SetOptionString($this->MODULE_ID, 'module_enabled', 'Y');
        COption::SetOptionString($this->MODULE_ID, 'GROUP_DEFAULT_RIGHT', 'R');

        return true;
    }

    public function UnInstallDB(): bool
    {
        global $DB;
        $DB->Query('DROP TABLE IF EXISTS b_vendor_favorites');
        COption::RemoveOption($this->MODULE_ID);

        return true;
    }

    public function InstallEvents(): bool
    {
        $em = EventManager::getInstance();
        $em->registerEventHandler(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterUserAuthorize'
        );
        $em->registerEventHandler(
            'iblock',
            'OnAfterIBlockElementDelete',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterIBlockElementDelete'
        );
        $em->registerEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterIBlockElementUpdate'
        );

        return true;
    }

    public function UnInstallEvents(): bool
    {
        $em = EventManager::getInstance();
        $em->unRegisterEventHandler(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterUserAuthorize'
        );
        $em->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementDelete',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterIBlockElementDelete'
        );
        $em->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterIBlockElementUpdate'
        );

        return true;
    }

    public function InstallFiles(): bool
    {
        CopyDirFiles(
            __DIR__ . '/components',
            $_SERVER['DOCUMENT_ROOT'] . '/local/components',
            true,
            true
        );

        return true;
    }

    public function UnInstallFiles(): bool
    {
        DeleteDirFilesEx('/local/components/vendor/favorites.button');

        return true;
    }
}
