<?php
/**
 * Настройки модуля (подключается из bitrix/admin/settings.php).
 *
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

use Bitrix\Main\Context;

$module_id = 'vendor.favorites';

$POST_RIGHT = CMain::GetUserRight($module_id);

if ($POST_RIGHT < 'R') {
    return;
}

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/options.php');
IncludeModuleLangFile(__FILE__);

$arAllOptions = [
    GetMessage('VENDOR_FAVORITES_TAB_SETTINGS'),
    ['catalog_iblock_id', GetMessage('VENDOR_FAVORITES_OPTION_CATALOG_IBLOCK'), ['text', 10]],
    ['guest_cookie_ttl', GetMessage('VENDOR_FAVORITES_OPTION_COOKIE_TTL'), ['text', 10]],
    ['list_cache_ttl', GetMessage('VENDOR_FAVORITES_OPTION_LIST_CACHE_TTL'), ['text', 10]],
    ['module_enabled', GetMessage('VENDOR_FAVORITES_OPTION_ENABLED'), ['checkbox', 'Y']],
];

$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => GetMessage('MAIN_TAB_SET'),
        'ICON' => '',
        'TITLE' => GetMessage('MAIN_TAB_TITLE_SET'),
    ],
    [
        'DIV' => 'edit2',
        'TAB' => GetMessage('MAIN_TAB_RIGHTS'),
        'ICON' => '',
        'TITLE' => GetMessage('MAIN_TAB_TITLE_RIGHTS'),
    ],
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);

$request = Context::getCurrent()->getRequest();

if (
    $request->isPost()
    && (
        (string) $request->get('Update') !== ''
        || (string) $request->get('Apply') !== ''
        || (string) $request->get('RestoreDefaults') !== ''
    )
    && $POST_RIGHT === 'W'
    && check_bitrix_sessid()
) {
    if ((string) $request->get('RestoreDefaults') !== '') {
        COption::RemoveOption($module_id);
    } else {
        foreach ($arAllOptions as $arOption) {
            if (!is_array($arOption)) {
                continue;
            }
            $name = $arOption[0];
            $val = $_POST[$name] ?? null;
            $type = $arOption[2][0];
            if ($type === 'checkbox' && $val !== 'Y') {
                $val = 'N';
            }
            if ($val === null) {
                $val = '';
            }
            if (in_array($name, ['catalog_iblock_id', 'guest_cookie_ttl', 'list_cache_ttl'], true)) {
                $val = (string) max(0, (int) $val);
                if ($name !== 'catalog_iblock_id') {
                    $val = (string) max(60, (int) $val);
                }
            }
            COption::SetOptionString($module_id, $name, is_scalar($val) ? (string) $val : '');
        }
    }

    if ((string) $request->get('Update') !== '' || (string) $request->get('Apply') !== '') {
        ob_start();
        require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php';
        ob_end_clean();
    }

    $backUrl = (string) $request->get('back_url_settings');
    if ($backUrl !== '') {
        if ((string) $request->get('Apply') !== '' || (string) $request->get('RestoreDefaults') !== '') {
            LocalRedirect(
                $APPLICATION->GetCurPage()
                . '?mid=' . urlencode($module_id)
                . '&lang=' . urlencode(LANGUAGE_ID)
                . '&back_url_settings=' . urlencode($backUrl)
                . '&' . $tabControl->ActiveTabParam()
            );
        } else {
            LocalRedirect($backUrl);
        }
    } else {
        LocalRedirect(
            $APPLICATION->GetCurPage()
            . '?mid=' . urlencode($module_id)
            . '&lang=' . urlencode(LANGUAGE_ID)
            . '&' . $tabControl->ActiveTabParam()
        );
    }
}

?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&amp;lang=<?= LANGUAGE_ID ?>">
<?php
$tabControl->Begin();
$tabControl->BeginNextTab();

foreach ($arAllOptions as $Option) {
    if (!is_array($Option)) {
        ?>
        <tr class="heading">
            <td colspan="2"><?= htmlspecialcharsbx((string) $Option) ?></td>
        </tr>
        <?php
        continue;
    }

    $type = $Option[2];
    $val = COption::GetOptionString($module_id, $Option[0], '');
    ?>
    <tr>
        <td width="40%" class="<?= $type[0] === 'textarea' ? 'adm-detail-valign-top' : '' ?>">
            <label for="<?= htmlspecialcharsbx($Option[0]) ?>"><?= $Option[1] ?></label>
        </td>
        <td width="60%">
            <?php
            if ($type[0] === 'checkbox') {
                ?>
                <input type="checkbox" name="<?= htmlspecialcharsbx($Option[0]) ?>"
                    id="<?= htmlspecialcharsbx($Option[0]) ?>" value="Y" <?= $val === 'Y' ? 'checked' : '' ?>>
                <?php
            } elseif ($type[0] === 'text') {
                ?>
                <input type="text" size="<?= (int) $type[1] ?>" maxlength="255"
                    value="<?= htmlspecialcharsbx($val) ?>" name="<?= htmlspecialcharsbx($Option[0]) ?>">
                <?php
            }
            ?>
        </td>
    </tr>
    <?php
}

$tabControl->BeginNextTab();
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php';
$tabControl->Buttons();
?>
    <input <?= $POST_RIGHT < 'W' ? 'disabled' : '' ?> type="submit" name="Update"
        value="<?= GetMessage('MAIN_SAVE') ?>" title="<?= GetMessage('MAIN_OPT_SAVE_TITLE') ?>" class="adm-btn-save">
    <input <?= $POST_RIGHT < 'W' ? 'disabled' : '' ?> type="submit" name="Apply"
        value="<?= GetMessage('MAIN_OPT_APPLY') ?>" title="<?= GetMessage('MAIN_OPT_APPLY_TITLE') ?>">
    <?php if ((string) ($_REQUEST['back_url_settings'] ?? '') !== '') { ?>
        <input <?= $POST_RIGHT < 'W' ? 'disabled' : '' ?> type="button" name="Cancel"
            value="<?= GetMessage('MAIN_OPT_CANCEL') ?>" title="<?= GetMessage('MAIN_OPT_CANCEL_TITLE') ?>"
            onclick="window.location='<?= htmlspecialcharsbx(CUtil::addslashes($_REQUEST['back_url_settings'])) ?>'">
        <input type="hidden" name="back_url_settings" value="<?= htmlspecialcharsbx((string) $_REQUEST['back_url_settings']) ?>">
    <?php } ?>
    <input <?= $POST_RIGHT < 'W' ? 'disabled' : '' ?> type="submit" name="RestoreDefaults"
        title="<?= GetMessage('MAIN_HINT_RESTORE_DEFAULTS') ?>"
        onclick="return confirm('<?= CUtil::JSEscape(GetMessage('MAIN_HINT_RESTORE_DEFAULTS_WARNING')) ?>')"
        value="<?= GetMessage('MAIN_RESTORE_DEFAULTS') ?>">
    <?= bitrix_sessid_post() ?>
<?php $tabControl->End(); ?>
</form>
