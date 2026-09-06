<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

$arComponentParameters = [
    'GROUPS' => [
        'SETTINGS' => [
            'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_GROUP_SETTINGS'),
            'SORT' => 100,
        ],
    ],
    'PARAMETERS' => [
        'PRODUCT_ID' => [
            'PARENT' => 'SETTINGS',
            'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_PARAM_PRODUCT_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '={$_REQUEST["ELEMENT_ID"]}',
        ],
        'SHOW_COUNTER' => [
            'PARENT' => 'SETTINGS',
            'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_PARAM_SHOW_COUNTER'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
        ],
        'BUTTON_SIZE' => [
            'PARENT' => 'SETTINGS',
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
