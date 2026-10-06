<?php

/**
 * Пункт админ-меню модуля bxmax.booking.
 *
 * Ядро подключает этот файл само (getLocalPath("modules/<id>/admin/menu.php")),
 * поэтому пункт появляется сразу после установки модуля и исчезает вместе с ним.
 */

global $APPLICATION;

use Bitrix\Main\Loader;

if (!is_object($APPLICATION) || $APPLICATION->GetGroupRight('bxmax.booking') === 'D')
{
	return false;
}

if (!Loader::includeModule('bxmax.booking'))
{
	return false;
}

$lang = defined('LANGUAGE_ID') ? LANGUAGE_ID : 'ru';

$aMenu = [
	'parent_menu' => 'global_menu_services',
	'section' => 'bxmax_booking',
	'sort' => 100,
	'text' => 'Онлайн-запись «Лак&Точка»',
	'title' => 'Заявки и расписание студии маникюра',
	'icon' => 'sender_menu_icon',
	'page_icon' => 'sender_page_icon',
	'items_id' => 'menu_bxmax_booking',
	'items' => [
		[
			'text' => 'Заявки',
			'title' => 'Список заявок на онлайн-запись',
			'url' => 'bxmax_booking_entries.php?lang=' . $lang,
			'more_url' => ['bxmax_booking_entries.php'],
		],
		[
			'text' => 'Слоты расписания',
			'title' => 'Расписание слотов: фильтр по мастеру и дате, закрытие и открытие',
			'url' => 'bxmax_booking_slots.php?lang=' . $lang,
			'more_url' => ['bxmax_booking_slots.php'],
		],
	],
];

return $aMenu;
