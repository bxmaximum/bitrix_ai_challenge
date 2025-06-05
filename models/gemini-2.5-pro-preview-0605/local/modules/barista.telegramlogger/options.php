<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

$moduleId = 'barista.telegramlogger';

Loc::loadMessages(__FILE__);

$request = Application::getInstance()->getContext()->getRequest();

$tabControl = new CAdminTabControl('tabControl', [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('BARISTA_TELEGRAMLOGGER_OPTIONS_TAB_GENERAL'),
        'ICON' => 'main_settings',
        'TITLE' => Loc::getMessage('BARISTA_TELEGRAMLOGGER_OPTIONS_TAB_GENERAL_TITLE'),
    ],
]);

if ($request->isPost() && check_bitrix_sessid()) {
    $options = [
        'is_enabled',
        'bot_token',
        'chat_id',
        'silence_period_min',
    ];
    foreach ($options as $option) {
        $value = $request->getPost($option);
        if ($option === 'is_enabled') {
            $value = ($value === 'Y') ? 'Y' : 'N';
        }
        Option::set($moduleId, $option, $value);
    }
    CAdminMessage::ShowMessage(['MESSAGE' => Loc::getMessage('BARISTA_TELEGRAMLOGGER_OPTIONS_SAVED'), 'TYPE' => 'OK']);
}

$tabControl->Begin();
?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($moduleId) ?>&lang=<?= LANGUAGE_ID ?>">
    <?php
    echo bitrix_sessid_post();
    $tabControl->BeginNextTab();

    $isEnabled = Option::get($moduleId, 'is_enabled', 'N');
    $botToken = Option::get($moduleId, 'bot_token', '');
    $chatId = Option::get($moduleId, 'chat_id', '');
    $silencePeriod = Option::get($moduleId, 'silence_period_min', '5');
    ?>
    <tr>
        <td width="40%"><?= Loc::getMessage('BARISTA_TELEGRAMLOGGER_OPTIONS_IS_ENABLED') ?></td>
        <td width="60%"><input type="checkbox" name="is_enabled" value="Y"<?= ($isEnabled === 'Y') ? ' checked' : '' ?>></td>
    </tr>
    <tr>
        <td width="40%"><?= Loc::getMessage('BARISTA_TELEGRAMLOGGER_OPTIONS_BOT_TOKEN') ?></td>
        <td width="60%"><input type="text" size="50" name="bot_token" value="<?= htmlspecialcharsbx($botToken) ?>"></td>
    </tr>
    <tr>
        <td width="40%"><?= Loc::getMessage('BARISTA_TELEGRAMLOGGER_OPTIONS_CHAT_ID') ?></td>
        <td width="60%"><input type="text" size="50" name="chat_id" value="<?= htmlspecialcharsbx($chatId) ?>"></td>
    </tr>
    <tr>
        <td width="40%"><?= Loc::getMessage('BARISTA_TELEGRAMLOGGER_OPTIONS_SILENCE_PERIOD') ?></td>
        <td width="60%"><input type="number" name="silence_period_min" value="<?= htmlspecialcharsbx($silencePeriod) ?>"></td>
    </tr>

    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="Update" value="<?= Loc::getMessage('MAIN_SAVE') ?>" title="<?= Loc::getMessage('MAIN_OPT_SAVE_TITLE') ?>" class="adm-btn-save">
    <?php $tabControl->End(); ?>
</form> 