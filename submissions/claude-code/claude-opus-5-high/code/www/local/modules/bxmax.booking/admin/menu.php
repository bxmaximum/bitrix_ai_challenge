<?php

use Bitrix\Main\Localization\Loc;

/** @global CMain $APPLICATION */
global $APPLICATION;

Loc::loadMessages(__FILE__);

if ($APPLICATION->GetGroupRight('bxmax.booking') === 'D')
{
	return false;
}

return [
	'parent_menu' => 'global_menu_services',
	'section' => 'bxmax_booking',
	'sort' => 300,
	'module_id' => 'bxmax.booking',
	'text' => Loc::getMessage('BXMAX_BOOKING_MENU_SECTION'),
	'title' => Loc::getMessage('BXMAX_BOOKING_MENU_SECTION_TITLE'),
	'icon' => 'bxmax_booking_menu_icon',
	'page_icon' => 'bxmax_booking_page_icon',
	'items_id' => 'menu_bxmax_booking',
	'items' => [
		[
			'text' => Loc::getMessage('BXMAX_BOOKING_MENU_ENTRIES'),
			'title' => Loc::getMessage('BXMAX_BOOKING_MENU_ENTRIES_TITLE'),
			'url' => 'bxmax_booking_entry_list.php?lang=' . LANGUAGE_ID,
			'more_url' => ['bxmax_booking_entry_list.php'],
			'module_id' => 'bxmax.booking',
		],
		[
			'text' => Loc::getMessage('BXMAX_BOOKING_MENU_SLOTS'),
			'title' => Loc::getMessage('BXMAX_BOOKING_MENU_SLOTS_TITLE'),
			'url' => 'bxmax_booking_slot_list.php?lang=' . LANGUAGE_ID,
			'more_url' => ['bxmax_booking_slot_list.php'],
			'module_id' => 'bxmax.booking',
		],
	],
];
