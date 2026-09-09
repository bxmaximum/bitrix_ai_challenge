<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bxmax\Booking\Application\Service\ScheduleService;
use Bxmax\Booking\Model\BookingEntryTable;
use Bxmax\Booking\Model\SlotTable;

Loc::loadMessages(__FILE__);

final class bxmax_booking extends CModule
{
    public $MODULE_ID = 'bxmax.booking';
    public $MODULE_VERSION = '';
    public $MODULE_VERSION_DATE = '';
    public $MODULE_NAME = '';
    public $MODULE_DESCRIPTION = '';
    public $PARTNER_NAME = '';
    public $PARTNER_URI = 'https://bxmax.ru/';
    public $MODULE_GROUP_RIGHTS = 'Y';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION = (string)($arModuleVersion['VERSION'] ?? '');
        $this->MODULE_VERSION_DATE = (string)($arModuleVersion['VERSION_DATE'] ?? '');
        $this->MODULE_NAME = (string)Loc::getMessage('BXMAX_BOOKING_MODULE_NAME');
        $this->MODULE_DESCRIPTION = (string)Loc::getMessage('BXMAX_BOOKING_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = (string)Loc::getMessage('BXMAX_BOOKING_PARTNER_NAME');
    }

    public function DoInstall(): void
    {
        global $USER, $APPLICATION;
        if (!$USER?->IsAdmin())
        {
            $APPLICATION->ThrowException('Access denied');
            return;
        }

        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();
    }

