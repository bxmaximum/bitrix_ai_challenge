<?php
use Bitrix\Main\Loader;
Loader::includeModule('barista.criticaleventstelegram');

$module = new barista_criticaleventstelegram();
if ($_REQUEST['install'] === 'Y') {
    $module->DoInstall();
} elseif ($_REQUEST['uninstall'] === 'Y') {
    $module->DoUninstall();
} 