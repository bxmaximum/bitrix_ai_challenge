<?php

use Bitrix\Main\Localization\Loc;

/** @var CMain $APPLICATION */
Loc::loadMessages(__DIR__ . '/index.php');
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    <input type="hidden" name="id" value="bxmax.booking">
    <input type="hidden" name="uninstall" value="Y">
    <input type="hidden" name="step" value="2">
    <?php CAdminMessage::ShowMessage(Loc::getMessage('BXMAX_BOOKING_UNINSTALL_WARN')); ?>
    <p>
        <input type="checkbox" name="savedata" id="savedata" value="Y">
        <label for="savedata"><?= Loc::getMessage('BXMAX_BOOKING_UNINSTALL_SAVE') ?></label>
    </p>
    <input type="submit" name="inst" value="<?= Loc::getMessage('BXMAX_BOOKING_UNINSTALL_DEL') ?>">
</form>
