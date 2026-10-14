<?php

use Bitrix\Main\Localization\Loc;

/** @var CMain $APPLICATION */

if ($APPLICATION->GetGroupRight('bxmax.booking') < 'R')
{
    return false;
}

return [
    'parent_menu' => 'global_menu_services',
    'section' => 'bxmax_booking',
    'sort' => 50,
    'text' => Loc::getMessage('BXMAX_BOOKING_MENU_SECTION'),
    'title' => Loc::getMessage('BXMAX_BOOKING_MENU_SECTION_TITLE'),
    'icon' => 'workflow_menu_icon',
    'page_icon' => 'workflow_page_icon',
    'items_id' => 'menu_bxmax_booking',
    'items' => [
        [
            'text' => Loc::getMessage('BXMAX_BOOKING_MENU_ENTRIES'),
            'title' => Loc::getMessage('BXMAX_BOOKING_MENU_ENTRIES_TITLE'),
            'url' => 'bxmax_booking_entries.php?lang=' . LANGUAGE_ID,
            'more_url' => ['bxmax_booking_entries.php'],
        ],
        [
            'text' => Loc::getMessage('BXMAX_BOOKING_MENU_SLOTS'),
            'title' => Loc::getMessage('BXMAX_BOOKING_MENU_SLOTS_TITLE'),
            'url' => 'bxmax_booking_slots.php?lang=' . LANGUAGE_ID,
            'more_url' => ['bxmax_booking_slots.php'],
        ],
    ],
];
