<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

$arComponentParameters = [
    'PARAMETERS' => [
        'ANCHOR_ID' => [
            'NAME' => 'Якорь секции (id)',
            'TYPE' => 'STRING',
            'DEFAULT' => 'booking',
        ],
        'TITLE' => [
            'NAME' => 'Заголовок',
            'TYPE' => 'STRING',
            'DEFAULT' => 'Выберите время — остальное мы подготовим',
        ],
        'WEEKS_AHEAD' => [
            'NAME' => 'Сколько недель вперёд можно листать',
            'TYPE' => 'STRING',
            'DEFAULT' => '2',
        ],
        'AJAX_URL' => [
            'NAME' => 'URL ajax-точки входа',
            'TYPE' => 'STRING',
            'DEFAULT' => '/bitrix/services/main/ajax.php',
        ],
    ],
];
