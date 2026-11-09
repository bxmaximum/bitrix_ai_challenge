<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

$arComponentParameters = [
	'PARAMETERS' => [
		'POLICY_URL' => [
			'PARENT' => 'BASE',
			'NAME' => Loc::getMessage('BXMAX_BOOKING_FORM_PARAM_POLICY_URL'),
			'TYPE' => 'STRING',
			'DEFAULT' => '/privacy/',
		],
		'ADDRESS' => [
			'PARENT' => 'BASE',
			'NAME' => Loc::getMessage('BXMAX_BOOKING_FORM_PARAM_ADDRESS'),
			'TYPE' => 'STRING',
			'DEFAULT' => '',
		],
		'PHONE' => [
			'PARENT' => 'BASE',
			'NAME' => Loc::getMessage('BXMAX_BOOKING_FORM_PARAM_PHONE'),
			'TYPE' => 'STRING',
			'DEFAULT' => '',
		],
	],
];
