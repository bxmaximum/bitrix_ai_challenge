<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentDescription = [
    'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_NAME'),
    'DESCRIPTION' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_DESCRIPTION'),
    'PATH' => [
        'ID' => 'content',
        'CHILD' => [
            'ID' => 'vendor_favorites',
            'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_GROUP'),
        ],
    ],
    'CACHE_PATH' => 'N',
    'COMPLEX' => 'N',
];
