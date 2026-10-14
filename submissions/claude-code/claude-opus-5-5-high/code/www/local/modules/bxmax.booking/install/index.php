<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Mail\Internal\EventMessageTable;
use Bitrix\Main\Mail\Internal\EventTypeTable;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SiteTable;

Loc::loadMessages(__FILE__);

class bxmax_booking extends CModule
{
    public const EVENT_NAME = 'BXMAX_BOOKING_NEW';

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

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('BXMAX_BOOKING_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('BXMAX_BOOKING_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = 'BXMax';
        $this->PARTNER_URI = 'https://bxmax.ru';
    }

    public function DoInstall(): bool
    {
        global $APPLICATION;

        if (!$this->canManageModules())
        {
            return false;
        }

        if (!$this->InstallDB())
        {
            return false;
        }

        $this->InstallEvents();
        $this->InstallFiles();
        $this->generateSchedule();

        if ($this->isAdminInterface())
        {
            $APPLICATION->IncludeAdminFile(Loc::getMessage('BXMAX_BOOKING_INSTALL_TITLE'), __DIR__ . '/step.php');
        }

        return true;
    }

    public function DoUninstall(): bool
    {
        global $APPLICATION;

        if (!$this->canManageModules())
        {
            return false;
        }

        $request = Context::getCurrent()->getRequest();
        if ($this->isAdminInterface() && (int)$request->get('step') < 2)
        {
            $APPLICATION->IncludeAdminFile(Loc::getMessage('BXMAX_BOOKING_UNINSTALL_TITLE'), __DIR__ . '/unstep1.php');

            return false;
        }

        $dropTables = !$this->isAdminInterface() || $request->get('savedata') !== 'Y';

        if (!$this->UnInstallDB($dropTables))
        {
            return false;
        }
        $this->UnInstallEvents();
        $this->UnInstallFiles();

        if ($this->isAdminInterface())
        {
            $APPLICATION->IncludeAdminFile(Loc::getMessage('BXMAX_BOOKING_UNINSTALL_TITLE'), __DIR__ . '/unstep2.php');
        }

        return true;
    }

    public function InstallDB(): bool
    {
        global $APPLICATION;

        $result = $this->installMigrations();
        if (!$result->isSuccess())
        {
            $APPLICATION->ThrowException(implode('<br>', $result->getErrorMessages()));

            return false;
        }

        if (!ModuleManager::isModuleInstalled($this->MODULE_ID))
        {
            ModuleManager::registerModule($this->MODULE_ID);
        }

        return true;
    }

    public function UnInstallDB(bool $dropTables = true): bool
    {
        global $APPLICATION;

        $result = $this->uninstallMigrations($dropTables);
        if (!$result->isSuccess())
        {
            $APPLICATION->ThrowException(implode('<br>', $result->getErrorMessages()));

            return false;
        }

        Option::delete($this->MODULE_ID);
        // Снимает агентов модуля и групповые права.
        ModuleManager::unRegisterModule($this->MODULE_ID);

        return true;
    }

    public function InstallEvents(): bool
    {
        $languages = ['ru', 'en'];
        foreach ($languages as $languageId)
        {
            $exists = EventTypeTable::getList([
                'select' => ['ID'],
                'filter' => ['=EVENT_NAME' => self::EVENT_NAME, '=LID' => $languageId],
                'limit' => 1,
            ])->fetch();
            if ($exists)
            {
                continue;
            }

            $messages = Loc::loadLanguageFile(__FILE__, $languageId);
            // D7-API для создания типов почтовых событий нет: используется CEventType.
            (new CEventType())->Add([
                'LID' => $languageId,
                'EVENT_NAME' => self::EVENT_NAME,
                'EVENT_TYPE' => 'email',
                'NAME' => $messages['BXMAX_BOOKING_EVENT_NAME'] ?? self::EVENT_NAME,
                'DESCRIPTION' => $messages['BXMAX_BOOKING_EVENT_DESCRIPTION'] ?? '',
                'SORT' => 100,
            ]);
        }

        $hasMessage = EventMessageTable::getList([
            'select' => ['ID'],
            'filter' => ['=EVENT_NAME' => self::EVENT_NAME],
            'limit' => 1,
        ])->fetch();

        if (!$hasMessage)
        {
            $siteIds = array_column(SiteTable::getList(['select' => ['LID']])->fetchAll(), 'LID');
            // D7-API для создания почтовых шаблонов нет: используется CEventMessage.
            (new CEventMessage())->Add([
                'ACTIVE' => 'Y',
                'EVENT_NAME' => self::EVENT_NAME,
                'LID' => $siteIds ?: ['s1'],
                'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
                'EMAIL_TO' => '#ADMIN_EMAIL#',
                'SUBJECT' => Loc::getMessage('BXMAX_BOOKING_MESSAGE_SUBJECT'),
                'BODY_TYPE' => 'text',
                'MESSAGE' => Loc::getMessage('BXMAX_BOOKING_MESSAGE_BODY'),
            ]);
        }

        return true;
    }

    public function UnInstallEvents(): bool
    {
        $messages = EventMessageTable::getList([
            'select' => ['ID'],
            'filter' => ['=EVENT_NAME' => self::EVENT_NAME],
        ]);
        while ($message = $messages->fetch())
        {
            CEventMessage::Delete((int)$message['ID']);
        }
        CEventType::Delete(self::EVENT_NAME);

        return true;
    }

    public function InstallFiles(): bool
    {
        $documentRoot = Application::getDocumentRoot();

        CopyDirFiles(__DIR__ . '/admin', $documentRoot . '/bitrix/admin', true, true);
        CopyDirFiles(__DIR__ . '/components', $documentRoot . '/local/components', true, true);

        return true;
    }

    public function UnInstallFiles(): bool
    {
        $documentRoot = Application::getDocumentRoot();

        DeleteDirFiles(__DIR__ . '/admin', $documentRoot . '/bitrix/admin');
        DeleteDirFilesEx('/local/components/bxmax/booking.form');

        $vendorDir = new Directory($documentRoot . '/local/components/bxmax');
        if ($vendorDir->isExists() && $vendorDir->getChildren() === [])
        {
            $vendorDir->delete();
        }

        return true;
    }

    public function GetModuleRightList(): array
    {
        return [
            'reference_id' => ['D', 'R', 'W'],
            'reference' => [
                '[D] ' . Loc::getMessage('BXMAX_BOOKING_RIGHT_DENIED'),
                '[R] ' . Loc::getMessage('BXMAX_BOOKING_RIGHT_READ'),
                '[W] ' . Loc::getMessage('BXMAX_BOOKING_RIGHT_WRITE'),
            ],
        ];
    }

    /**
     * Первичное расписание: 14 дней для всех активных мастеров. Дальше его продлевает агент.
     */
    private function generateSchedule(): void
    {
        if (!Loader::includeModule($this->MODULE_ID))
        {
            return;
        }

        ServiceLocator::getInstance()->get(\Bxmax\Booking\Service\ScheduleService::class)->generate();
    }

    private function canManageModules(): bool
    {
        global $USER;

        return PHP_SAPI === 'cli' || (is_object($USER) && $USER->IsAdmin());
    }

    private function isAdminInterface(): bool
    {
        return PHP_SAPI !== 'cli' && defined('ADMIN_SECTION') && ADMIN_SECTION === true;
    }
}
