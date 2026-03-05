<?php
declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Vendor\Favorites\EventHandler;
use Vendor\Favorites\Model\FavoritesTable;
use Vendor\Favorites\Service\ModuleSettings;

Loc::loadMessages(__FILE__);

/**
 * Module installer for vendor.favorites.
 */
final class vendor_favorites extends CModule
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
        $versionFile = __DIR__ . '/version.php';
        if (is_file($versionFile)) {
            include $versionFile;
        }

        $this->MODULE_VERSION = (string) ($arModuleVersion['VERSION'] ?? '1.0.0');
        $this->MODULE_VERSION_DATE = (string) ($arModuleVersion['VERSION_DATE'] ?? '');
        $this->MODULE_NAME = (string) Loc::getMessage('VENDOR_FAVORITES_MODULE_NAME');
        $this->MODULE_DESCRIPTION = (string) Loc::getMessage('VENDOR_FAVORITES_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = (string) Loc::getMessage('VENDOR_FAVORITES_PARTNER_NAME');
        $this->PARTNER_URI = (string) Loc::getMessage('VENDOR_FAVORITES_PARTNER_URI');
    }

    /**
     * Installs module and all required artifacts.
     */
    public function DoInstall(): void
    {
        global $USER;

        if (!$USER->IsAdmin()) {
            return;
        }

        ModuleManager::registerModule($this->MODULE_ID);

        Loader::includeModule($this->MODULE_ID);
        $this->installDB();
        $this->installFiles();
        $this->installEvents();
        $this->installOptions();
    }

    /**
     * Removes module and all installed artifacts.
     */
    public function DoUninstall(): void
    {
        global $USER;

        if (!$USER->IsAdmin()) {
            return;
        }

        $this->unInstallEvents();
        $this->unInstallFiles();
        $this->unInstallDB();
        $this->unInstallOptions();

        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    /**
     * Creates favorites ORM table.
     */
    public function installDB(): void
    {
        $connection = Application::getConnection();
        if (!$connection->isTableExists(FavoritesTable::getTableName())) {
            FavoritesTable::getEntity()->createDbTable();
        }
    }

    /**
     * Drops favorites ORM table.
     */
    public function unInstallDB(): void
    {
        $connection = Application::getConnection();
        if ($connection->isTableExists(FavoritesTable::getTableName())) {
            $connection->dropTable(FavoritesTable::getTableName());
        }
    }

    /**
     * Registers system event handlers.
     */
    public function installEvents(): void
    {
        $eventManager = EventManager::getInstance();

        $eventManager->registerEventHandlerCompatible(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            EventHandler::class,
            'onAfterUserAuthorize'
        );
        $eventManager->registerEventHandlerCompatible(
            'iblock',
            'OnAfterIBlockElementDelete',
            $this->MODULE_ID,
            EventHandler::class,
            'onAfterIBlockElementDelete'
        );
        $eventManager->registerEventHandlerCompatible(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            EventHandler::class,
            'onAfterIBlockElementUpdate'
        );
    }

    /**
     * Unregisters system event handlers.
     */
    public function unInstallEvents(): void
    {
        $eventManager = EventManager::getInstance();

        $eventManager->unRegisterEventHandler(
            'main',
            'OnAfterUserAuthorize',
            $this->MODULE_ID,
            EventHandler::class,
            'onAfterUserAuthorize'
        );
        $eventManager->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementDelete',
            $this->MODULE_ID,
            EventHandler::class,
            'onAfterIBlockElementDelete'
        );
        $eventManager->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            EventHandler::class,
            'onAfterIBlockElementUpdate'
        );
    }

    /**
     * Installs public component files.
     */
    public function installFiles(): void
    {
        CopyDirFiles(
            $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . $this->MODULE_ID . '/install/components',
            $_SERVER['DOCUMENT_ROOT'] . '/local/components',
            true,
            true
        );
    }

    /**
     * Removes public component files.
     */
    public function unInstallFiles(): void
    {
        DeleteDirFilesEx('/local/components/vendor/favorites.button');
    }

    /**
     * Saves default module options.
     */
    private function installOptions(): void
    {
        Option::set($this->MODULE_ID, ModuleSettings::OPTION_ENABLED, ModuleSettings::DEFAULT_ENABLED);
        Option::set(
            $this->MODULE_ID,
            ModuleSettings::OPTION_COOKIE_LIFETIME_DAYS,
            (string) ModuleSettings::DEFAULT_COOKIE_LIFETIME_DAYS
        );
        Option::set($this->MODULE_ID, ModuleSettings::OPTION_IBLOCK_ID, '0');
    }

    /**
     * Deletes persisted module options.
     */
    private function unInstallOptions(): void
    {
        Option::delete($this->MODULE_ID);
    }
}
