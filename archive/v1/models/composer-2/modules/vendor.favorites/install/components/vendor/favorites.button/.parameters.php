<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'GROUPS' => [],
    'PARAMETERS' => [
        'PRODUCT_ID' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('VENDOR_FAV_BTN_PARAM_PRODUCT_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'SHOW_COUNTER' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('VENDOR_FAV_BTN_PARAM_SHOW_COUNTER'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
        ],
        'BUTTON_SIZE' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('VENDOR_FAV_BTN_PARAM_BUTTON_SIZE'),
            'TYPE' => 'LIST',
            'VALUES' => [
                'small' => 'small',
                'medium' => 'medium',
                'large' => 'large',
            ],
            'DEFAULT' => 'medium',
        ],
        'SYNC_STATE_ON_CLIENT' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('VENDOR_FAV_BTN_PARAM_SYNC_CLIENT'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ],
    ],
];
