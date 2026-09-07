<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentDescription = [
	'NAME' => Loc::getMessage('BXMAX_BOOKING_COMP_NAME'),
	'DESCRIPTION' => Loc::getMessage('BXMAX_BOOKING_COMP_DESC'),
	'PATH' => [
		'ID' => 'bxmax',
		'NAME' => Loc::getMessage('BXMAX_BOOKING_COMP_GROUP'),
	],
	'ICON' => '/images/icon.gif',
	'CACHE_PATH' => 'Y',
];
