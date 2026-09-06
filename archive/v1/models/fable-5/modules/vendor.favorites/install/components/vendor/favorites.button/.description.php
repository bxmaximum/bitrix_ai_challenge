<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

$arComponentDescription = [
    'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_NAME'),
    'DESCRIPTION' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_DESC'),
    'PATH' => [
        'ID' => 'e-store',
        'CHILD' => [
            'ID' => 'favorites',
            'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_PATH'),
        ],
    ],
    'CACHE_PATH' => 'Y',
    'COMPLEX' => 'N',
];
