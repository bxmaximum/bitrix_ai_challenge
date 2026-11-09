<?php

use Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid())
{
	return;
}

/** @var CMain $APPLICATION */
// Фразы MOD_* — из языкового файла главного модуля, он уже загружен страницей установки модулей.

if ($exception = $APPLICATION->GetException())
{
	CAdminMessage::ShowMessage([
		'TYPE' => 'ERROR',
		'MESSAGE' => Loc::getMessage('MOD_INST_ERR'),
		'DETAILS' => $exception->GetString(),
		'HTML' => true,
	]);
}
else
{
	CAdminMessage::ShowNote(Loc::getMessage('MOD_INST_OK'));
}
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
	<input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
	<input type="submit" value="<?= Loc::getMessage('MOD_BACK') ?>">
</form>
