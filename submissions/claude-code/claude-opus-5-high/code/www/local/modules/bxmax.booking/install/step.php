<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>
<p><?= Loc::getMessage('BXMAX_BOOKING_INSTALL_DONE') ?></p>
<form action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>">
	<input type="hidden" name="lang" value="<?= htmlspecialcharsbx(LANGUAGE_ID) ?>">
	<input type="submit" name="" value="<?= Loc::getMessage('BXMAX_BOOKING_BACK') ?>">
</form>
