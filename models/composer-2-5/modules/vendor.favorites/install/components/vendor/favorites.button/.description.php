<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentDescription = [
    'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_NAME'),
    'DESCRIPTION' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_DESCRIPTION'),
    'ICON' => '/images/icon.gif',
    'PATH' => [
        'ID' => 'e-store',
        'CHILD' => [
            'ID' => 'catalog',
            'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_PATH_CATALOG'),
        ],
    ],
    'CACHE_PATH' => 'N',
];
