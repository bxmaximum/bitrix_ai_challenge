<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class bxmax_booking extends CModule
{
    public $MODULE_ID = 'bxmax.booking';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME = 'BXMax';
    public $PARTNER_URI = 'https://bxmax.ru';

    private const EVENT_TYPE = 'BXMAX_BOOKING_NEW';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('BXMAX_BOOKING_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('BXMAX_BOOKING_MODULE_DESCRIPTION');
    }

    public function DoInstall(): void
    {
        global $USER, $APPLICATION;

        if (!$USER->IsAdmin())
        {
            return;
        }

        if ($this->InstallDB())
        {
            $this->InstallEvents();
            $this->InstallFiles();
            $this->InstallData();
        }
    }

    public function DoUninstall(): void
    {
        global $USER;

        if (!$USER->IsAdmin())
        {
            return;
        }

        $this->UnInstallFiles();
        $this->UnInstallEvents();
        $this->UnInstallDB();
    }

    public function InstallDB(): bool
    {
        global $APPLICATION;

        try
        {
            $result = $this->installMigrations();
        }
        catch (\Bitrix\Main\UpdateSystem\Migration\Exception $e)
        {
            $APPLICATION->ThrowException($e->getMessage());

            return false;
        }
        if (!$result->isSuccess())
        {
            $APPLICATION->ThrowException(implode('<br>', $result->getErrorMessages()));

            return false;
        }

        ModuleManager::registerModule($this->MODULE_ID);

        return true;
    }

    public function UnInstallDB(): bool
    {
        global $APPLICATION;

        // зарегистрированный модуль нужен, чтобы миграции удаления нашли классы
        $result = $this->uninstallMigrations(true);
        if (!$result->isSuccess())
        {
            $APPLICATION->ThrowException(implode('<br>', $result->getErrorMessages()));

            return false;
        }

        // агенты и права групп снимает unRegisterModule()
        Option::delete($this->MODULE_ID);
        ModuleManager::unRegisterModule($this->MODULE_ID);

        return true;
    }

    /**
     * Почтовый тип BXMAX_BOOKING_NEW и шаблон письма администратору.
     * Для почтовых событий нет D7-писателя — используются CEventType/CEventMessage.
     */
    public function InstallEvents(): void
    {
        $type = new CEventType();
        $exists = CEventType::GetList(['EVENT_NAME' => self::EVENT_TYPE, 'LID' => 'ru'])->Fetch();
        if (!$exists)
        {
            $type->Add([
                'LID' => 'ru',
                'EVENT_NAME' => self::EVENT_TYPE,
                'NAME' => Loc::getMessage('BXMAX_BOOKING_EVENT_NAME'),
                'DESCRIPTION' => Loc::getMessage('BXMAX_BOOKING_EVENT_DESCRIPTION'),
                'EVENT_TYPE' => 'email',
            ]);
        }

        if (CEventMessage::GetList('id', 'asc', ['TYPE_ID' => self::EVENT_TYPE])->Fetch())
        {
            return;
        }

        $sites = [];
        $rsSites = CSite::GetList();
        while ($site = $rsSites->Fetch())
        {
            $sites[] = $site['LID'];
        }

        (new CEventMessage())->Add([
            'ACTIVE' => 'Y',
            'EVENT_NAME' => self::EVENT_TYPE,
            'LID' => $sites,
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => '#ADMIN_EMAIL#',
            'SUBJECT' => Loc::getMessage('BXMAX_BOOKING_MAIL_SUBJECT'),
            'BODY_TYPE' => 'text',
            'MESSAGE' => Loc::getMessage('BXMAX_BOOKING_MAIL_BODY'),
        ]);
    }

    public function UnInstallEvents(): void
    {
        $rsMessages = CEventMessage::GetList('id', 'asc', ['TYPE_ID' => self::EVENT_TYPE]);
        while ($message = $rsMessages->Fetch())
        {
            CEventMessage::Delete((int)$message['ID']);
        }
        CEventType::Delete(self::EVENT_TYPE);
    }

    public function InstallFiles(): void
    {
        $root = Application::getDocumentRoot();
        CopyDirFiles(__DIR__ . '/admin', $root . '/bitrix/admin', true, true);
        CopyDirFiles(__DIR__ . '/components', $root . '/local/components', true, true);
    }

    public function UnInstallFiles(): void
    {
        $root = Application::getDocumentRoot();
        DeleteDirFiles(__DIR__ . '/admin', $root . '/bitrix/admin');
        DeleteDirFilesEx('/local/components/bxmax/booking.form');
        // пустой каталог вендора убираем, только если в нём больше ничего нет
        $vendorDir = $root . '/local/components/bxmax';
        if (is_dir($vendorDir) && count(scandir($vendorDir)) === 2)
        {
            @rmdir($vendorDir);
        }
    }

    /**
     * Первичное расписание: слоты на ближайшие 14 дней. Если мастеров ещё нет, расписание
     * создаст миграция контента или ежедневный агент.
     */
    public function InstallData(): void
    {
        if (Loader::includeModule($this->MODULE_ID))
        {
            ServiceLocator::getInstance()->get(\Bxmax\Booking\Service\ScheduleGenerator::class)->generate();
        }
    }
}
