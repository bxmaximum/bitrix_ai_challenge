<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

$arComponentParameters = [
    'PARAMETERS' => [
        'POLICY_URL' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('BXMAX_BOOKING_FORM_PARAM_POLICY_URL'),
            'TYPE' => 'STRING',
            'DEFAULT' => '/policy/',
        ],
        'CACHE_TIME' => ['DEFAULT' => 3600],
    ],
];
