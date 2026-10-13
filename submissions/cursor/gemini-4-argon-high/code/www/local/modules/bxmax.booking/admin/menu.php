<?php declare(strict_types=1);

/** @var CMain $APPLICATION */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if ($APPLICATION->GetGroupRight('bxmax.booking') < 'R')
{
    return false;
}

if (!Loader::includeModule('bxmax.booking'))
{
    return false;
}

return [
    'parent_menu' => 'global_menu_services',
    'section' => 'bxmax_booking',
    'sort' => 150,
    'text' => Loc::getMessage('BXMAX_BOOKING_MENU_ROOT') ?: 'Лак&Точка: Запись',
    'title' => Loc::getMessage('BXMAX_BOOKING_MENU_ROOT_TITLE') ?: 'Управление онлайн-записью',
    'icon' => 'form_menu_icon',
    'page_icon' => 'form_page_icon',
    'items_id' => 'menu_bxmax_booking',
    'items' => [
        [
            'text' => Loc::getMessage('BXMAX_BOOKING_MENU_ENTRIES') ?: 'Заявки на запись',
            'title' => Loc::getMessage('BXMAX_BOOKING_MENU_ENTRIES_TITLE') ?: 'Список заявок клиентов',
            'url' => 'bxmax_booking_entries.php?lang=' . LANGUAGE_ID,
            'more_url' => ['bxmax_booking_entries.php'],
        ],
        [
            'text' => Loc::getMessage('BXMAX_BOOKING_MENU_SLOTS') ?: 'Расписание слотов',
            'title' => Loc::getMessage('BXMAX_BOOKING_MENU_SLOTS_TITLE') ?: 'Управление слотами мастеров',
            'url' => 'bxmax_booking_slots.php?lang=' . LANGUAGE_ID,
            'more_url' => ['bxmax_booking_slots.php'],
        ],
    ],
];
