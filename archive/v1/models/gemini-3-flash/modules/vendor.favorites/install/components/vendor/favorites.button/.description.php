<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
    'NAME' => 'Кнопка избранного',
    'DESCRIPTION' => 'Кнопка для добавления товара в избранное',
    'PATH' => [
        'ID' => 'vendor',
        'NAME' => 'Vendor',
        'CHILD' => [
            'ID' => 'favorites',
            'NAME' => 'Избранное',
        ],
    ],
];

