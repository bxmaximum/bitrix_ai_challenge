<?php

declare(strict_types=1);

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

$moduleId = 'vendor.favorites';

if (!Loader::includeModule($moduleId) || !Loader::includeModule('iblock')) {
    return;
}

Loc::loadMessages(__FILE__);

$request = \Bitrix\Main\Context::getCurrent()->getRequest();

if ($request->isPost() && check_bitrix_sessid() && $request->getPost('save')) {
    Option::set($moduleId, 'enabled', $request->getPost('enabled') === 'Y' ? 'Y' : 'N');
    Option::set($moduleId, 'iblock_id', (string) max(0, (int) $request->getPost('iblock_id')));
    Option::set($moduleId, 'cookie_ttl', (string) max(3600, (int) $request->getPost('cookie_ttl')));

    CAdminMessage::ShowMessage([
        'MESSAGE' => Loc::getMessage('VENDOR_FAVORITES_OPT_SAVED'),
        'TYPE' => 'OK',
    ]);
}

$enabled = Option::get($moduleId, 'enabled', 'Y');
$iblockId = (int) Option::get($moduleId, 'iblock_id', '0');
$cookieTtl = (int) Option::get($moduleId, 'cookie_ttl', '2592000');

$iblockOptions = ['' => Loc::getMessage('VENDOR_FAVORITES_OPT_IBLOCK_EMPTY')];
$iblockResult = IblockTable::getList([
    'select' => ['ID', 'NAME'],
    'order' => ['NAME' => 'ASC'],
]);

while ($iblock = $iblockResult->fetch()) {
    $iblockOptions[(string) $iblock['ID']] = '[' . $iblock['ID'] . '] ' . $iblock['NAME'];
}

$tabControl = new CAdminTabControl('vendor_favorites_options', [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('VENDOR_FAVORITES_OPTIONS_TAB'),
        'TITLE' => Loc::getMessage('VENDOR_FAVORITES_OPTIONS_TITLE'),
    ],
]);
?>
<form method="post" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>?mid=<?= urlencode($moduleId) ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>
    <?php $tabControl->Begin(); ?>
    <?php $tabControl->BeginNextTab(); ?>
    <tr>
        <td width="40%"><?= Loc::getMessage('VENDOR_FAVORITES_OPT_ENABLED') ?>:</td>
        <td width="60%">
            <input type="checkbox" name="enabled" value="Y"<?= $enabled === 'Y' ? ' checked' : '' ?>>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('VENDOR_FAVORITES_OPT_IBLOCK') ?>:</td>
        <td>
            <select name="iblock_id">
                <?php foreach ($iblockOptions as $value => $label): ?>
                    <option value="<?= htmlspecialcharsbx($value) ?>"<?= (string) $iblockId === $value ? ' selected' : '' ?>>
                        <?= htmlspecialcharsbx($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('VENDOR_FAVORITES_OPT_COOKIE_TTL') ?>:</td>
        <td>
            <input type="number" name="cookie_ttl" value="<?= $cookieTtl ?>" min="3600" step="1" size="10">
        </td>
    </tr>
    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="save" value="<?= Loc::getMessage('VENDOR_FAVORITES_OPT_SAVE') ?>" class="adm-btn-save">
    <input type="submit" name="reset" value="<?= Loc::getMessage('VENDOR_FAVORITES_OPT_RESET') ?>">
    <?php $tabControl->End(); ?>
</form>
