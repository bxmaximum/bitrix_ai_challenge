<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Barista\TelegramNotifier\Services\ConfigService;
use Barista\TelegramNotifier\Services\SecurityService;
use Barista\TelegramNotifier\Services\TelegramService;
use Barista\TelegramNotifier\Services\QueueService;
use Barista\TelegramNotifier\Services\AntiSpamService;

Loc::loadMessages(__FILE__);

$module_id = 'barista.telegramnotifier';

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

if (!Loader::includeModule($module_id)) {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
    echo CAdminMessage::ShowMessage('Модуль не установлен');
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
    die();
}

$APPLICATION->SetTitle('Настройки Telegram уведомлений');

$errors = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()) {
    
    if (isset($_POST['save'])) {
        $settings = [
            'enabled' => $_POST['enabled'] === 'Y',
            'bot_token' => trim($_POST['bot_token']),
            'chat_ids' => array_filter(array_map('trim', explode(',', $_POST['chat_ids']))),
            'critical_event_types' => array_filter(array_map('trim', explode(',', $_POST['critical_event_types']))),
            'critical_keywords' => array_filter(array_map('trim', explode(',', $_POST['critical_keywords']))),
            'antispam_interval' => (int)$_POST['antispam_interval'],
            'silence_mode' => $_POST['silence_mode'] === 'Y',
            'silence_duration' => (int)$_POST['silence_duration'],
            'max_retries' => (int)$_POST['max_retries'],
            'queue_limit' => (int)$_POST['queue_limit'],
            'logging_enabled' => $_POST['logging_enabled'] === 'Y',
            'log_level' => $_POST['log_level'],
        ];
        
        $errors = ConfigService::validateSettings($settings);
        
        if (empty($errors)) {
            if ($settings['enabled']) {
                ConfigService::enable();
            } else {
                ConfigService::disable();
            }
            
            ConfigService::setBotToken($settings['bot_token']);
            ConfigService::setChatIds($settings['chat_ids']);
            ConfigService::setCriticalEventTypes($settings['critical_event_types']);
            ConfigService::setCriticalKeywords($settings['critical_keywords']);
            ConfigService::setAntiSpamInterval($settings['antispam_interval']);
            ConfigService::setSilenceMode($settings['silence_mode']);
            ConfigService::setSilenceDuration($settings['silence_duration']);
            ConfigService::setMaxRetries($settings['max_retries']);
            ConfigService::setQueueProcessingLimit($settings['queue_limit']);
            ConfigService::setLoggingEnabled($settings['logging_enabled']);
            ConfigService::setLogLevel($settings['log_level']);
            
            $message = 'Настройки сохранены';
        }
    }
    
    if (isset($_POST['test_connection'])) {
        $botToken = trim($_POST['bot_token']);
        $chatIds = array_filter(array_map('trim', explode(',', $_POST['chat_ids'])));
        
        if (empty($botToken)) {
            $errors[] = 'Введите токен бота';
        } elseif (empty($chatIds)) {
            $errors[] = 'Введите Chat ID';
        } else {
            $telegram = new TelegramService($botToken);
            $testResult = $telegram->testConnection();
            
            if ($testResult) {
                $testMessage = "🧪 Тестовое сообщение от модуля Telegram уведомлений\n\nВремя: " . date('d.m.Y H:i:s');
                $results = $telegram->sendToMultipleChats($chatIds, $testMessage);
                
                $successCount = 0;
                $errorMessages = [];
                
                foreach ($results as $chatId => $result) {
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $errorMessages[] = "Chat ID {$chatId}: " . $result['error'];
                    }
                }
                
                if ($successCount > 0) {
                    $message = "Тестовое сообщение отправлено в {$successCount} чат(ов)";
                    if (!empty($errorMessages)) {
                        $message .= "\nОшибки: " . implode(', ', $errorMessages);
                    }
                } else {
                    $errors = array_merge($errors, $errorMessages);
                }
            } else {
                $errors[] = 'Не удалось подключиться к Telegram API';
            }
        }
    }
}

$currentSettings = ConfigService::getAllSettings();
$queueStats = QueueService::getQueueStats();
$spamStats = AntiSpamService::getSpamStats();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

if (!empty($errors)) {
    echo CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => implode('<br>', $errors)
    ]);
}

if (!empty($message)) {
    echo CAdminMessage::ShowMessage([
        'TYPE' => 'OK',
        'MESSAGE' => $message
    ]);
}

$tabControl = new CAdminTabControl('telegram_settings', [
    ['DIV' => 'main', 'TAB' => 'Основные настройки', 'TITLE' => 'Основные настройки'],
    ['DIV' => 'antispam', 'TAB' => 'Антиспам', 'TITLE' => 'Настройки антиспама'],
    ['DIV' => 'stats', 'TAB' => 'Статистика', 'TITLE' => 'Статистика и мониторинг'],
]);

