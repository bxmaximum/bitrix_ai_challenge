<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Loader;
use Bxmax\Booking\Model\EntryTable;
use Bxmax\Booking\Model\SlotTable;

Loc::loadMessages(__FILE__);

class bxmax_booking extends CModule
{
    public $MODULE_ID = 'bxmax.booking';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME = 'Bxmax';
    public $PARTNER_URI = 'https://bxmax.ru';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'] ?? '1.0.0';
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'] ?? '';
        $this->MODULE_NAME = (string)Loc::getMessage('BXMAX_BOOKING_MODULE_NAME');
        $this->MODULE_DESCRIPTION = (string)Loc::getMessage('BXMAX_BOOKING_MODULE_DESCRIPTION');
    }

    public function DoInstall(): void
    {
        global $USER, $APPLICATION;

        if (is_object($USER) && method_exists($USER, 'IsAdmin') && !$USER->IsAdmin()) {
            $APPLICATION->ThrowException('Access denied');
            return;
        }

        ModuleManager::registerModule($this->MODULE_ID);
        Loader::includeModule($this->MODULE_ID);
        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();
    }

    public function DoUninstall(): void
    {
        global $USER;

        if (is_object($USER) && method_exists($USER, 'IsAdmin') && !$USER->IsAdmin()) {
            return;
        }

        $this->UnInstallFiles();
        $this->UnInstallEvents();
        Loader::includeModule($this->MODULE_ID);
        $this->UnInstallDB();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function InstallDB(): void
    {
        $connection = Application::getConnection();

        if (!$connection->isTableExists(SlotTable::getTableName())) {
            SlotTable::getEntity()->createDbTable();
            $connection->createIndex(
                SlotTable::getTableName(),
                'ux_bxmax_booking_slot_master_starts',
                ['MASTER_ID', 'STARTS_AT'],
                null,
                \Bitrix\Main\DB\Connection::INDEX_UNIQUE
            );
        }

        if (!$connection->isTableExists(EntryTable::getTableName())) {
            EntryTable::getEntity()->createDbTable();
        }
    }

    public function UnInstallDB(): void
    {
        $connection = Application::getConnection();
        if ($connection->isTableExists(EntryTable::getTableName())) {
            $connection->dropTable(EntryTable::getTableName());
        }
        if ($connection->isTableExists(SlotTable::getTableName())) {
            $connection->dropTable(SlotTable::getTableName());
        }
    }

    public function InstallEvents(): void
    {
        $helper = $this->eventType();
        $type = new CEventType();
        $existing = CEventType::GetList(['EVENT_NAME' => 'BXMAX_BOOKING_NEW', 'LID' => 'ru'])->Fetch();
        if (!$existing) {
            $type->Add([
                'EVENT_NAME' => 'BXMAX_BOOKING_NEW',
                'LID' => 'ru',
                'NAME' => Loc::getMessage('BXMAX_BOOKING_EVENT_NAME'),
                'DESCRIPTION' => Loc::getMessage('BXMAX_BOOKING_EVENT_DESC'),
            ]);
        }

        $by = 'id';
        $order = 'desc';
        $messageExists = CEventMessage::GetList($by, $order, [
            'EVENT_NAME' => 'BXMAX_BOOKING_NEW',
            'EVENT_NAME_EXACT_MATCH' => 'Y',
        ])->Fetch();

        if (!$messageExists) {
            $message = new CEventMessage();
            $message->Add([
                'ACTIVE' => 'Y',
                'EVENT_NAME' => 'BXMAX_BOOKING_NEW',
                'LID' => ['s1'],
                'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
                'EMAIL_TO' => '#EMAIL_TO#',
                'SUBJECT' => Loc::getMessage('BXMAX_BOOKING_MAIL_SUBJECT'),
                'BODY_TYPE' => 'text',
                'MESSAGE' => Loc::getMessage('BXMAX_BOOKING_MAIL_BODY'),
            ]);
        }

        unset($helper);
    }

    public function UnInstallEvents(): void
    {
        $by = 'id';
        $order = 'desc';
        $res = CEventMessage::GetList($by, $order, [
            'EVENT_NAME' => 'BXMAX_BOOKING_NEW',
            'EVENT_NAME_EXACT_MATCH' => 'Y',
        ]);
        while ($row = $res->Fetch()) {
            CEventMessage::Delete($row['ID']);
        }
        CEventType::Delete(['EVENT_NAME' => 'BXMAX_BOOKING_NEW']);
    }

    public function InstallFiles(): void
    {
        CopyDirFiles(
            __DIR__ . '/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin',
            true,
            true
        );
        CopyDirFiles(
            __DIR__ . '/components',
            $_SERVER['DOCUMENT_ROOT'] . '/local/components',
            true,
            true
        );
    }

    public function UnInstallFiles(): void
    {
        DeleteDirFiles(
            __DIR__ . '/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin'
        );

        $componentDir = $_SERVER['DOCUMENT_ROOT'] . '/local/components/bxmax';
        if (is_dir($componentDir . '/booking.form')) {
            Directory::deleteDirectory($componentDir . '/booking.form');
        }
        if (is_dir($componentDir) && count(scandir($componentDir)) <= 2) {
            Directory::deleteDirectory($componentDir);
        }
    }

    public function GetModuleRightList(): array
    {
        return [
            'reference_id' => ['D', 'R', 'W'],
            'reference' => [
                '[D] ' . Loc::getMessage('BXMAX_BOOKING_RIGHT_D'),
                '[R] ' . Loc::getMessage('BXMAX_BOOKING_RIGHT_R'),
                '[W] ' . Loc::getMessage('BXMAX_BOOKING_RIGHT_W'),
            ],
        ];
    }

    private function eventType(): string
    {
        return 'BXMAX_BOOKING_NEW';
    }
}
