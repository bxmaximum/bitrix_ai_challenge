<?php

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\IO\Directory;

Loc::loadMessages(__FILE__);

class barista_telegramnotifier extends CModule
{
    public $MODULE_ID = 'barista.telegramnotifier';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('BARISTA_TELEGRAMNOTIFIER_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('BARISTA_TELEGRAMNOTIFIER_MODULE_DESC');

        $this->PARTNER_NAME = Loc::getMessage('BARISTA_TELEGRAMNOTIFIER_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('BARISTA_TELEGRAMNOTIFIER_PARTNER_URI');
    }

    public function doInstall(): void
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installDB();
        $this->installEvents();
        $this->installFiles();
        $this->installAgents();
    }

    public function doUninstall(): void
    {
        $this->unInstallEvents();
        $this->unInstallAgents();
        $this->unInstallDB();
        $this->unInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function installDB(): void
    {
        $connection = Application::getConnection();
        
        // Создаем таблицу для очереди уведомлений
        if (!$connection->isTableExists('barista_telegram_queue')) {
            $connection->query("
                CREATE TABLE `barista_telegram_queue` (
                    `ID` INT(11) NOT NULL AUTO_INCREMENT,
                    `EVENT_ID` VARCHAR(255) NOT NULL,
                    `CHAT_ID` VARCHAR(255) NOT NULL,
                    `MESSAGE` TEXT NOT NULL,
                    `ATTEMPTS` INT(11) DEFAULT 0,
                    `STATUS` ENUM('PENDING', 'PROCESSING', 'SENT', 'FAILED') DEFAULT 'PENDING',
                    `CREATED_AT` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `UPDATED_AT` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    `SCHEDULED_AT` TIMESTAMP NULL,
                    `ERROR_MESSAGE` TEXT NULL,
                    PRIMARY KEY (`ID`),
                    INDEX `IX_STATUS` (`STATUS`),
                    INDEX `IX_SCHEDULED` (`SCHEDULED_AT`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Создаем таблицу для хранения отправленных уведомлений (антидублирование)
        if (!$connection->isTableExists('barista_telegram_notifications')) {
            $connection->query("
                CREATE TABLE `barista_telegram_notifications` (
                    `ID` INT(11) NOT NULL AUTO_INCREMENT,
                    `EVENT_HASH` VARCHAR(64) NOT NULL,
                    `AUDIT_TYPE_ID` VARCHAR(255) NOT NULL,
                    `ITEM_ID` VARCHAR(255) NOT NULL,
                    `DESCRIPTION` TEXT NOT NULL,
                    `CREATED_AT` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `SILENCE_UNTIL` TIMESTAMP NULL,
                    PRIMARY KEY (`ID`),
                    UNIQUE KEY `UX_EVENT_HASH` (`EVENT_HASH`),
                    INDEX `IX_AUDIT_TYPE` (`AUDIT_TYPE_ID`),
                    INDEX `IX_SILENCE` (`SILENCE_UNTIL`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }

    public function unInstallDB(): void
    {
        $connection = Application::getConnection();
        $connection->query("DROP TABLE IF EXISTS `barista_telegram_queue`");
        $connection->query("DROP TABLE IF EXISTS `barista_telegram_notifications`");
    }

    public function installEvents(): void
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler(
            'main',
            'OnEventLogAdd',
            $this->MODULE_ID,
            'Barista\\TelegramNotifier\\EventHandler',
            'onEventLogAdd'
        );
    }

    public function unInstallEvents(): void
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            'main',
            'OnEventLogAdd',
            $this->MODULE_ID,
            'Barista\\TelegramNotifier\\EventHandler',
            'onEventLogAdd'
        );
    }

    public function installFiles(): void
    {
        CopyDirFiles(
            __DIR__ . '/admin/',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/',
            true,
            true
        );
    }

    public function unInstallFiles(): void
    {
        DeleteDirFiles(
            __DIR__ . '/admin/',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/'
        );
    }

    public function installAgents(): void
    {
        if (\Bitrix\Main\Loader::includeModule($this->MODULE_ID)) {
            \Barista\TelegramNotifier\Agent::install();
        }
    }

    public function unInstallAgents(): void
    {
        \Barista\TelegramNotifier\Agent::uninstall();
    }
} 