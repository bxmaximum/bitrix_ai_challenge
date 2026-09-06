<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentParameters = [
	'PARAMETERS' => [
		'PRODUCT_ID' => [
			'PARENT' => 'BASE',
			'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_PARAM_PRODUCT_ID'),
			'TYPE' => 'STRING',
			'DEFAULT' => '',
		],
		'SHOW_COUNTER' => [
			'PARENT' => 'BASE',
			'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_PARAM_SHOW_COUNTER'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'N',
		],
		'BUTTON_SIZE' => [
			'PARENT' => 'VISUAL',
			'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_PARAM_BUTTON_SIZE'),
			'TYPE' => 'LIST',
			'VALUES' => [
				'small' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_SIZE_SMALL'),
				'medium' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_SIZE_MEDIUM'),
				'large' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_SIZE_LARGE'),
			],
			'DEFAULT' => 'medium',
		],
	],
];
