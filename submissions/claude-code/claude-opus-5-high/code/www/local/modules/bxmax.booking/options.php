<?php

/**
 * Настройки модуля: адрес, на который уходят письма о новых заявках.
 *
 * @global CMain $APPLICATION
 */

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CMain $APPLICATION */
global $APPLICATION;

Loc::loadMessages(__FILE__);

$module_id = 'bxmax.booking';
$moduleRight = $APPLICATION->GetGroupRight($module_id);

if ($moduleRight < 'S')
{
	$APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

if ($request->isPost() && $request->getPost('Update') !== null && check_bitrix_sessid())
{
	$email = trim((string)$request->getPost('admin_email'));

	if ($email === '' || check_email($email))
	{
		Option::set($module_id, 'admin_email', $email);
		CAdminMessage::ShowNote(Loc::getMessage('BXMAX_BOOKING_OPT_SAVED'));
	}
	else
	{
		CAdminMessage::ShowMessage(Loc::getMessage('BXMAX_BOOKING_OPT_BAD_EMAIL'));
	}
}

$tabControl = new CAdminTabControl('tabControl', [
	[
		'DIV' => 'edit1',
		'TAB' => Loc::getMessage('BXMAX_BOOKING_OPT_TAB'),
		'TITLE' => Loc::getMessage('BXMAX_BOOKING_OPT_TAB_TITLE'),
	],
]);

$tabControl->Begin();
?>
<form method="post" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>?mid=<?= htmlspecialcharsbx($module_id) ?>&amp;lang=<?= htmlspecialcharsbx(LANGUAGE_ID) ?>">
	<?= bitrix_sessid_post() ?>
	<?php $tabControl->BeginNextTab(); ?>
	<tr>
		<td width="40%"><?= Loc::getMessage('BXMAX_BOOKING_OPT_EMAIL') ?></td>
		<td width="60%">
			<input type="email" size="40" name="admin_email"
				   value="<?= htmlspecialcharsbx((string)Option::get($module_id, 'admin_email', '')) ?>">
			<div class="adm-info-message"><?= Loc::getMessage('BXMAX_BOOKING_OPT_EMAIL_HINT') ?></div>
		</td>
	</tr>
	<?php
	$tabControl->Buttons();
	?>
	<input type="submit" name="Update" value="<?= Loc::getMessage('BXMAX_BOOKING_OPT_SAVE') ?>" class="adm-btn-save">
	<?php
	$tabControl->End();
	?>
</form>
