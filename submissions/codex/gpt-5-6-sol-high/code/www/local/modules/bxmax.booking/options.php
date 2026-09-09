<?php

declare(strict_types=1);

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}
if (!Loader::includeModule('bxmax.booking'))
{
    return;
}

$moduleId = 'bxmax.booking';
$right = CMain::GetGroupRight($moduleId);
if ($right < 'R')
{
    $APPLICATION->AuthForm('Доступ запрещён');
}
$request = Context::getCurrent()->getRequest();
if ($request->isPost() && check_bitrix_sessid() && $right >= 'W' && $request->getPost('Update') !== null)
{
    Option::set($moduleId, 'notification_email', trim((string)$request->getPost('notification_email')));
}
$notificationEmail = trim(Option::get($moduleId, 'notification_email', ''));
if ($notificationEmail === '')
{
    $notificationEmail = Option::get('main', 'email_from');
}

$tabs = [
    ['DIV' => 'edit1', 'TAB' => 'Настройки', 'TITLE' => 'Уведомления'],
    ['DIV' => 'edit2', 'TAB' => 'Права доступа', 'TITLE' => 'Права групп пользователей'],
];
$tabControl = new CAdminTabControl('tabControl', $tabs);
$tabControl->Begin();
?>
<form method="post" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>?mid=<?= urlencode($moduleId) ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>
    <?php $tabControl->BeginNextTab(); ?>
    <tr>
        <td width="40%"><label for="notification_email">E-mail администратора:</label></td>
        <td><input id="notification_email" type="email" size="40" name="notification_email" value="<?= htmlspecialcharsbx($notificationEmail) ?>"></td>
    </tr>
    <?php $tabControl->BeginNextTab(); ?>
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php'; ?>
    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="Update" value="Сохранить" class="adm-btn-save"<?= $right < 'W' ? ' disabled' : '' ?>>
    <?php $tabControl->End(); ?>
</form>
