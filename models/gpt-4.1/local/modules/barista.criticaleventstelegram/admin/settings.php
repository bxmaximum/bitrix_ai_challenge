<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

$module_id = 'barista.criticaleventstelegram';
Loader::includeModule($module_id);

$tabControl = new CAdminTabControl('tabControl', [
    ['DIV' => 'edit1', 'TAB' => Loc::getMessage('BARISTA_CET_TAB_SETTINGS'), 'ICON' => 'settings', 'TITLE' => Loc::getMessage('BARISTA_CET_TAB_TITLE')],
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()) {
    Option::set($module_id, 'enabled', $_POST['enabled'] === 'Y' ? 'Y' : 'N');
    Option::set($module_id, 'telegram_token', trim($_POST['telegram_token']));
    Option::set($module_id, 'telegram_chat_id', trim($_POST['telegram_chat_id']));
    Option::set($module_id, 'critical_levels', implode(',', $_POST['critical_levels'] ?? []));
    Option::set($module_id, 'rabbitmq_host', trim($_POST['rabbitmq_host']));
    Option::set($module_id, 'rabbitmq_port', trim($_POST['rabbitmq_port']));
    Option::set($module_id, 'rabbitmq_user', trim($_POST['rabbitmq_user']));
    Option::set($module_id, 'rabbitmq_pass', trim($_POST['rabbitmq_pass']));
    Option::set($module_id, 'silence_mode', $_POST['silence_mode'] === 'Y' ? 'Y' : 'N');
    Option::set($module_id, 'lang', $_POST['lang'] === 'EN' ? 'EN' : 'RU');
}

$enabled = Option::get($module_id, 'enabled', 'N');
$telegram_token = Option::get($module_id, 'telegram_token', '');
$telegram_chat_id = Option::get($module_id, 'telegram_chat_id', '');
$critical_levels = explode(',', Option::get($module_id, 'critical_levels', 'SECURITY,ERROR,FATAL'));
$rabbitmq_host = Option::get($module_id, 'rabbitmq_host', 'localhost');
$rabbitmq_port = Option::get($module_id, 'rabbitmq_port', '5672');
$rabbitmq_user = Option::get($module_id, 'rabbitmq_user', 'guest');
$rabbitmq_pass = Option::get($module_id, 'rabbitmq_pass', 'guest');
$silence_mode = Option::get($module_id, 'silence_mode', 'N');
$lang = Option::get($module_id, 'lang', 'RU');

$tabControl->Begin();
?><form method="post" action="">
<?=bitrix_sessid_post()?>
<?php $tabControl->BeginNextTab(); ?>
<tr>
    <td width="40%"><?=Loc::getMessage('BARISTA_CET_ENABLED')?></td>
    <td><input type="checkbox" name="enabled" value="Y" <?=$enabled === 'Y' ? 'checked' : ''?>></td>
</tr>
<tr>
    <td><?=Loc::getMessage('BARISTA_CET_TELEGRAM_TOKEN')?></td>
    <td><input type="text" name="telegram_token" value="<?=htmlspecialcharsbx($telegram_token)?>" size="50"></td>
</tr>
<tr>
    <td><?=Loc::getMessage('BARISTA_CET_TELEGRAM_CHAT_ID')?></td>
    <td><input type="text" name="telegram_chat_id" value="<?=htmlspecialcharsbx($telegram_chat_id)?>" size="30"></td>
</tr>
<tr>
    <td><?=Loc::getMessage('BARISTA_CET_CRITICAL_LEVELS')?></td>
    <td>
        <select name="critical_levels[]" multiple size="4">
            <option value="SECURITY" <?=in_array('SECURITY', $critical_levels) ? 'selected' : ''?>>SECURITY</option>
            <option value="ERROR" <?=in_array('ERROR', $critical_levels) ? 'selected' : ''?>>ERROR</option>
            <option value="FATAL" <?=in_array('FATAL', $critical_levels) ? 'selected' : ''?>>FATAL</option>
            <option value="WARNING" <?=in_array('WARNING', $critical_levels) ? 'selected' : ''?>>WARNING</option>
        </select>
    </td>
</tr>
<tr>
    <td><?=Loc::getMessage('BARISTA_CET_RABBITMQ_HOST')?></td>
    <td><input type="text" name="rabbitmq_host" value="<?=htmlspecialcharsbx($rabbitmq_host)?>"></td>
</tr>
<tr>
    <td><?=Loc::getMessage('BARISTA_CET_RABBITMQ_PORT')?></td>
    <td><input type="text" name="rabbitmq_port" value="<?=htmlspecialcharsbx($rabbitmq_port)?>"></td>
</tr>
<tr>
    <td><?=Loc::getMessage('BARISTA_CET_RABBITMQ_USER')?></td>
    <td><input type="text" name="rabbitmq_user" value="<?=htmlspecialcharsbx($rabbitmq_user)?>"></td>
</tr>
<tr>
    <td><?=Loc::getMessage('BARISTA_CET_RABBITMQ_PASS')?></td>
    <td><input type="password" name="rabbitmq_pass" value="<?=htmlspecialcharsbx($rabbitmq_pass)?>"></td>
</tr>
<tr>
    <td><?=Loc::getMessage('BARISTA_CET_SILENCE_MODE')?></td>
    <td><input type="checkbox" name="silence_mode" value="Y" <?=$silence_mode === 'Y' ? 'checked' : ''?>></td>
</tr>
<tr>
    <td><?=Loc::getMessage('BARISTA_CET_LANG')?></td>
    <td>
        <select name="lang">
            <option value="RU" <?=$lang === 'RU' ? 'selected' : ''?>>Русский</option>
            <option value="EN" <?=$lang === 'EN' ? 'selected' : ''?>>English</option>
        </select>
    </td>
</tr>
<?php $tabControl->Buttons(['btnSave' => true]); ?>
</form>
<?php $tabControl->End(); ?> 