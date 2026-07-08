<?php

declare(strict_types=1);

/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @var string $mid
 */

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Vendor\Favorites\Config\ModuleOptions;

Loc::loadMessages(__FILE__);

if (!$USER->CanDoOperation('edit_other_settings'))
{
	$APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

if (!Loader::includeModule('vendor.favorites'))
{
	ShowError('Module vendor.favorites is not installed');

	return;
}

$moduleId = ModuleOptions::MODULE_ID;

$iblockList = [0 => Loc::getMessage('VENDOR_FAVORITES_OPTION_IBLOCK_EMPTY')];
if (Loader::includeModule('iblock'))
{
	$rows = IblockTable::getList([
		'select' => ['ID', 'NAME', 'IBLOCK_TYPE_ID', 'API_CODE'],
		'filter' => ['=ACTIVE' => 'Y'],
		'order' => ['IBLOCK_TYPE_ID' => 'ASC', 'SORT' => 'ASC', 'NAME' => 'ASC'],
		'cache' => ['ttl' => 3600],
	])->fetchAll();

	foreach ($rows as $row)
	{
		$label = '[' . (int)$row['ID'] . '] ' . (string)$row['NAME'];
		if ((string)($row['API_CODE'] ?? '') !== '')
		{
			$label .= ' (API: ' . (string)$row['API_CODE'] . ')';
		}
		$iblockList[(int)$row['ID']] = $label;
	}
}

$options = [
	[
		ModuleOptions::OPTION_ENABLED,
		Loc::getMessage('VENDOR_FAVORITES_OPTION_ENABLED'),
		'Y',
		['checkbox'],
	],
	[
		ModuleOptions::OPTION_IBLOCK_ID,
		Loc::getMessage('VENDOR_FAVORITES_OPTION_IBLOCK_ID'),
		'0',
		['selectbox', $iblockList],
	],
	[
		ModuleOptions::OPTION_COOKIE_TTL,
		Loc::getMessage('VENDOR_FAVORITES_OPTION_COOKIE_TTL'),
		'2592000',
		['text', 12],
	],
];

if (
	$_SERVER['REQUEST_METHOD'] === 'POST'
	&& check_bitrix_sessid()
	&& (isset($_POST['save']) || isset($_POST['apply']))
)
{
	foreach ($options as $option)
	{
		$name = $option[0];
		$type = $option[3][0] ?? 'text';
		$value = $_POST[$name] ?? $option[2];

		if ($type === 'checkbox')
		{
			$value = ($value === 'Y') ? 'Y' : 'N';
		}
		elseif ($name === ModuleOptions::OPTION_IBLOCK_ID)
		{
			$value = (string)max(0, (int)$value);
		}
		elseif ($name === ModuleOptions::OPTION_COOKIE_TTL)
		{
			$value = (string)max(60, (int)$value);
		}
		else
		{
			$value = (string)$value;
		}

		Option::set($moduleId, $name, $value);
	}

	LocalRedirect(
		$APPLICATION->GetCurPage()
		. '?mid=' . urlencode($moduleId)
		. '&lang=' . LANGUAGE_ID
	);
}

$tabs = [
	[
		'DIV' => 'edit1',
		'TAB' => Loc::getMessage('VENDOR_FAVORITES_OPTIONS_TAB'),
		'TITLE' => Loc::getMessage('VENDOR_FAVORITES_OPTIONS_TAB_TITLE'),
	],
];

$tabControl = new CAdminTabControl('tabControl', $tabs);
?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($moduleId) ?>&lang=<?= LANGUAGE_ID ?>">
	<?= bitrix_sessid_post() ?>
	<?php $tabControl->Begin(); ?>
	<?php $tabControl->BeginNextTab(); ?>
	<?php foreach ($options as $option): ?>
		<?php
		$name = $option[0];
		$label = $option[1];
		$default = $option[2];
		$type = $option[3][0] ?? 'text';
		$current = Option::get($moduleId, $name, $default);
		?>
		<tr>
			<td width="40%"><?= htmlspecialcharsbx((string)$label) ?>:</td>
			<td width="60%">
				<?php if ($type === 'checkbox'): ?>
					<input type="hidden" name="<?= htmlspecialcharsbx($name) ?>" value="N">
					<input
						type="checkbox"
						name="<?= htmlspecialcharsbx($name) ?>"
						value="Y"
						<?= $current === 'Y' ? 'checked' : '' ?>
					>
				<?php elseif ($type === 'selectbox'): ?>
					<select name="<?= htmlspecialcharsbx($name) ?>">
						<?php foreach ($option[3][1] as $value => $title): ?>
							<option
								value="<?= htmlspecialcharsbx((string)$value) ?>"
								<?= (string)$current === (string)$value ? 'selected' : '' ?>
							>
								<?= htmlspecialcharsbx((string)$title) ?>
							</option>
						<?php endforeach; ?>
					</select>
				<?php else: ?>
					<input
						type="text"
						name="<?= htmlspecialcharsbx($name) ?>"
						value="<?= htmlspecialcharsbx((string)$current) ?>"
						size="<?= (int)($option[3][1] ?? 40) ?>"
					>
					<?php if ($name === ModuleOptions::OPTION_COOKIE_TTL): ?>
						<br>
						<small><?= Loc::getMessage('VENDOR_FAVORITES_OPTION_COOKIE_TTL_HINT') ?></small>
					<?php endif; ?>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; ?>
	<?php $tabControl->Buttons(['btnApply' => true, 'btnCancel' => false, 'btnSaveAndAdd' => false]); ?>
	<?php $tabControl->End(); ?>
</form>
