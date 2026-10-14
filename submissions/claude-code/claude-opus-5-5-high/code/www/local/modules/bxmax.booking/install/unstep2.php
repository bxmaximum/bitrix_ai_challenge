<?php

use Bitrix\Main\Localization\Loc;

/** @var CMain $APPLICATION */
if (!check_bitrix_sessid())
{
    return;
}

Loc::loadMessages(__DIR__ . '/index.php');

CAdminMessage::ShowNote(Loc::getMessage('BXMAX_BOOKING_UNINSTALL_DONE'));
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    <input type="submit" value="<?= Loc::getMessage('BXMAX_BOOKING_BACK') ?>">
</form>
