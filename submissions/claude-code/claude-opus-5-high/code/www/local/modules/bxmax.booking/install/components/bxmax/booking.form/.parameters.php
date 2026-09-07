<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentParameters = [
	'PARAMETERS' => [
		'CONSENT_URL' => [
			'PARENT' => 'BASE',
			'NAME' => Loc::getMessage('BXMAX_BOOKING_PARAM_CONSENT_URL'),
			'TYPE' => 'STRING',
			'DEFAULT' => '/privacy/',
		],
		'CACHE_TIME' => ['DEFAULT' => 0],
	],
];
