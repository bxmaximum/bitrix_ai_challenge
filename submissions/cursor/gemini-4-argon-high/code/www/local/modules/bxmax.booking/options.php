<?php declare(strict_types=1);

/** @var CMain $APPLICATION */
/** @var CUser $USER */

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/options.php');
Loc::loadMessages(__FILE__);

$module_id = 'bxmax.booking';
$moduleId = $module_id;

$moduleRight = $APPLICATION->GetGroupRight($module_id);
if ($moduleRight < 'R')
{
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

Loader::includeModule($module_id);

$options = [
    Loc::getMessage('BXMAX_BOOKING_OPT_SECTION_MAIN'),
    ['admin_email', Loc::getMessage('BXMAX_BOOKING_OPT_ADMIN_EMAIL'), '', ['text', 45]],
    ['schedule_days_ahead', Loc::getMessage('BXMAX_BOOKING_OPT_DAYS_AHEAD'), '14', ['text', 10]],
];

$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('BXMAX_BOOKING_OPT_TAB_SETTINGS'),
        'TITLE' => Loc::getMessage('BXMAX_BOOKING_OPT_TAB_SETTINGS_TITLE'),
    ],
    [
        'DIV' => 'edit2',
        'TAB' => Loc::getMessage('MAIN_TAB_RIGHTS'),
        'TITLE' => Loc::getMessage('MAIN_TAB_TITLE_RIGHTS'),
    ],
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);
$request = Context::getCurrent()->getRequest();

if ($request->isPost() && $moduleRight >= 'W' && check_bitrix_sessid())
{
    $Update = $request->getPost('Update');
    $Apply = $request->getPost('Apply');
    $RestoreDefaults = $request->getPost('RestoreDefaults');

    if ($Update !== null || $Apply !== null || $RestoreDefaults !== null)
    {
        if ($RestoreDefaults !== null)
        {
            \Bitrix\Main\Config\Option::delete($module_id);
        }
        else
        {
            __AdmSettingsSaveOptions($module_id, $options);
        }

        ob_start();
        require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php';
        ob_end_clean();

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
    <?= bitrix_sessid_post() ?>
    <?php
    $tabControl->Begin();
    $tabControl->BeginNextTab();
    __AdmSettingsDrawList($module_id, $options);
    $tabControl->BeginNextTab();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php';
    $tabControl->Buttons(['disabled' => ($moduleRight < 'W')]);
    ?>
    <input type="hidden" name="Update" value="Y">
    <?php $tabControl->End(); ?>
</form>
