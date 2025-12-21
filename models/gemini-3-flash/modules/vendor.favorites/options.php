<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

$module_id = 'vendor.favorites';

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/options.php');
Loc::loadMessages(__FILE__);

if ($APPLICATION->GetGroupRight($module_id) < 'S') {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

Loader::includeModule($module_id);

$request = HttpApplication::getInstance()->getContext()->getRequest();

if (!Loader::includeModule('iblock')) {
    \CAdminMessage::ShowMessage(Loc::getMessage('VENDOR_FAVORITES_IBLOCK_NOT_INSTALLED'));
}

$iblocks = [0 => '---'];
if (Loader::includeModule('iblock')) {
    $res = \Bitrix\Iblock\IblockTable::getList([
        'select' => ['ID', 'NAME'],
        'filter' => ['=ACTIVE' => 'Y'],
    ]);
    while ($row = $res->fetch()) {
        $iblocks[$row['ID']] = '[' . $row['ID'] . '] ' . $row['NAME'];
    }
}

$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('VENDOR_FAVORITES_TAB_SETTINGS'),
        'ICON' => '',
        'TITLE' => Loc::getMessage('VENDOR_FAVORITES_TAB_SETTINGS_TITLE'),
    ],
];

$arAllOptions = [
    'edit1' => [
        ['active', Loc::getMessage('VENDOR_FAVORITES_OPTION_ACTIVE'), 'Y', ['checkbox']],
        ['catalog_iblock_id', Loc::getMessage('VENDOR_FAVORITES_OPTION_IBLOCK'), '', ['selectbox', $iblocks]],
        ['cookie_lifetime', Loc::getMessage('VENDOR_FAVORITES_OPTION_COOKIE_LIFETIME'), '2592000', ['text', 10]],
    ],
];

if ($request->isPost() && $request['Update'] && check_bitrix_sessid()) {
    foreach ($arAllOptions as $aTab) {
        foreach ($aTab as $arOption) {
            if (!is_array($arOption)) {
                continue;
            }

            $name = $arOption[0];
            $val = $request->getPost($name);

            if ($arOption[3][0] === 'checkbox' && $val !== 'Y') {
                $val = 'N';
            }

            Option::set($module_id, $name, $val);
        }
    }
}

$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>

<? $tabControl->Begin(); ?>
<form method="post" 
      action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&amp;lang=<?= LANGUAGE_ID ?>">
    <?
    foreach ($arAllOptions as $aTab) {
        $tabControl->BeginNextTab();
        __AdmSettingsDrawList($module_id, $aTab);
    }
    $tabControl->Buttons();
    ?>
    <input type="submit" name="Update" value="<?= Loc::getMessage('MAIN_SAVE') ?>" title="<?= Loc::getMessage('MAIN_OPT_SAVE_TITLE') ?>" class="adm-btn-save">
    <input type="submit" name="RestoreDefaults" value="<?= Loc::getMessage('MAIN_RESTORE_DEFAULTS') ?>" title="<?= Loc::getMessage('MAIN_HINT_RESTORE_DEFAULTS') ?>">
    <?= bitrix_sessid_post(); ?>
</form>
<? $tabControl->End(); ?>

