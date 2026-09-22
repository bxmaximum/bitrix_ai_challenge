<?php declare(strict_types=1);
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
if (\Bitrix\Main\Loader::includeModule('bxmax.booking')) {
 require $_SERVER['DOCUMENT_ROOT'].'/local/modules/bxmax.booking/views/landing.php';
} else { echo '<!doctype html><html lang="ru"><meta charset="utf-8"><title>Студия</title><h1>Сайт студии</h1><p>Онлайн-запись временно недоступна.</p></html>'; }
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php';
