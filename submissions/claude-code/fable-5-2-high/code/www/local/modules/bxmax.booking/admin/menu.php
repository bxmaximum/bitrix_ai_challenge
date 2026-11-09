<?php

use Bitrix\Main\Localization\Loc;

/** @global CMain $APPLICATION */

Loc::loadMessages(__FILE__);

// Пункты видны только тем, у кого есть право на модуль (не ниже «просмотр»).
if ($APPLICATION->GetGroupRight('bxmax.booking') < 'R')
{
	return false;
}

return [
	'parent_menu' => 'global_menu_services',
	'section' => 'bxmax_booking',
	'sort' => 150,
	'text' => Loc::getMessage('BXMAX_BOOKING_MENU_ROOT'),
	'title' => Loc::getMessage('BXMAX_BOOKING_MENU_ROOT_TITLE'),
	'icon' => 'calendar_menu_icon',
	'page_icon' => 'calendar_page_icon',
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
