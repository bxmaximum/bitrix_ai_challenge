<?php declare(strict_types=1);
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php';
if (!\Bitrix\Main\Loader::includeModule('bxmax.booking') || $APPLICATION->GetGroupRight('bxmax.booking')<'R') $APPLICATION->AuthForm('Нет доступа');
$view=\Bxmax\Booking\Admin\Lists::prepare('entries');
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php';
\Bxmax\Booking\Admin\Lists::display($view);
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php';
