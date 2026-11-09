<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

$arComponentDescription = [
	'NAME' => Loc::getMessage('BXMAX_BOOKING_FORM_NAME'),
	'DESCRIPTION' => Loc::getMessage('BXMAX_BOOKING_FORM_DESCRIPTION'),
	'CACHE_PATH' => 'Y',
	'PATH' => [
		'ID' => 'bxmax',
		'NAME' => 'BXMax',
	],
];
