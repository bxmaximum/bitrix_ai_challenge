<?php
declare(strict_types=1);

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

/** @var CMain $APPLICATION */
/** @var CUser $USER */
/** @var string $mid */

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/options.php');
Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin()) {
    return;
}

$moduleId = 'vendor.favorites';
$request = Context::getCurrent()->getRequest();
$mid = (string) ($request->get('mid') ?: $moduleId);
$backUrl = (string) $request->get('back_url_settings');

$tabs = [
    [
        'DIV' => 'vendor_favorites_settings',
        'TAB' => Loc::getMessage('VENDOR_FAVORITES_OPTIONS_TAB_NAME'),
        'TITLE' => Loc::getMessage('VENDOR_FAVORITES_OPTIONS_TAB_TITLE'),
    ],
];

$iblockOptions = [
    '0' => (string) Loc::getMessage('VENDOR_FAVORITES_OPTION_IBLOCK_EMPTY'),
];

if (Loader::includeModule('iblock')) {
    $iblocks = IblockTable::getList([
        'select' => ['ID', 'NAME', 'API_CODE'],
        'order' => ['NAME' => 'ASC'],
    ])->fetchAll();

    foreach ($iblocks as $iblock) {
        $iblockOptions[(string) $iblock['ID']] = sprintf(
            '[%d] %s%s',
            (int) $iblock['ID'],
            (string) $iblock['NAME'],
            !empty($iblock['API_CODE']) ? ' (' . $iblock['API_CODE'] . ')' : ''
        );
    }
}

$currentAction = null;
if ($request->getPost('save') !== null) {
    $currentAction = 'save';
} elseif ($request->getPost('apply') !== null) {
    $currentAction = 'apply';
}

$tabControl = new CAdminTabControl('vendorFavoritesTabs', $tabs);

if ($request->isPost() && $currentAction !== null && check_bitrix_sessid()) {
    Option::set($moduleId, 'enabled', $request->getPost('enabled') === 'Y' ? 'Y' : 'N');

    $iblockId = max(0, (int) $request->getPost('iblock_id'));
    Option::set($moduleId, 'iblock_id', (string) $iblockId);

    Option::set(
        $moduleId,
        'cookie_lifetime_days',
        (string) max(1, (int) $request->getPost('cookie_lifetime_days'))
    );

    if ($currentAction === 'save' && $backUrl !== '') {
        LocalRedirect($backUrl);
    }

    LocalRedirect(
        $APPLICATION->GetCurPage()
        . '?lang=' . urlencode(LANGUAGE_ID)
        . '&mid=' . urlencode($mid)
        . '&mid_menu=1'
        . ($backUrl !== '' ? '&back_url_settings=' . urlencode($backUrl) : '')
        . '&' . $tabControl->ActiveTabParam()
    );
}

$enabled = Option::get($moduleId, 'enabled', 'Y');
$selectedIblockId = Option::get($moduleId, 'iblock_id', '0');
$cookieLifetimeDays = Option::get($moduleId, 'cookie_lifetime_days', '30');
?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($mid) ?>&lang=<?= LANGUAGE_ID ?>&mid_menu=1<?= $backUrl !== '' ? '&back_url_settings=' . urlencode($backUrl) : '' ?>">
    <?php
    $tabControl->begin();
    $tabControl->beginNextTab();
    ?>
    <tr>
        <td width="40%"><?= htmlspecialcharsbx((string) Loc::getMessage('VENDOR_FAVORITES_OPTION_ENABLED')) ?></td>
        <td width="60%">
            <input type="checkbox" name="enabled" value="Y" <?= $enabled === 'Y' ? 'checked' : '' ?>>
        </td>
    </tr>
    <tr>
        <td><?= htmlspecialcharsbx((string) Loc::getMessage('VENDOR_FAVORITES_OPTION_IBLOCK_ID')) ?></td>
        <td>
            <select name="iblock_id">
                <?php foreach ($iblockOptions as $value => $label): ?>
                    <option value="<?= htmlspecialcharsbx($value) ?>" <?= $selectedIblockId === $value ? 'selected' : '' ?>>
                        <?= htmlspecialcharsbx($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><?= htmlspecialcharsbx((string) Loc::getMessage('VENDOR_FAVORITES_OPTION_COOKIE_LIFETIME_DAYS')) ?></td>
        <td>
            <input type="number" min="1" name="cookie_lifetime_days" value="<?= htmlspecialcharsbx($cookieLifetimeDays) ?>">
        </td>
    </tr>
    <?php
    $tabControl->buttons();
    ?>
    <input type="submit" name="save" value="<?= htmlspecialcharsbx((string) GetMessage('MAIN_SAVE')) ?>" class="adm-btn-save">
    <input type="submit" name="apply" value="<?= htmlspecialcharsbx((string) GetMessage('MAIN_OPT_APPLY')) ?>">
    <?php if ($backUrl !== ''): ?>
        <input
            type="button"
            name="cancel"
            value="<?= htmlspecialcharsbx((string) GetMessage('MAIN_OPT_CANCEL')) ?>"
            title="<?= htmlspecialcharsbx((string) GetMessage('MAIN_OPT_CANCEL_TITLE')) ?>"
            onclick="window.location='<?= htmlspecialcharsbx(CUtil::addslashes($backUrl)) ?>'"
        >
        <input type="hidden" name="back_url_settings" value="<?= htmlspecialcharsbx($backUrl) ?>">
    <?php endif; ?>
    <?= bitrix_sessid_post() ?>
    <?php
    $tabControl->end();
    ?>
</form>
