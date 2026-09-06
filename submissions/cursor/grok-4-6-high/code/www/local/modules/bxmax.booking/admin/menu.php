<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

global $APPLICATION;

$right = $APPLICATION->GetGroupRight('bxmax.booking');
if ($right < 'R') {
    return false;
}

return [
    'parent_menu' => 'global_menu_content',
    'section' => 'bxmax_booking',
    'sort' => 400,
    'text' => Loc::getMessage('BXMAX_BOOKING_MENU_ROOT'),
    'title' => Loc::getMessage('BXMAX_BOOKING_MENU_ROOT'),
    'icon' => 'iblock_menu_icon',
    'page_icon' => 'iblock_page_icon',
    'items_id' => 'menu_bxmax_booking',
    'items' => [
        [
            'text' => Loc::getMessage('BXMAX_BOOKING_MENU_ENTRIES'),
            'url' => 'bxmax_booking_entries.php?lang=' . LANGUAGE_ID,
            'more_url' => ['bxmax_booking_entries.php'],
        ],
        [
            'text' => Loc::getMessage('BXMAX_BOOKING_MENU_SLOTS'),
            'url' => 'bxmax_booking_slots.php?lang=' . LANGUAGE_ID,
            'more_url' => ['bxmax_booking_slots.php'],
        ],
    ],
];
