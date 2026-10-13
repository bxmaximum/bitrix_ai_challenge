<?php declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SiteTable;
use Bxmax\Booking\Model\BookingEntryTable;
use Bxmax\Booking\Model\SlotTable;
use Bxmax\Booking\Service\BookingService;
use Bxmax\Booking\Service\SlotService;

Loc::loadMessages(__FILE__);

class bxmax_booking extends CModule
{
    public $MODULE_ID = 'bxmax.booking';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;
    public $MODULE_GROUP_RIGHTS = 'Y';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = (string)($arModuleVersion['VERSION'] ?? '1.0.0');
        $this->MODULE_VERSION_DATE = (string)($arModuleVersion['VERSION_DATE'] ?? '2026-10-13 12:00:00');
        $this->MODULE_NAME = Loc::getMessage('BXMAX_BOOKING_MODULE_NAME') ?: 'Лак&Точка: Онлайн-запись';
        $this->MODULE_DESCRIPTION = Loc::getMessage('BXMAX_BOOKING_MODULE_DESCRIPTION') ?: '';
        $this->PARTNER_NAME = Loc::getMessage('BXMAX_BOOKING_PARTNER_NAME') ?: 'Bxmax';
        $this->PARTNER_URI = Loc::getMessage('BXMAX_BOOKING_PARTNER_URI') ?: 'https://ai.bitrix';
    }

    public function DoInstall(): bool
    {
        if (!$this->InstallDB())
        {
            return false;
        }

        $this->InstallEvents();
        $this->InstallFiles();

        if (Loader::includeModule($this->MODULE_ID))
        {
            try
            {
                /** @var SlotService $slotService */
                $slotService = ServiceLocator::getInstance()->get(SlotService::class);
                $slotService->generateScheduleForAllMasters(14);
            }
            catch (\Throwable)
            {
            }
        }

        return true;
    }

    public function DoUninstall(): bool
    {
        $this->UnInstallFiles();
        $this->UnInstallEvents();

        return $this->UnInstallDB(true);
    }

    public function InstallDB(): bool
    {
        try
        {
            $result = $this->installMigrations();
            if (!$result->isSuccess())
            {
                $GLOBALS['APPLICATION']?->ThrowException(implode('<br>', $result->getErrorMessages()));
                return false;
            }
        }
        catch (\Bitrix\Main\UpdateSystem\Migration\Exception $e)
        {
            $GLOBALS['APPLICATION']?->ThrowException($e->getMessage());
            return false;
        }

        ModuleManager::registerModule($this->MODULE_ID);

        if (Loader::includeModule($this->MODULE_ID))
        {
            $connection = Application::getConnection();
            if (!$connection->isTableExists(SlotTable::getTableName()))
            {
                SlotTable::getEntity()->createDbTable();
            }
            if (!$connection->isTableExists(BookingEntryTable::getTableName()))
            {
                BookingEntryTable::getEntity()->createDbTable();
            }
        }

        return true;
    }

    public function UnInstallDB(bool $dropTables = true): bool
    {
        try
        {
            $result = $this->uninstallMigrations($dropTables);
            if (!$result->isSuccess())
            {
                $GLOBALS['APPLICATION']?->ThrowException(implode('<br>', $result->getErrorMessages()));
                return false;
            }
        }
        catch (\Bitrix\Main\UpdateSystem\Migration\Exception $e)
        {
            $GLOBALS['APPLICATION']?->ThrowException($e->getMessage());
            return false;
        }

        $connection = Application::getConnection();
        if ($dropTables)
        {
            if ($connection->isTableExists('bxmax_booking_entry'))
            {
                $connection->dropTable('bxmax_booking_entry');
            }
            if ($connection->isTableExists('bxmax_booking_slot'))
            {
                $connection->dropTable('bxmax_booking_slot');
            }
        }

        Option::delete($this->MODULE_ID);
        ModuleManager::unRegisterModule($this->MODULE_ID);

        return true;
    }

    public function InstallEvents(): bool
    {
        $eventName = 'BXMAX_BOOKING_NEW';

        // Legacy CEventType / CEventMessage: D7 EventMessageTable::add() does not create b_event_message_site bindings
        $existingType = \CEventType::GetByID($eventName, 'ru')->Fetch();
        if (!$existingType)
        {
            $eventType = new \CEventType();
            $eventType->Add([
                'LID' => 'ru',
                'EVENT_NAME' => $eventName,
                'NAME' => Loc::getMessage('BXMAX_BOOKING_EVENT_NAME') ?: 'Новая онлайн-запись',
                'DESCRIPTION' => Loc::getMessage('BXMAX_BOOKING_EVENT_DESC') ?: '',
            ]);
        }

        $existingTypeEn = \CEventType::GetByID($eventName, 'en')->Fetch();
        if (!$existingTypeEn)
        {
            $eventType = new \CEventType();
            $eventType->Add([
                'LID' => 'en',
                'EVENT_NAME' => $eventName,
                'NAME' => 'New booking at Lak&Tochka studio',
                'DESCRIPTION' => Loc::getMessage('BXMAX_BOOKING_EVENT_DESC') ?: '',
            ]);
        }

        $siteIds = [];
        $sites = SiteTable::query()->setSelect(['LID'])->fetchAll();
        foreach ($sites as $site)
        {
            $siteIds[] = (string)$site['LID'];
        }
        if (empty($siteIds))
        {
            $siteIds = ['s1'];
        }

        $existingMsg = \CEventMessage::GetList('id', 'asc', ['TYPE_ID' => $eventName])->Fetch();
        if (!$existingMsg)
        {
            $message = new \CEventMessage();
            $message->Add([
                'ACTIVE' => 'Y',
                'EVENT_NAME' => $eventName,
                'LID' => $siteIds,
                'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
                'EMAIL_TO' => '#EMAIL_TO#',
                'SUBJECT' => Loc::getMessage('BXMAX_BOOKING_EVENT_SUBJECT') ?: '#SITE_NAME#: Новая запись №#BOOKING_ID#',
                'BODY_TYPE' => 'html',
                'MESSAGE' => '<div style="font-family: Georgia, serif; color: #111; line-height: 1.6; max-width: 560px;">'
                    . '<h2 style="font-weight: 400; border-bottom: 1px solid #000; padding-bottom: 12px;">Новая запись в студию «Лак&amp;Точка»</h2>'
                    . '<p><strong>Номер заявки:</strong> ##BOOKING_ID#</p>'
                    . '<p><strong>Мастер:</strong> #HTML_MASTER_NAME#</p>'
                    . '<p><strong>Услуга:</strong> #HTML_SERVICE_NAME# (#HTML_SERVICE_PRICE#)</p>'
                    . '<p><strong>Дата и время:</strong> #HTML_SLOT_TIME#</p>'
                    . '<p><strong>Клиент:</strong> #HTML_CLIENT_NAME#</p>'
                    . '<p><strong>Телефон:</strong> #HTML_CLIENT_PHONE#</p>'
                    . '<p style="font-size: 12px; color: #666;">Согласие на обработку персональных данных получено: #HTML_CONSENT_AT#</p>'
                    . '</div>',
            ]);
        }

        return true;
    }

    public function UnInstallEvents(): bool
    {
        $eventName = 'BXMAX_BOOKING_NEW';

        $rsMess = \CEventMessage::GetList('id', 'asc', ['TYPE_ID' => $eventName]);
        while ($arMess = $rsMess->Fetch())
        {
            \CEventMessage::Delete((int)$arMess['ID']);
        }

        \CEventType::Delete($eventName);

        return true;
    }

    public function InstallFiles(): bool
    {
        $docRoot = rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/');

        if (is_dir(__DIR__ . '/admin'))
        {
            CopyDirFiles(__DIR__ . '/admin', $docRoot . '/bitrix/admin', true, true);
        }

        if (is_dir(__DIR__ . '/components'))
        {
            CopyDirFiles(__DIR__ . '/components', $docRoot . '/local/components', true, true);
        }

        return true;
    }

    public function UnInstallFiles(): bool
    {
        $docRoot = rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/');

        if (is_dir(__DIR__ . '/admin'))
        {
            DeleteDirFiles(__DIR__ . '/admin', $docRoot . '/bitrix/admin');
        }

        DeleteDirFilesEx('/local/components/bxmax/booking.widget');

        return true;
    }

    public function GetModuleRightList(): array
    {
        return [
            'reference_id' => ['D', 'R', 'W'],
            'reference' => [
                '[D] ' . (Loc::getMessage('BXMAX_BOOKING_RIGHT_D') ?: 'Доступ закрыт'),
                '[R] ' . (Loc::getMessage('BXMAX_BOOKING_RIGHT_R') ?: 'Просмотр расписания и заявок'),
                '[W] ' . (Loc::getMessage('BXMAX_BOOKING_RIGHT_W') ?: 'Полный доступ'),
            ],
        ];
    }
}
