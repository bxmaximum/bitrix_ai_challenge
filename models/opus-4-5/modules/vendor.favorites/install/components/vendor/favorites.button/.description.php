<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

$arComponentDescription = [
    'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_NAME') ?: 'Кнопка "Избранное"',
    'DESCRIPTION' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_DESC') ?: 'Кнопка добавления/удаления товара из избранного',
    'ICON' => '/images/icon.gif',
    'SORT' => 10,
    'CACHE_PATH' => 'Y',
    'PATH' => [
        'ID' => 'vendor',
        'NAME' => 'Vendor',
        'CHILD' => [
            'ID' => 'favorites',
            'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_PATH_NAME') ?: 'Избранное',
        ],
    ],
];





