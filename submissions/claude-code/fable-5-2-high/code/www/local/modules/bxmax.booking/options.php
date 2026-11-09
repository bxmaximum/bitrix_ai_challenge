<?php

use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;

/**
 * @global CMain $APPLICATION
 * @var string $mid
 */

$module_id = 'bxmax.booking';

Loc::loadMessages(__FILE__);
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/options.php');

$right = $APPLICATION->GetGroupRight($module_id);
if ($right < 'R')
{
	$APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

$options = [
	Loc::getMessage('BXMAX_BOOKING_OPT_NOTIFY'),
	['admin_email', Loc::getMessage('BXMAX_BOOKING_OPT_ADMIN_EMAIL'), '', ['text', 40]],
	['note' => Loc::getMessage('BXMAX_BOOKING_OPT_ADMIN_EMAIL_NOTE')],
];

$tabs = [
	['DIV' => 'edit1', 'TAB' => Loc::getMessage('MAIN_TAB_SET'), 'TITLE' => Loc::getMessage('MAIN_TAB_TITLE_SET')],
	['DIV' => 'edit2', 'TAB' => Loc::getMessage('MAIN_TAB_RIGHTS'), 'TITLE' => Loc::getMessage('MAIN_TAB_TITLE_RIGHTS')],
];
$tabControl = new CAdminTabControl('tabControl', $tabs);

$request = Context::getCurrent()->getRequest();
$isSave = $request->isPost()
	&& $request->getPost('Update') !== null
	&& $right >= 'W'
	&& check_bitrix_sessid();

if ($isSave)
{
	$email = trim((string)$request->getPost('admin_email'));
	if ($email !== '' && !check_email($email))
	{
		CAdminMessage::ShowMessage(Loc::getMessage('BXMAX_BOOKING_OPT_ADMIN_EMAIL_ERROR'));
	}
	else
	{
		__AdmSettingsSaveOptions($module_id, $options);
	}
}

$tabControl->Begin();
?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&amp;lang=<?= LANGUAGE_ID ?>">
	<?= bitrix_sessid_post() ?>
	<?php
	$tabControl->BeginNextTab();
	__AdmSettingsDrawList($module_id, $options);

	$tabControl->BeginNextTab();
	// Штатная вкладка «Доступ»: уровни из bxmax_booking::GetModuleRightList().
	// Файл сам сохраняет права по POST-полю Update после проверки сессии и права W.
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php';

	$tabControl->Buttons();
	?>
	<input type="submit" name="Update" value="<?= Loc::getMessage('MAIN_SAVE') ?>" class="adm-btn-save"<?= $right < 'W' ? ' disabled' : '' ?>>
	<?php $tabControl->End(); ?>
</form>
