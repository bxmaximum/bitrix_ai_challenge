<?php

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class barista_telegramlogger extends CModule
{
    public $MODULE_ID = 'barista.telegramlogger';
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
        $this->MODULE_NAME = Loc::getMessage('BARISTA_TELEGRAMLOGGER_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('BARISTA_TELEGRAMLOGGER_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('BARISTA_TELEGRAMLOGGER_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('BARISTA_TELEGRAMLOGGER_PARTNER_URI');
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installDB();
        $this->installEvents();
        $this->installAgents();
    }

    public function DoUninstall()
    {
        $this->uninstallAgents();
        $this->uninstallEvents();
        $this->uninstallDB();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function installDB()
    {
        $connection = Application::getConnection();

        $connection->query('
            CREATE TABLE IF NOT EXISTS barista_telegramlogger_queue (
                ID INT(11) NOT NULL AUTO_INCREMENT,
                TIMESTAMP_X DATETIME NOT NULL,
                EVENT_ID INT(11) NOT NULL,
                EVENT_DATA TEXT NOT NULL,
                STATUS VARCHAR(10) NOT NULL DEFAULT \'NEW\',
                RETRY_COUNT INT(3) NOT NULL DEFAULT 0,
                PRIMARY KEY (ID),
                INDEX ix_status_retry (STATUS, RETRY_COUNT)
            );
        ');

        $connection->query('
            CREATE TABLE IF NOT EXISTS barista_telegramlogger_history (
                ID INT(11) NOT NULL AUTO_INCREMENT,
                EVENT_HASH VARCHAR(32) NOT NULL,
                LAST_SENT_TIMESTAMP_X DATETIME NOT NULL,
                PRIMARY KEY (ID),
                UNIQUE KEY uk_event_hash (EVENT_HASH)
            );
        ');
    }

    public function uninstallDB()
    {
        $connection = Application::getConnection();
        $connection->query('DROP TABLE IF EXISTS barista_telegramlogger_queue');
        $connection->query('DROP TABLE IF EXISTS barista_telegramlogger_history');
    }

    public function installEvents()
    {
        EventManager::getInstance()->registerEventHandler(
            'main',
            'OnEventLog',
            $this->MODULE_ID,
            '\\Barista\\Telegramlogger\\EventHandler',
            'handleEventLog'
        );
    }

    public function uninstallEvents()
    {
        EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnEventLog',
            $this->MODULE_ID,
            '\\Barista\\Telegramlogger\\EventHandler',
            'handleEventLog'
        );
    }

    public function installAgents()
    {
        \CAgent::AddAgent(
            '\\Barista\\Telegramlogger\\Agent::processQueue();',
            $this->MODULE_ID,
            'N',
            60, // interval in seconds
            '',
            'Y',
            '',
            100
        );
    }

    public function uninstallAgents()
    {
        \CAgent::RemoveModuleAgents($this->MODULE_ID);
    }
}
