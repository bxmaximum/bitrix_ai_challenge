<?php

/**
 * Страница настроек модуля vendor.favorites
 * (Настройки → Настройки продукта → Настройки модулей → Избранное).
 *
 * @var CMain $APPLICATION
 * @var string $mid
 */

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true || die();

global $USER;

$moduleId = 'vendor.favorites';

if (!$USER->IsAdmin()) {
    return;
}

Loc::loadMessages(__FILE__);
Loader::includeModule($moduleId);

$request = Context::getCurrent()->getRequest();

// Список инфоблоков для выпадающего списка
$iblocks = [];
if (Loader::includeModule('iblock')) {
    $rows = IblockTable::getList([
        'select' => ['ID', 'NAME', 'CODE'],
        'order' => ['NAME' => 'ASC'],
        'cache' => ['ttl' => 3600],
    ])->fetchAll();

    foreach ($rows as $row) {
        $iblocks[(int)$row['ID']] = sprintf('[%d] %s', (int)$row['ID'], (string)$row['NAME']);
    }
}

// Сохранение
if ($request->isPost() && $request->getPost('save') !== null && check_bitrix_sessid()) {
    Option::set($moduleId, 'enabled', $request->getPost('enabled') === 'Y' ? 'Y' : 'N');

    $iblockId = (int)$request->getPost('iblock_id');
    Option::set($moduleId, 'iblock_id', (string)max(0, $iblockId));

    $cookieTtl = (int)$request->getPost('cookie_ttl_days');
    Option::set($moduleId, 'cookie_ttl_days', (string)max(1, $cookieTtl));

    CAdminMessage::ShowMessage([
        'MESSAGE' => Loc::getMessage('VENDOR_FAVORITES_OPT_SAVED'),
        'TYPE' => 'OK',
    ]);
}

$enabled = Option::get($moduleId, 'enabled', 'Y');
$currentIblockId = (int)Option::get($moduleId, 'iblock_id', '0');
$cookieTtlDays = (int)Option::get($moduleId, 'cookie_ttl_days', '30');

$tabControl = new CAdminTabControl('tabControl', [
    [
        'DIV' => 'settings',
        'TAB' => Loc::getMessage('VENDOR_FAVORITES_OPT_TAB_SETTINGS'),
        'TITLE' => Loc::getMessage('VENDOR_FAVORITES_OPT_TAB_SETTINGS_TITLE'),
    ],
]);

$tabControl->Begin();
?>
<form method="post" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>?mid=<?= htmlspecialcharsbx($mid) ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>
    <?php $tabControl->BeginNextTab(); ?>

    <tr>
        <td width="40%"><?= Loc::getMessage('VENDOR_FAVORITES_OPT_ENABLED') ?></td>
        <td width="60%">
            <input type="checkbox" name="enabled" value="Y" <?= $enabled === 'Y' ? 'checked' : '' ?>>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('VENDOR_FAVORITES_OPT_IBLOCK_ID') ?></td>
        <td>
            <select name="iblock_id">
                <option value="0"><?= Loc::getMessage('VENDOR_FAVORITES_OPT_IBLOCK_NOT_SELECTED') ?></option>
                <?php foreach ($iblocks as $id => $name): ?>
                    <option value="<?= $id ?>" <?= $id === $currentIblockId ? 'selected' : '' ?>>
                        <?= htmlspecialcharsbx($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('VENDOR_FAVORITES_OPT_COOKIE_TTL') ?></td>
        <td>
            <input type="number" name="cookie_ttl_days" min="1" value="<?= $cookieTtlDays ?>" size="10">
        </td>
    </tr>

    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="save" value="<?= Loc::getMessage('VENDOR_FAVORITES_OPT_SAVE') ?>" class="adm-btn-save">
    <?php $tabControl->End(); ?>
</form>
