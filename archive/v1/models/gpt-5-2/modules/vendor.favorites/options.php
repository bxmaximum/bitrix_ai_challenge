<?php
declare(strict_types=1);

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;

if (!defined('ADMIN_SECTION') || ADMIN_SECTION !== true)
{
	return;
}

$moduleId = 'vendor.favorites';

if (!Loader::includeModule($moduleId))
{
	return;
}

if (!CurrentUser::get()->isAdmin())
{
	return;
}

$request = Context::getCurrent()->getRequest();

if ($request->isPost() && check_bitrix_sessid())
{
	$enabled = ($request->getPost('enabled') === 'Y') ? 'Y' : 'N';
	$catalogIblockId = max(0, (int) $request->getPost('catalog_iblock_id'));
	$cookieTtl = max(60, (int) $request->getPost('cookie_ttl'));

	Option::set($moduleId, 'enabled', $enabled);
	Option::set($moduleId, 'catalog_iblock_id', (string) $catalogIblockId);
	Option::set($moduleId, 'cookie_ttl', (string) $cookieTtl);
}

$enabled = Option::get($moduleId, 'enabled', 'Y');
$catalogIblockId = (int) Option::get($moduleId, 'catalog_iblock_id', '0');
$cookieTtl = (int) Option::get($moduleId, 'cookie_ttl', (string) (60 * 60 * 24 * 30));

$iblocks = [];
if (Loader::includeModule('iblock'))
{
	$rows = \Bitrix\Iblock\IblockTable::getList([
		'select' => ['ID', 'NAME'],
		'filter' => ['=ACTIVE' => 'Y'],
		'order' => ['ID' => 'ASC'],
	]);

	while ($row = $rows->fetch())
	{
		$iblocks[(int) $row['ID']] = '[' . (int) $row['ID'] . '] ' . (string) $row['NAME'];
	}
}

$aTabs = [
	[
		'DIV' => 'edit1',
		'TAB' => 'Настройки',
		'TITLE' => 'Настройки модуля «Избранное»',
	],
];

global $APPLICATION;

$tabControl = new CAdminTabControl('tabControl', $aTabs);
$tabControl->Begin();
?>

<form method="post" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPageParam()); ?>">
	<?= bitrix_sessid_post(); ?>

	<?php
	$tabControl->BeginNextTab();
	?>

	<tr>
		<td width="40%">Включить модуль</td>
		<td width="60%">
			<input type="checkbox" name="enabled" value="Y" <?= ($enabled === 'Y') ? 'checked' : '' ?>>
		</td>
	</tr>

	<tr>
		<td width="40%">Инфоблок каталога</td>
		<td width="60%">
			<select name="catalog_iblock_id">
				<option value="0">— не выбран —</option>
				<?php foreach ($iblocks as $id => $name): ?>
					<option value="<?= (int) $id ?>" <?= ($catalogIblockId === (int) $id) ? 'selected' : '' ?>>
						<?= htmlspecialcharsbx($name) ?>
					</option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>

	<tr>
		<td width="40%">Время жизни cookie (сек.) для гостей</td>
		<td width="60%">
			<input type="number" name="cookie_ttl" value="<?= (int) $cookieTtl ?>" min="60" step="1">
		</td>
	</tr>

	<?php
	$tabControl->Buttons();
	?>

	<input type="submit" name="Update" value="Сохранить" class="adm-btn-save">
	<input type="reset" name="reset" value="Сбросить">

	<?php
	$tabControl->End();
	?>
</form>


