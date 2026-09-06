<?php
declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'GROUPS' => [],
    'PARAMETERS' => [
        'PRODUCT_ID' => [
            'PARENT' => 'BASE',
            'NAME' => 'ID товара',
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'SHOW_COUNTER' => [
            'PARENT' => 'VISUAL',
            'NAME' => 'Показывать счётчик',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
        ],
        'BUTTON_SIZE' => [
            'PARENT' => 'VISUAL',
            'NAME' => 'Размер кнопки',
            'TYPE' => 'LIST',
            'VALUES' => [
                'small' => 'small',
                'medium' => 'medium',
                'large' => 'large',
            ],
            'DEFAULT' => 'medium',
        ],
    ],
];
