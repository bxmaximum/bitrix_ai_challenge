<?php

declare(strict_types=1);

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Vendor\Favorites\Config\ModuleOptions;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

global $APPLICATION, $USER;

$moduleId = 'vendor.favorites';
Loader::includeModule($moduleId);
Loader::includeModule('iblock');

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

$request = Context::getCurrent()->getRequest();

if ($request->isPost() && check_bitrix_sessid()) {
    Option::set($moduleId, ModuleOptions::OPTION_ENABLED, $request->getPost(ModuleOptions::OPTION_ENABLED) === 'Y' ? 'Y' : 'N');
    Option::set($moduleId, ModuleOptions::OPTION_CATALOG_IBLOCK_ID, (string)max(0, (int)$request->getPost(ModuleOptions::OPTION_CATALOG_IBLOCK_ID)));
    Option::set($moduleId, ModuleOptions::OPTION_COOKIE_TTL, (string)max(3600, (int)$request->getPost(ModuleOptions::OPTION_COOKIE_TTL)));
}

$iblocks = [];
if (Loader::includeModule('iblock')) {
    $rows = IblockTable::getList([
        'select' => ['ID', 'NAME'],
        'filter' => ['=ACTIVE' => 'Y'],
        'order' => ['NAME' => 'ASC'],
        'cache' => ['ttl' => 3600],
    ]);
    while ($row = $rows->fetch()) {
        $iblocks[(int)$row['ID']] = (string)$row['NAME'];
    }
}

$tabControl = new CAdminTabControl('vendorFavoritesOptions', [
    [
        'DIV' => 'settings',
        'TAB' => Loc::getMessage('VENDOR_FAVORITES_OPTIONS_TAB_SETTINGS'),
        'TITLE' => Loc::getMessage('VENDOR_FAVORITES_OPTIONS_TAB_SETTINGS_TITLE'),
    ],
]);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$tabControl->Begin();
?>
<form method="post" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>?mid=<?= htmlspecialcharsbx($moduleId) ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>
    <?php $tabControl->BeginNextTab(); ?>
    <tr>
        <td width="40%">
            <label for="vendor_favorites_enabled">
                <?= htmlspecialcharsbx(Loc::getMessage('VENDOR_FAVORITES_OPTIONS_ENABLED')) ?>
            </label>
        </td>
        <td width="60%">
            <input
                type="checkbox"
                id="vendor_favorites_enabled"
                name="<?= htmlspecialcharsbx(ModuleOptions::OPTION_ENABLED) ?>"
                value="Y"
                <?= ModuleOptions::isEnabled() ? 'checked' : '' ?>
            >
        </td>
    </tr>
    <tr>
        <td>
            <label for="vendor_favorites_catalog_iblock_id">
                <?= htmlspecialcharsbx(Loc::getMessage('VENDOR_FAVORITES_OPTIONS_CATALOG_IBLOCK_ID')) ?>
            </label>
        </td>
        <td>
            <select id="vendor_favorites_catalog_iblock_id" name="<?= htmlspecialcharsbx(ModuleOptions::OPTION_CATALOG_IBLOCK_ID) ?>">
                <option value="0"><?= htmlspecialcharsbx(Loc::getMessage('VENDOR_FAVORITES_OPTIONS_SELECT_IBLOCK')) ?></option>
                <?php foreach ($iblocks as $iblockId => $iblockName): ?>
                    <option value="<?= $iblockId ?>" <?= $iblockId === ModuleOptions::getCatalogIblockId() ? 'selected' : '' ?>>
                        [<?= $iblockId ?>] <?= htmlspecialcharsbx($iblockName) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td>
            <label for="vendor_favorites_cookie_ttl">
                <?= htmlspecialcharsbx(Loc::getMessage('VENDOR_FAVORITES_OPTIONS_COOKIE_TTL')) ?>
            </label>
        </td>
        <td>
            <input
                type="number"
                min="3600"
                id="vendor_favorites_cookie_ttl"
                name="<?= htmlspecialcharsbx(ModuleOptions::OPTION_COOKIE_TTL) ?>"
                value="<?= ModuleOptions::getCookieTtl() ?>"
            >
        </td>
    </tr>
    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="save" value="<?= htmlspecialcharsbx(Loc::getMessage('MAIN_SAVE')) ?>" class="adm-btn-save">
</form>
<?php
$tabControl->End();

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
