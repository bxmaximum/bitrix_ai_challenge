<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentDescription = [
	'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_NAME'),
	'DESCRIPTION' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_DESCRIPTION'),
	'PATH' => [
		'ID' => 'vendor',
		'NAME' => 'Vendor',
		'CHILD' => [
			'ID' => 'favorites',
			'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_PATH'),
		],
	],
	'CACHE_PATH' => 'Y',
	'COMPLEX' => 'N',
];
