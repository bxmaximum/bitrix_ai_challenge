<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

/**
 * Установщик модуля «Избранное»
 */
class vendor_favorites extends CModule
{
    public $MODULE_ID = 'vendor.favorites';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    public function __construct()
    {
        include __DIR__ . '/version.php';

        if (isset($arModuleVersion['VERSION'], $arModuleVersion['VERSION_DATE'])) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('VENDOR_FAVORITES_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('VENDOR_FAVORITES_MODULE_DESCRIPTION');
    }

    /**
     * Установка модуля
     */
    public function DoInstall(): void
    {
        global $USER;

        if (!$USER->IsAdmin()) {
            return;
        }

        ModuleManager::registerModule($this->MODULE_ID);

        $this->InstallDB();
        $this->InstallFiles();
        $this->InstallEvents();
    }

    /**
     * Удаление модуля
     */
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

    /**
     * Создание таблицы в БД
     */
    public function InstallDB(): void
    {
        $connection = Application::getConnection();

        if (!$connection->isTableExists('vendor_favorites')) {
            $connection->queryExecute("
                CREATE TABLE vendor_favorites (
                    ID INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    USER_ID INT UNSIGNED NOT NULL,
                    PRODUCT_ID INT UNSIGNED NOT NULL,
                    DATE_ADDED DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    UNIQUE INDEX ux_user_product (USER_ID, PRODUCT_ID),
                    INDEX ix_user_id (USER_ID),
                    INDEX ix_product_id (PRODUCT_ID)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }

    /**
     * Удаление таблицы из БД
     */
    public function UnInstallDB(): void
    {
        $connection = Application::getConnection();

        if ($connection->isTableExists('vendor_favorites')) {
            $connection->queryExecute('DROP TABLE vendor_favorites');
        }
    }

    /**
     * Копирование файлов компонентов
     */
    public function InstallFiles(): void
    {
        CopyDirFiles(
            __DIR__ . '/components',
            $_SERVER['DOCUMENT_ROOT'] . '/local/components',
            true,
            true
        );
    }

    /**
     * Удаление файлов компонентов
     */
    public function UnInstallFiles(): void
    {
        DeleteDirFilesEx('/local/components/vendor/favorites.button');
    }

    /**
     * Регистрация обработчиков событий
     */
    public function InstallEvents(): void
    {
        $eventManager = EventManager::getInstance();

        $eventManager->registerEventHandlerCompatible(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            '\\Vendor\\Favorites\\EventHandler',
            'onAfterUserAuthorize'
        );

        $eventManager->registerEventHandler(
            'iblock',
            'OnAfterIBlockElementDelete',
            $this->MODULE_ID,
            '\\Vendor\\Favorites\\EventHandler',
            'onAfterIBlockElementDelete'
        );

        $eventManager->registerEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            '\\Vendor\\Favorites\\EventHandler',
            'onAfterIBlockElementUpdate'
        );
    }

    /**
     * Удаление обработчиков событий
     */
    public function UnInstallEvents(): void
    {
        $eventManager = EventManager::getInstance();

        $eventManager->unRegisterEventHandler(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            '\\Vendor\\Favorites\\EventHandler',
            'onAfterUserAuthorize'
        );

        $eventManager->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementDelete',
            $this->MODULE_ID,
            '\\Vendor\\Favorites\\EventHandler',
            'onAfterIBlockElementDelete'
        );

        $eventManager->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            '\\Vendor\\Favorites\\EventHandler',
            'onAfterIBlockElementUpdate'
        );
    }
}
