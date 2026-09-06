<?php

declare(strict_types=1);

/**
 * @var CMain $APPLICATION
 */

use Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid())
{
	return;
}

Loc::loadMessages(__FILE__);
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
	<?= bitrix_sessid_post() ?>
	<input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
	<input type="hidden" name="id" value="vendor.favorites">
	<input type="hidden" name="uninstall" value="Y">
	<input type="hidden" name="step" value="2">
	<p><?= Loc::getMessage('VENDOR_FAVORITES_UNINSTALL_SAVE_DATA') ?></p>
	<p>
		<label>
			<input type="checkbox" name="savedata" value="Y" checked>
			<?= Loc::getMessage('VENDOR_FAVORITES_UNINSTALL_SAVE_DATA_LABEL') ?>
		</label>
	</p>
	<input type="submit" name="inst" value="<?= Loc::getMessage('MOD_UNINST_DEL') ?>">
</form>
