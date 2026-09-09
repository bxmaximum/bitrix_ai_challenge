<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

if (CMain::GetGroupRight('bxmax.booking') <= 'D')
{
    return false;
}

return [
    'parent_menu' => 'global_menu_services',
    'section' => 'bxmax_booking',
    'sort' => 250,
    'text' => 'Лак&Точка',
    'title' => 'Онлайн-запись студии «Лак&Точка»',
    'icon' => 'form_menu_icon',
    'page_icon' => 'form_page_icon',
    'items_id' => 'menu_bxmax_booking',
    'items' => [
        [
            'text' => 'Заявки',
            'title' => 'Список заявок на услуги',
            'url' => '/local/modules/bxmax.booking/admin/entries.php?lang=' . LANGUAGE_ID,
        ],
        [
            'text' => 'Расписание',
            'title' => 'Слоты мастеров',
            'url' => '/local/modules/bxmax.booking/admin/slots.php?lang=' . LANGUAGE_ID,
        ],
        [
            'text' => 'Настройки и права',
            'title' => 'Настройки модуля и права групп',
            'url' => '/bitrix/admin/settings.php?lang=' . LANGUAGE_ID . '&mid=bxmax.booking&mid_menu=1',
        ],
    ],
];
