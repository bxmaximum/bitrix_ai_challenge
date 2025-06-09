<?php

if (php_sapi_name() !== 'cli') {
    die('Этот скрипт может быть запущен только из командной строки');
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Barista\TelegramNotifier\EventHandler;
use Barista\TelegramNotifier\Services\LogService;
use Barista\TelegramNotifier\Services\QueueService;
use Barista\TelegramNotifier\Services\AntiSpamService;
use Barista\TelegramNotifier\Services\ConfigService;

if (!Loader::includeModule('barista.telegramnotifier')) {
    echo "Модуль barista.telegramnotifier не установлен\n";
    exit(1);
}

if (!ConfigService::isModuleEnabled()) {
    echo "Модуль отключен\n";
    exit(0);
}

function printUsage(): void
{
    echo "Использование: php process_queue.php [команда]\n\n";
    echo "Команды:\n";
    echo "  process     - Обработать очередь уведомлений (по умолчанию)\n";
    echo "  stats       - Показать статистику\n";
    echo "  cleanup     - Очистить старые записи\n";
    echo "  retry       - Повторить неудачные задания\n";
    echo "  clear       - Очистить всю очередь\n";
    echo "  test        - Отправить тестовое уведомление\n";
    echo "  help        - Показать эту справку\n\n";
}

function processQueue(): void
{
    $startTime = microtime(true);
    $processed = EventHandler::processQueue();
    $endTime = microtime(true);
    
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "Обработано уведомлений: {$processed}\n";
    echo "Время выполнения: {$duration} мс\n";
    
    if ($processed > 0) {
        LogService::log("Обработка очереди завершена", [
            'processed' => $processed,
            'duration_ms' => $duration
        ]);
    }
}

function showStats(): void
{
    $queueStats = QueueService::getQueueStats();
    $spamStats = AntiSpamService::getSpamStats();
    
    echo "=== Статистика очереди ===\n";
    echo "Ожидают отправки: {$queueStats['pending']}\n";
    echo "Обрабатываются: {$queueStats['processing']}\n";
    echo "Отправлены: {$queueStats['sent']}\n";
    echo "Ошибки: {$queueStats['failed']}\n";
    echo "Всего: {$queueStats['total']}\n\n";
    
    echo "=== Статистика антиспама ===\n";
    echo "Всего событий: {$spamStats['total_events']}\n";
    echo "Активных блокировок: {$spamStats['active_silences']}\n";
    
    if (!empty($spamStats['most_frequent_events'])) {
        echo "\nНаиболее частые события:\n";
        foreach ($spamStats['most_frequent_events'] as $event) {
            echo "  {$event['event_type']}: {$event['count']}\n";
        }
    }
    echo "\n";
}

function cleanup(): void
{
    $result = EventHandler::cleanupOldData();
    
    echo "Очищено записей:\n";
    echo "  Очередь: {$result['queue']}\n";
    echo "  Уведомления: {$result['notifications']}\n";
    
    $expiredProtections = AntiSpamService::cleanExpiredProtections();
    echo "  Истекшие защиты: {$expiredProtections}\n";
    
    LogService::log("Очистка старых данных выполнена", $result);
}

function retryFailed(): void
{
    $retried = QueueService::retryFailedJobs();
    echo "Повторно добавлено в очередь: {$retried} заданий\n";
    
    if ($retried > 0) {
        LogService::log("Повторная постановка в очередь выполнена", ['retried' => $retried]);
    }
}

function clearQueue(): void
{
    $cleared = QueueService::clearQueue();
    echo "Очищено записей из очереди: {$cleared}\n";
    
    LogService::log("Очередь очищена", ['cleared' => $cleared]);
}

function sendTest(): void
{
    if (!ConfigService::getBotToken() || empty(ConfigService::getChatIds())) {
        echo "Не настроены токен бота или Chat ID\n";
        exit(1);
    }
    
    $testEvent = [
        'AUDIT_TYPE_ID' => 'TEST',
        'ITEM_ID' => 'cli_test_' . time(),
        'DESCRIPTION' => 'Тестовое критическое событие из CLI',
        'REMOTE_ADDR' => '127.0.0.1',
        'REQUEST_URI' => '/test',
        'SITE_ID' => 's1',
        'USER_ID' => 0,
        'SEVERITY' => 'ERROR',
        'TIMESTAMP' => time(),
    ];
    
    $added = QueueService::addNotification($testEvent);
    
    if ($added) {
        echo "Тестовое уведомление добавлено в очередь\n";
        echo "Запустите обработку очереди для отправки\n";
    } else {
        echo "Ошибка добавления тестового уведомления\n";
        exit(1);
    }
}

$command = $argv[1] ?? 'process';

try {
    switch ($command) {
        case 'process':
            processQueue();
            break;
            
        case 'stats':
            showStats();
            break;
            
        case 'cleanup':
            cleanup();
            break;
            
        case 'retry':
            retryFailed();
            break;
            
        case 'clear':
            clearQueue();
            break;
            
        case 'test':
            sendTest();
            break;
            
        case 'help':
        case '--help':
        case '-h':
            printUsage();
            break;
            
        default:
            echo "Неизвестная команда: {$command}\n\n";
            printUsage();
            exit(1);
    }
} catch (Throwable $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
    LogService::error("CLI ошибка", [
        'command' => $command,
        'exception' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit(1);
} 