    public function DoUninstall(): void
    {
        global $USER, $APPLICATION;
        if (!$USER?->IsAdmin())
        {
            $APPLICATION->ThrowException('Access denied');
            return;
        }

        $this->UnInstallEvents();
        $this->UnInstallDB();
        $this->UnInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function InstallDB(): bool
    {
        if (!Loader::includeModule($this->MODULE_ID))
        {
            throw new RuntimeException('Unable to load bxmax.booking');
        }

        $connection = Application::getConnection();
        if (!$connection->isTableExists(SlotTable::getTableName()))
        {
            SlotTable::getEntity()->createDbTable();
            $connection->createIndex(
                SlotTable::getTableName(),
                'UX_BXMAX_SLOT_MASTER_START',
                ['MASTER_ID', 'STARTS_AT'],
                null,
                Connection::INDEX_UNIQUE,
            );
            $connection->createIndex(SlotTable::getTableName(), 'IX_BXMAX_SLOT_DATE', ['STARTS_AT']);
        }
        if (!$connection->isTableExists(BookingEntryTable::getTableName()))
        {
            BookingEntryTable::getEntity()->createDbTable();
            $connection->createIndex(
                BookingEntryTable::getTableName(),
                'UX_BXMAX_ENTRY_SLOT',
                ['SLOT_ID'],
                null,
                Connection::INDEX_UNIQUE,
            );
        }

        $this->installContent();
        ServiceLocator::getInstance()->get(ScheduleService::class)->provision(14);
        return true;
    }

    public function UnInstallDB(): bool
    {
        Loader::includeModule($this->MODULE_ID);
        $connection = Application::getConnection();
        if ($connection->isTableExists(BookingEntryTable::getTableName()))
        {
            $connection->dropTable(BookingEntryTable::getTableName());
        }
        if ($connection->isTableExists(SlotTable::getTableName()))
        {
            $connection->dropTable(SlotTable::getTableName());
        }
        $this->uninstallContent();
        Option::delete($this->MODULE_ID);
        return true;
    }

    public function InstallEvents(): bool
    {
        $eventType = new CEventType();
        $eventType->Add([
            'LID' => 'ru',
            'EVENT_NAME' => 'BXMAX_BOOKING_NEW',
            'NAME' => 'Новая запись в студию «Лак&Точка»',
            'DESCRIPTION' => "#BOOKING_ID# — номер записи\n#NAME# — имя\n#PHONE# — телефон\n#SERVICE# — услуга\n#MASTER# — мастер\n#STARTS_AT# — время\n#EMAIL_TO# — получатель",
        ]);

        $siteIds = [];
        $sites = CSite::GetList($by = 'sort', $order = 'asc', ['ACTIVE' => 'Y']);
        while ($site = $sites->Fetch())
        {
            $siteIds[] = $site['LID'];
        }
        if ($siteIds === [])
        {
            $siteIds = ['s1'];
        }

        (new CEventMessage())->Add([
            'ACTIVE' => 'Y',
            'EVENT_NAME' => 'BXMAX_BOOKING_NEW',
            'LID' => $siteIds,
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => '#EMAIL_TO#',
            'SUBJECT' => 'Новая запись №#BOOKING_ID#: #SERVICE#',
            'BODY_TYPE' => 'text',
            'MESSAGE' => "Новая запись в студию «Лак&Точка».\n\nКлиент: #NAME#\nТелефон: #PHONE#\nУслуга: #SERVICE#\nМастер: #MASTER#\nДата и время: #STARTS_AT#\nНомер записи: #BOOKING_ID#",
        ]);
        return true;
    }

    public function UnInstallEvents(): bool
    {
        $messages = CEventMessage::GetList($by = 'id', $order = 'asc', ['TYPE_ID' => 'BXMAX_BOOKING_NEW']);
        while ($message = $messages->Fetch())
        {
            CEventMessage::Delete((int)$message['ID']);
        }
        CEventType::Delete('BXMAX_BOOKING_NEW');
        return true;
    }

    public function InstallFiles(): bool
    {
        CopyDirFiles(
            __DIR__ . '/components',
            $_SERVER['DOCUMENT_ROOT'] . '/local/components',
            true,
            true,
        );
        return true;
    }

    public function UnInstallFiles(): bool
    {
        DeleteDirFilesEx('/local/components/bxmax/booking');
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

    private function installContent(): void
    {
        if (!Loader::includeModule('iblock'))
        {
            throw new RuntimeException('The iblock module is required');
        }

        $typeId = 'bxmax_booking';
        if (!CIBlockType::GetByID($typeId)->Fetch())
        {
            $type = new CIBlockType();
            if (!$type->Add([
                'ID' => $typeId,
                'SECTIONS' => 'N',
                'IN_RSS' => 'N',
                'SORT' => 100,
                'LANG' => [
                    'ru' => ['NAME' => 'Лак&Точка'],
                    'en' => ['NAME' => 'Lak & Tochka'],
                ],
            ]))
            {
                throw new RuntimeException((string)$type->LAST_ERROR);
            }
            Option::set($this->MODULE_ID, 'created_iblock_type', 'Y');
        }

        $servicesId = $this->createIblock($typeId, 'Услуги', 'services', 'BxmaxServices');
        $mastersId = $this->createIblock($typeId, 'Мастера', 'masters', 'BxmaxMasters');
        Option::set($this->MODULE_ID, 'services_iblock_id', (string)$servicesId);
        Option::set($this->MODULE_ID, 'masters_iblock_id', (string)$mastersId);

        $this->createProperty($servicesId, 'Цена', 'PRICE', 'N');
        $this->createProperty($servicesId, 'Длительность', 'DURATION', 'S');
        $this->createProperty($mastersId, 'Специализация', 'SPECIALTY', 'S');
        $this->createProperty($mastersId, 'Опыт', 'EXPERIENCE', 'S');

        $services = [
            ['Маникюр без покрытия', 'manicure', 'Аккуратная форма, бережная обработка и уход с маслом.', 1700, '60 минут', 'work-1.jpg'],
            ['Маникюр с гель-лаком', 'gel-polish', 'Стойкое покрытие, идеальный блик и палитра из 180 оттенков.', 2900, '120 минут', 'work-2.jpg'],
            ['Укрепление ногтей', 'strengthening', 'Выравнивание и укрепление без лишней толщины.', 900, '30 минут', 'work-3.jpg'],
            ['Дизайн: минимализм', 'minimal-design', 'Тонкие линии, точки, френч или деликатный акцент.', 500, '20 минут', 'work-4.jpg'],
            ['SMART-педикюр', 'smart-pedicure', 'Комфортная обработка стоп и пальчиков, гладкость надолго.', 3100, '90 минут', 'work-5.jpg'],
            ['Педикюр с покрытием', 'pedicure-polish', 'Полный педикюр и стойкое цветное покрытие.', 3900, '120 минут', 'work-2.jpg'],
            ['Снятие покрытия', 'removal', 'Мягкое снятие без пропилов и травмирования пластины.', 600, '30 минут', 'work-1.jpg'],
            ['SPA-уход для рук', 'spa-hands', 'Скраб, питательная маска и расслабляющий массаж.', 1100, '40 минут', 'hero.jpg'],
        ];
        foreach ($services as $index => [$name, $code, $description, $price, $duration, $image])
        {
            $this->createElement($servicesId, $name, $code, $description, $image, [
                'PRICE' => $price,
                'DURATION' => $duration,
            ], 100 + $index * 10);
        }

        $masters = [
            ['Анна Лебедева', 'anna-lebedeva', 'Старший мастер, сложная архитектура и френч', '8 лет', 'master-1.jpg', 'Спокойно объясняет каждый этап и умеет подобрать форму, которая визуально удлиняет пальцы.'],
            ['Мира Волкова', 'mira-volkova', 'Nail-стилист, минималистичные дизайны', '6 лет', 'master-2.jpg', 'Любит чистые оттенки, тонкие линии и рисунки, которые не надоедают через неделю.'],
            ['София Орлова', 'sofia-orlova', 'Мастер маникюра и укрепления', '5 лет', 'master-3.jpg', 'Работает особенно деликатно с тонкими ногтями и возвращает им здоровый вид.'],
            ['Лиза Романова', 'liza-romanova', 'Мастер SMART-педикюра', '7 лет', 'master-4.jpg', 'Создаёт ощущение лёгкости и тот самый безупречный педикюр без спешки.'],
        ];
        foreach ($masters as $index => [$name, $code, $specialty, $experience, $image, $description])
        {
            $this->createElement($mastersId, $name, $code, $description, $image, [
                'SPECIALTY' => $specialty,
                'EXPERIENCE' => $experience,
            ], 100 + $index * 10);
        }
    }

    private function createIblock(string $typeId, string $name, string $code, string $apiCode): int
    {
        $existing = CIBlock::GetList([], ['TYPE' => $typeId, '=CODE' => $code])->Fetch();
        if ($existing)
        {
            return (int)$existing['ID'];
        }

        $siteIds = [];
        $sites = CSite::GetList($by = 'sort', $order = 'asc', ['ACTIVE' => 'Y']);
        while ($site = $sites->Fetch())
        {
            $siteIds[] = $site['LID'];
        }
        if ($siteIds === [])
        {
            $siteIds = ['s1'];
        }

        $iblock = new CIBlock();
        $id = $iblock->Add([
            'IBLOCK_TYPE_ID' => $typeId,
            'NAME' => $name,
            'CODE' => $code,
            'API_CODE' => $apiCode,
            'ACTIVE' => 'Y',
            'LID' => $siteIds,
            'VERSION' => 2,
            'GROUP_ID' => [2 => 'R'],
        ]);
        if (!$id)
        {
            throw new RuntimeException((string)$iblock->LAST_ERROR);
        }

        return (int)$id;
    }

    private function createProperty(int $iblockId, string $name, string $code, string $type): void
    {
        if (CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $code])->Fetch())
        {
            return;
        }
        (new CIBlockProperty())->Add([
            'IBLOCK_ID' => $iblockId,
            'NAME' => $name,
            'CODE' => $code,
            'PROPERTY_TYPE' => $type,
            'MULTIPLE' => 'N',
        ]);
    }

