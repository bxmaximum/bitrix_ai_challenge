<?php

/**
 * Настройки модуля: e-mail администратора и права доступа групп.
 *
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$moduleId = 'bxmax.booking';
if ($APPLICATION->GetGroupRight($moduleId) < 'R')
{
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

$module_id = $moduleId; // переменная, которую ожидает group_rights.php
$request = Context::getCurrent()->getRequest();

if ($request->isPost() && $request->getPost('Update') !== null && check_bitrix_sessid() && $APPLICATION->GetGroupRight($moduleId) >= 'W')
{
    $email = trim((string)$request->getPost('admin_email'));
    if ($email === '' || check_email($email))
    {
        Option::set($moduleId, 'admin_email', $email);
    }
}

$tabControl = new CAdminTabControl('bxmaxBookingTabs', [
    ['DIV' => 'main', 'TAB' => Loc::getMessage('BXMAX_BOOKING_OPT_TAB'), 'TITLE' => Loc::getMessage('BXMAX_BOOKING_OPT_TAB_TITLE')],
    ['DIV' => 'rights', 'TAB' => Loc::getMessage('MAIN_TAB_RIGHTS'), 'TITLE' => Loc::getMessage('MAIN_TAB_TITLE_RIGHTS')],
]);
?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($moduleId) ?>&amp;lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>
    <?php
    $tabControl->Begin();
    $tabControl->BeginNextTab();
    ?>
    <tr>
        <td width="40%"><label for="bxmax_booking_admin_email"><?= Loc::getMessage('BXMAX_BOOKING_OPT_EMAIL') ?></label></td>
        <td width="60%">
            <input type="text" size="40" id="bxmax_booking_admin_email" name="admin_email"
                   value="<?= htmlspecialcharsbx(Option::get($moduleId, 'admin_email', '')) ?>">
            <br><small><?= Loc::getMessage('BXMAX_BOOKING_OPT_EMAIL_HINT') ?></small>
        </td>
    </tr>
    <?php
    $tabControl->BeginNextTab();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php';
    $tabControl->Buttons();
    ?>
    <input type="submit" name="Update" value="<?= Loc::getMessage('MAIN_SAVE') ?>" class="adm-btn-save">
    <?php $tabControl->End(); ?>
</form>
