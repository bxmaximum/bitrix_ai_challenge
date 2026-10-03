<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/** @global CMain $APPLICATION */
if ($APPLICATION->GetGroupRight('bxmax.booking') < 'R')
{
    return false;
}

return [
    'parent_menu' => 'global_menu_services',
    'section' => 'bxmax_booking',
    'sort' => 400,
    'text' => Loc::getMessage('BXMAX_BOOKING_MENU_TITLE'),
    'title' => Loc::getMessage('BXMAX_BOOKING_MENU_TITLE'),
    'icon' => 'form_menu_icon',
    'page_icon' => 'form_page_icon',
    'items_id' => 'menu_bxmax_booking',
    'items' => [
        [
            'text' => Loc::getMessage('BXMAX_BOOKING_MENU_ENTRIES'),
            'title' => Loc::getMessage('BXMAX_BOOKING_MENU_ENTRIES'),
            'url' => 'bxmax_booking_entries.php?lang=' . LANGUAGE_ID,
            'more_url' => [],
        ],
        [
            'text' => Loc::getMessage('BXMAX_BOOKING_MENU_SLOTS'),
            'title' => Loc::getMessage('BXMAX_BOOKING_MENU_SLOTS'),
            'url' => 'bxmax_booking_slots.php?lang=' . LANGUAGE_ID,
            'more_url' => [],
        ],
    ],
];