    private function createElement(
        int $iblockId,
        string $name,
        string $code,
        string $description,
        string $image,
        array $properties,
        int $sort,
    ): void {
        if (CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, '=CODE' => $code], false, ['nTopCount' => 1], ['ID'])->Fetch())
        {
            return;
        }
        $element = new CIBlockElement();
        $id = $element->Add([
            'IBLOCK_ID' => $iblockId,
            'NAME' => $name,
            'CODE' => $code,
            'ACTIVE' => 'Y',
            'SORT' => $sort,
            'PREVIEW_TEXT' => $description,
            'PREVIEW_TEXT_TYPE' => 'text',
            'PREVIEW_PICTURE' => CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'] . '/local/assets/lak-tochka/images/' . $image),
            'PROPERTY_VALUES' => $properties,
        ]);
        if (!$id)
        {
            throw new RuntimeException((string)$element->LAST_ERROR);
        }
    }

    private function uninstallContent(): void
    {
        if (!Loader::includeModule('iblock'))
        {
            return;
        }
        foreach (['services_iblock_id', 'masters_iblock_id'] as $option)
        {
            $id = (int)Option::get($this->MODULE_ID, $option, 0);
            if ($id > 0)
            {
                CIBlock::Delete($id);
            }
        }
        if (Option::get($this->MODULE_ID, 'created_iblock_type', 'N') === 'Y')
        {
            CIBlockType::Delete('bxmax_booking');
        }
    }
}
