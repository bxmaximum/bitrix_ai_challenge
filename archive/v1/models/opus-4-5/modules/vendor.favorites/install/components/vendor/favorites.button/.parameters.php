<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

$arComponentParameters = [
    'GROUPS' => [
        'SETTINGS' => [
            'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_GROUP_SETTINGS') ?: 'Настройки',
            'SORT' => 100,
        ],
        'VISUAL' => [
            'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_GROUP_VISUAL') ?: 'Внешний вид',
            'SORT' => 200,
        ],
    ],
    'PARAMETERS' => [
        'PRODUCT_ID' => [
            'PARENT' => 'SETTINGS',
            'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_PARAM_PRODUCT_ID') ?: 'ID товара',
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'SHOW_COUNTER' => [
            'PARENT' => 'VISUAL',
            'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_PARAM_SHOW_COUNTER') ?: 'Показывать счётчик добавлений',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
        ],
        'BUTTON_SIZE' => [
            'PARENT' => 'VISUAL',
            'NAME' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_PARAM_BUTTON_SIZE') ?: 'Размер кнопки',
            'TYPE' => 'LIST',
            'VALUES' => [
                'small' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_SIZE_SMALL') ?: 'Маленький',
                'medium' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_SIZE_MEDIUM') ?: 'Средний',
                'large' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_SIZE_LARGE') ?: 'Большой',
            ],
            'DEFAULT' => 'medium',
        ],
    ],
];





