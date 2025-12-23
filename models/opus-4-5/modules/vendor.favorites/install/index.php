<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

/**
 * Класс установщика модуля "Избранное"
 *
 * Управляет установкой, удалением модуля, созданием таблиц БД,
 * регистрацией событий и копированием компонентов.
 */
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
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('VENDOR_FAVORITES_MODULE_NAME') ?: 'Избранное для каталога';
        $this->MODULE_DESCRIPTION = Loc::getMessage('VENDOR_FAVORITES_MODULE_DESC') ?: 'Модуль для работы с избранным товарами в каталоге';
        $this->PARTNER_NAME = 'Vendor';
        $this->PARTNER_URI = 'https://example.com';
    }

    /**
     * Выполняет установку модуля
     */
    public function DoInstall(): void
    {
        global $APPLICATION;

        ModuleManager::registerModule($this->MODULE_ID);

        $this->installDB();
        $this->installEvents();
        $this->installFiles();

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('VENDOR_FAVORITES_INSTALL_TITLE') ?: 'Установка модуля',
            __DIR__ . '/step.php'
        );
    }

    /**
     * Выполняет удаление модуля
     */
    public function DoUninstall(): void
    {
        global $APPLICATION;

        $this->uninstallEvents();
        $this->uninstallFiles();
        $this->uninstallDB();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('VENDOR_FAVORITES_UNINSTALL_TITLE') ?: 'Удаление модуля',
            __DIR__ . '/unstep.php'
        );
    }

    /**
     * Создает таблицы в БД
     */
    public function installDB(): void
    {
        $connection = Application::getConnection();

        if (!$connection->isTableExists('vendor_favorites')) {
            $connection->queryExecute("
                CREATE TABLE vendor_favorites (
                    ID INT(11) NOT NULL AUTO_INCREMENT,
                    USER_ID INT(11) NOT NULL,
                    PRODUCT_ID INT(11) NOT NULL,
                    IBLOCK_ID INT(11) NOT NULL,
                    DATE_CREATED DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    UNIQUE KEY ux_user_product (USER_ID, PRODUCT_ID),
                    KEY ix_user_id (USER_ID),
                    KEY ix_product_id (PRODUCT_ID),
                    KEY ix_iblock_id (IBLOCK_ID)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }

    /**
     * Удаляет таблицы из БД
     */
    public function uninstallDB(): void
    {
        $connection = Application::getConnection();

        if ($connection->isTableExists('vendor_favorites')) {
            $connection->queryExecute('DROP TABLE vendor_favorites');
        }

        // Удаляем настройки модуля
        Option::delete($this->MODULE_ID);
    }

    /**
     * Регистрирует обработчики событий
     */
    public function installEvents(): void
    {
        $eventManager = EventManager::getInstance();

        // Миграция избранного из cookie при авторизации
        $eventManager->registerEventHandler(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterUserAuthorize'
        );

        // Удаление товара из избранного при удалении элемента инфоблока
        $eventManager->registerEventHandler(
            'iblock',
            'OnAfterIBlockElementDelete',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterIBlockElementDelete'
        );

        // Инвалидация кэша при изменении элемента инфоблока
        $eventManager->registerEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterIBlockElementUpdate'
        );
    }

    /**
     * Удаляет обработчики событий
     */
    public function uninstallEvents(): void
    {
        $eventManager = EventManager::getInstance();

        $eventManager->unRegisterEventHandler(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterUserAuthorize'
        );

        $eventManager->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementDelete',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterIBlockElementDelete'
        );

        $eventManager->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            \Vendor\Favorites\EventHandler::class,
            'onAfterIBlockElementUpdate'
        );
    }

    /**
     * Копирует файлы компонентов
     */
    public function installFiles(): void
    {
        CopyDirFiles(
            __DIR__ . '/components',
            Application::getDocumentRoot() . '/local/components',
            true,
            true
        );
    }

    /**
     * Удаляет файлы компонентов
     */
    public function uninstallFiles(): void
    {
        DeleteDirFilesEx('/local/components/vendor/favorites.button');
    }
}

