<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;

/**
 * @global CMain $APPLICATION
 */

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/options.php');
Loc::loadMessages(__FILE__);

$module_id = 'bxmax.booking';
$right = $APPLICATION->GetGroupRight($module_id);
if ($right < 'R')
{
    return;
}

$request = Context::getCurrent()->getRequest();

$tabs = [
    ['DIV' => 'edit1', 'TAB' => Loc::getMessage('MAIN_TAB_SET'), 'TITLE' => Loc::getMessage('BXMAX_BOOKING_OPT_TAB_TITLE')],
    ['DIV' => 'edit2', 'TAB' => Loc::getMessage('MAIN_TAB_RIGHTS'), 'TITLE' => Loc::getMessage('MAIN_TAB_TITLE_RIGHTS')],
];
$tabControl = new CAdminTabControl('tabControl', $tabs);

$errors = [];
if ($request->isPost() && $right >= 'W' && check_bitrix_sessid() && $request->getPost('Update') !== null)
{
    $email = trim((string)$request->getPost('admin_email'));
    if ($email !== '' && !check_email($email))
    {
        $errors[] = Loc::getMessage('BXMAX_BOOKING_OPT_EMAIL_INVALID');
    }
    else
    {
        Option::set($module_id, 'admin_email', $email);
    }
}

if ($errors)
{
    CAdminMessage::ShowMessage(implode('<br>', $errors));
}
?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&amp;lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>
    <?php
    $tabControl->Begin();
    $tabControl->BeginNextTab();
    ?>
    <tr>
        <td width="40%"><label for="admin_email"><?= Loc::getMessage('BXMAX_BOOKING_OPT_ADMIN_EMAIL') ?></label></td>
        <td width="60%">
            <input type="email" size="40" id="admin_email" name="admin_email"
                   value="<?= htmlspecialcharsbx(Option::get($module_id, 'admin_email', '')) ?>"
                   placeholder="<?= htmlspecialcharsbx(Option::get('main', 'email_from', '')) ?>">
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <?= BeginNote() . Loc::getMessage('BXMAX_BOOKING_OPT_NOTE') . EndNote() ?>
        </td>
    </tr>
    <?php
    $tabControl->BeginNextTab();
    // Штатная вкладка «Доступ»: сама сохраняет групповые права модуля при POST с Update.
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php';
    $tabControl->Buttons();
    ?>
    <input type="submit" name="Update" value="<?= Loc::getMessage('MAIN_SAVE') ?>" class="adm-btn-save" <?= $right < 'W' ? 'disabled' : '' ?>>
    <?php $tabControl->End(); ?>
</form>
