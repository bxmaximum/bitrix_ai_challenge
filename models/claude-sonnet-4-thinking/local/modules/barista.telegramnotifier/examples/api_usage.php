<?php

/**
 * Примеры использования API модуля Telegram уведомлений
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Barista\TelegramNotifier\Services\QueueService;
use Barista\TelegramNotifier\Services\ConfigService;
use Barista\TelegramNotifier\Services\TelegramService;
use Barista\TelegramNotifier\Services\AntiSpamService;
use Barista\TelegramNotifier\EventHandler;

if (!Loader::includeModule('barista.telegramnotifier')) {
    die('Модуль не установлен');
}

// Пример 1: Отправка кастомного уведомления
function sendCustomNotification(): void
{
    $eventData = [
        'AUDIT_TYPE_ID' => 'CUSTOM_ERROR',
        'ITEM_ID' => 'order_12345',
        'DESCRIPTION' => 'Критическая ошибка при обработке заказа #12345',
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '/api/orders',
        'SITE_ID' => SITE_ID,
        'USER_ID' => $GLOBALS['USER']->GetID(),
        'SEVERITY' => 'CRITICAL',
        'TIMESTAMP' => time(),
    ];

    $success = QueueService::addNotification($eventData);
    
    if ($success) {
        echo "Уведомление добавлено в очередь\n";
    } else {
        echo "Ошибка добавления уведомления\n";
    }
}

// Пример 2: Проверка настроек модуля
function checkModuleSettings(): void
{
    $settings = ConfigService::getAllSettings();
    
    echo "Статус модуля: " . ($settings['enabled'] ? 'Включен' : 'Выключен') . "\n";
    echo "Количество чатов: " . count($settings['chat_ids']) . "\n";
    echo "Режим тишины: " . ($settings['silence_mode'] ? 'Включен' : 'Выключен') . "\n";
    
    if (!empty($settings['bot_token'])) {
        $telegram = TelegramService::createFromConfig();
        if ($telegram && $telegram->testConnection()) {
            echo "Соединение с Telegram: OK\n";
        } else {
            echo "Соединение с Telegram: ОШИБКА\n";
        }
    }
}

// Пример 3: Управление режимом тишины
function manageSilenceMode(): void
{
    // Включить режим тишины для типа события на 1 час
    $success = AntiSpamService::setSilenceForEventType('ERROR', 60);
    
    if ($success) {
        echo "Режим тишины включен для ERROR событий\n";
    }
    
    // Проверить, активен ли режим тишины
    $inSilence = AntiSpamService::isEventTypeInSilence('ERROR');
    echo "ERROR события в режиме тишины: " . ($inSilence ? 'Да' : 'Нет') . "\n";
    
    // Получить активные защиты
    $protections = AntiSpamService::getActiveSpamProtections();
    echo "Активных защит: " . count($protections) . "\n";
}

// Пример 4: Статистика и мониторинг
function showStatistics(): void
{
    $queueStats = QueueService::getQueueStats();
    $spamStats = AntiSpamService::getSpamStats();
    
    echo "=== Статистика очереди ===\n";
    foreach ($queueStats as $status => $count) {
        echo ucfirst($status) . ": {$count}\n";
    }
    
    echo "\n=== Статистика антиспама ===\n";
    echo "Всего событий: {$spamStats['total_events']}\n";
    echo "Активных блокировок: {$spamStats['active_silences']}\n";
    
    if (!empty($spamStats['most_frequent_events'])) {
        echo "\nТоп-5 событий:\n";
        foreach (array_slice($spamStats['most_frequent_events'], 0, 5) as $event) {
            echo "  {$event['event_type']}: {$event['count']}\n";
        }
    }
}

// Пример 5: Обработка очереди программно
function processQueueManually(): void
{
    $processed = EventHandler::processQueue();
    echo "Обработано уведомлений: {$processed}\n";
    
    if ($processed > 0) {
        // Показать последние задания
        $recentJobs = QueueService::getRecentJobs(5);
        echo "\nПоследние задания:\n";
        foreach ($recentJobs as $job) {
            echo "  ID: {$job['ID']}, Статус: {$job['STATUS']}, Чат: {$job['CHAT_ID']}\n";
        }
    }
}

// Пример 6: Настройка модуля программно
function configureModule(): void
{
    // Включить модуль
    ConfigService::enable();
    
    // Настроить антиспам
    ConfigService::setAntiSpamInterval(300); // 5 минут
    ConfigService::setSilenceMode(true);
    ConfigService::setSilenceDuration(3600); // 1 час
    
    // Настроить обработку
    ConfigService::setMaxRetries(3);
    ConfigService::setQueueProcessingLimit(20);
    
    // Настроить логирование
    ConfigService::setLoggingEnabled(true);
    ConfigService::setLogLevel('INFO');
    
    echo "Модуль настроен\n";
}

// Пример 7: Отправка тестового сообщения
function sendTestMessage(): void
{
    $telegram = TelegramService::createFromConfig();
    if (!$telegram) {
        echo "Не удалось создать Telegram сервис\n";
        return;
    }
    
    $chatIds = ConfigService::getChatIds();
    if (empty($chatIds)) {
        echo "Не настроены Chat ID\n";
        return;
    }
    
    $message = "🧪 **Тестовое сообщение**\n\n";
    $message .= "Время: " . date('d.m.Y H:i:s') . "\n";
    $message .= "Сервер: " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\n";
    $message .= "Модуль работает корректно!";
    
    $results = $telegram->sendToMultipleChats($chatIds, $message);
    
    foreach ($results as $chatId => $result) {
        if ($result['success']) {
            echo "Сообщение отправлено в чат {$chatId}\n";
        } else {
            echo "Ошибка отправки в чат {$chatId}: {$result['error']}\n";
        }
    }
}

// Пример 8: Очистка старых данных
function cleanupOldData(): void
{
    $result = EventHandler::cleanupOldData();
    
    echo "Очищено записей:\n";
    echo "  Очередь: {$result['queue']}\n";
    echo "  Уведомления: {$result['notifications']}\n";
    
    // Дополнительная очистка
    $expiredProtections = AntiSpamService::cleanExpiredProtections();
    echo "  Истекшие защиты: {$expiredProtections}\n";
}

// Запуск примеров
if (php_sapi_name() === 'cli') {
    $action = $argv[1] ?? 'help';
    
    switch ($action) {
        case 'send':
            sendCustomNotification();
            break;
        case 'check':
            checkModuleSettings();
            break;
        case 'silence':
            manageSilenceMode();
            break;
        case 'stats':
            showStatistics();
            break;
        case 'process':
            processQueueManually();
            break;
        case 'config':
            configureModule();
            break;
        case 'test':
            sendTestMessage();
            break;
        case 'cleanup':
            cleanupOldData();
            break;
        default:
            echo "Доступные команды:\n";
            echo "  send    - Отправить кастомное уведомление\n";
            echo "  check   - Проверить настройки\n";
            echo "  silence - Управление режимом тишины\n";
            echo "  stats   - Показать статистику\n";
            echo "  process - Обработать очередь\n";
            echo "  config  - Настроить модуль\n";
            echo "  test    - Отправить тест\n";
            echo "  cleanup - Очистить старые данные\n";
    }
} else {
    echo "Этот файл предназначен для запуска из командной строки\n";
} 