?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>">
    <?= bitrix_sessid_post() ?>
    
    <?php $tabControl->Begin(); ?>
    
    <?php $tabControl->BeginNextTab(); ?>
    
    <tr>
        <td width="40%">Включить модуль:</td>
        <td>
            <input type="checkbox" name="enabled" value="Y" <?= $currentSettings['enabled'] ? 'checked' : '' ?>>
        </td>
    </tr>
    
    <tr>
        <td>Токен бота <span class="required">*</span>:</td>
        <td>
            <input type="text" name="bot_token" value="<?= htmlspecialchars($currentSettings['bot_token']) ?>" size="50">
            <br><small>Получите у @BotFather в Telegram</small>
        </td>
    </tr>
    
    <tr>
        <td>Chat ID <span class="required">*</span>:</td>
        <td>
            <input type="text" name="chat_ids" value="<?= htmlspecialchars(implode(', ', $currentSettings['chat_ids'])) ?>" size="50">
            <br><small>Несколько ID через запятую. Для групп используйте отрицательные ID</small>
        </td>
    </tr>
    
    <tr>
        <td>Типы критических событий:</td>
        <td>
            <input type="text" name="critical_event_types" value="<?= htmlspecialchars(implode(', ', $currentSettings['critical_event_types'])) ?>" size="50">
            <br><small>Например: SECURITY, ERROR, EXCEPTION (пусто = все типы)</small>
        </td>
    </tr>
    
    <tr>
        <td>Ключевые слова:</td>
        <td>
            <input type="text" name="critical_keywords" value="<?= htmlspecialchars(implode(', ', $currentSettings['critical_keywords'])) ?>" size="50">
            <br><small>Слова для поиска в описании событий, через запятую</small>
        </td>
    </tr>
    
    <tr>
        <td>Максимум повторов:</td>
        <td>
            <input type="number" name="max_retries" value="<?= $currentSettings['max_retries'] ?>" min="1" max="10">
        </td>
    </tr>
    
    <tr>
        <td>Лимит обработки очереди:</td>
        <td>
            <input type="number" name="queue_limit" value="<?= $currentSettings['queue_limit'] ?>" min="1" max="100">
        </td>
    </tr>
    
    <tr>
        <td>Включить логирование:</td>
        <td>
            <input type="checkbox" name="logging_enabled" value="Y" <?= $currentSettings['logging_enabled'] ? 'checked' : '' ?>>
        </td>
    </tr>
    
    <tr>
        <td>Уровень логирования:</td>
        <td>
            <select name="log_level">
                <option value="ERROR" <?= $currentSettings['log_level'] === 'ERROR' ? 'selected' : '' ?>>ERROR</option>
                <option value="WARNING" <?= $currentSettings['log_level'] === 'WARNING' ? 'selected' : '' ?>>WARNING</option>
                <option value="INFO" <?= $currentSettings['log_level'] === 'INFO' ? 'selected' : '' ?>>INFO</option>
                <option value="DEBUG" <?= $currentSettings['log_level'] === 'DEBUG' ? 'selected' : '' ?>>DEBUG</option>
            </select>
        </td>
    </tr>
    
    <?php $tabControl->BeginNextTab(); ?>
    
    <tr>
        <td>Интервал антиспама (сек):</td>
        <td>
            <input type="number" name="antispam_interval" value="<?= $currentSettings['antispam_interval'] ?>" min="60" max="3600">
            <br><small>Минимальный интервал между одинаковыми уведомлениями</small>
        </td>
    </tr>
    
    <tr>
        <td>Режим тишины:</td>
        <td>
            <input type="checkbox" name="silence_mode" value="Y" <?= $currentSettings['silence_mode'] ? 'checked' : '' ?>>
            <br><small>Автоматически включать режим тишины для повторяющихся событий</small>
        </td>
    </tr>
    
    <tr>
        <td>Длительность тишины (сек):</td>
        <td>
            <input type="number" name="silence_duration" value="<?= $currentSettings['silence_duration'] ?>" min="300" max="86400">
            <br><small>На сколько времени отключать уведомления для типа события</small>
        </td>
    </tr>
    
    <?php $tabControl->BeginNextTab(); ?>
    
    <tr>
        <td colspan="2">
            <h3>Очередь уведомлений</h3>
            <table class="internal">
                <tr>
                    <th>Статус</th>
                    <th>Количество</th>
                </tr>
                <tr>
                    <td>Ожидают</td>
                    <td><?= $queueStats['pending'] ?></td>
                </tr>
                <tr>
                    <td>Обрабатываются</td>
                    <td><?= $queueStats['processing'] ?></td>
                </tr>
                <tr>
                    <td>Отправлены</td>
                    <td><?= $queueStats['sent'] ?></td>
                </tr>
                <tr>
                    <td>Ошибки</td>
                    <td><?= $queueStats['failed'] ?></td>
                </tr>
                <tr>
                    <td><strong>Всего</strong></td>
                    <td><strong><?= $queueStats['total'] ?></strong></td>
                </tr>
            </table>
            
            <h3>Антиспам статистика</h3>
            <table class="internal">
                <tr>
                    <th>Параметр</th>
                    <th>Значение</th>
                </tr>
                <tr>
                    <td>Всего событий</td>
                    <td><?= $spamStats['total_events'] ?></td>
                </tr>
                <tr>
                    <td>Активных блокировок</td>
                    <td><?= $spamStats['active_silences'] ?></td>
                </tr>
            </table>
            
            <?php if (!empty($spamStats['most_frequent_events'])): ?>
            <h3>Наиболее частые события</h3>
            <table class="internal">
                <tr>
                    <th>Тип события</th>
                    <th>Количество</th>
                </tr>
                <?php foreach ($spamStats['most_frequent_events'] as $event): ?>
                <tr>
                    <td><?= htmlspecialchars($event['event_type']) ?></td>
                    <td><?= $event['count'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </td>
    </tr>
    
    <?php $tabControl->Buttons(); ?>
    
    <input type="submit" name="save" value="Сохранить" class="adm-btn-save">
    <input type="submit" name="test_connection" value="Тест соединения" class="adm-btn">
    
    <?php $tabControl->End(); ?>
</form>

<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
?> 