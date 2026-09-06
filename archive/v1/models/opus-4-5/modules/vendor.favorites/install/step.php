<?php

declare(strict_types=1);

if (!check_bitrix_sessid()) {
    return;
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

echo CAdminMessage::ShowNote(
    Loc::getMessage('VENDOR_FAVORITES_INSTALL_SUCCESS') ?: 'Модуль "Избранное" успешно установлен'
);
?>

<form action="<?= $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    <input type="submit" value="<?= Loc::getMessage('MOD_BACK') ?: 'Вернуться в список' ?>">
</form>





