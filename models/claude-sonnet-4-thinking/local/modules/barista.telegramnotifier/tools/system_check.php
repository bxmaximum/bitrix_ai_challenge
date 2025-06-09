<?php

/**
 * Утилита проверки системы для модуля Telegram уведомлений
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Main\Application;

class SystemChecker
{
    private array $results = [];
    
    public function runAllChecks(): array
    {
        $this->checkPHPVersion();
        $this->checkBitrixVersion();
        $this->checkExtensions();
        $this->checkPermissions();
        $this->checkDatabase();
        $this->checkModule();
        $this->checkConfiguration();
        $this->checkConnectivity();
        
        return $this->results;
    }
    
    private function addResult(string $category, string $check, bool $status, string $message, string $recommendation = ''): void
    {
        $this->results[$category][] = [
            'check' => $check,
            'status' => $status,
            'message' => $message,
            'recommendation' => $recommendation
        ];
    }
    
    private function checkPHPVersion(): void
    {
        $version = PHP_VERSION;
        $required = '8.1.0';
        
        $status = version_compare($version, $required, '>=');
        $message = "PHP версия: {$version}";
        $recommendation = $status ? '' : "Требуется PHP {$required} или выше";
        
        $this->addResult('PHP', 'Версия PHP', $status, $message, $recommendation);
        
        // Проверка режима CLI
        $cliAvailable = function_exists('php_sapi_name');
        $this->addResult('PHP', 'CLI доступен', $cliAvailable, 
            $cliAvailable ? 'CLI режим доступен' : 'CLI режим недоступен',
            $cliAvailable ? '' : 'Установите PHP CLI для работы cron заданий'
        );
    }
    
    private function checkBitrixVersion(): void
    {
        if (!defined('SM_VERSION')) {
            $this->addResult('Битрикс', 'Версия', false, 'Не удалось определить версию', 'Проверьте установку Битрикс');
            return;
        }
        
        $version = SM_VERSION;
        $required = '20.0.0';
        
        $status = version_compare($version, $required, '>=');
        $message = "Битрикс версия: {$version}";
        $recommendation = $status ? '' : "Требуется Битрикс {$required} или выше";
        
        $this->addResult('Битрикс', 'Версия', $status, $message, $recommendation);
    }
    
    private function checkExtensions(): void
    {
        $required = [
            'openssl' => 'Шифрование токенов',
            'curl' => 'HTTP запросы к Telegram API',
            'json' => 'Обработка JSON данных',
            'mbstring' => 'Работа с Unicode строками'
        ];
        
        foreach ($required as $ext => $purpose) {
            $loaded = extension_loaded($ext);
            $this->addResult('Расширения', $ext, $loaded,
                $loaded ? "Расширение {$ext} загружено" : "Расширение {$ext} не найдено",
                $loaded ? '' : "Установите расширение {$ext} для {$purpose}"
            );
        }
    }
    
    private function checkPermissions(): void
    {
        $paths = [
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tmp/' => 'Запись логов',
            $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' => 'Установка модулей'
        ];
        
        foreach ($paths as $path => $purpose) {
            $writable = is_writable($path);
            $this->addResult('Права доступа', basename($path), $writable,
                $writable ? "Папка {$path} доступна для записи" : "Папка {$path} недоступна для записи",
                $writable ? '' : "Установите права записи для {$purpose}"
            );
        }
    }
    
    private function checkDatabase(): void
    {
        try {
            $connection = Application::getConnection();
            
            // Проверка подключения
            $this->addResult('База данных', 'Подключение', true, 'Подключение к БД работает');
            
            // Проверка таблиц модуля
            $tables = [
                'barista_telegram_queue' => 'Очередь уведомлений',
                'barista_telegram_notifications' => 'История уведомлений'
            ];
            
            foreach ($tables as $table => $purpose) {
                $exists = $connection->isTableExists($table);
                $this->addResult('База данных', $table, $exists,
                    $exists ? "Таблица {$table} существует" : "Таблица {$table} не найдена",
                    $exists ? '' : "Переустановите модуль для создания таблицы {$purpose}"
                );
            }
            
        } catch (\Throwable $e) {
            $this->addResult('База данных', 'Подключение', false, 
                'Ошибка подключения: ' . $e->getMessage(),
                'Проверьте настройки подключения к БД'
            );
        }
    }
    
    private function checkModule(): void
    {
        $moduleInstalled = Loader::includeModule('barista.telegramnotifier');
        $this->addResult('Модуль', 'Установка', $moduleInstalled,
            $moduleInstalled ? 'Модуль установлен и подключен' : 'Модуль не установлен',
            $moduleInstalled ? '' : 'Установите модуль через административную панель'
        );
        
        if (!$moduleInstalled) {
            return;
        }
        
        // Проверка классов
        $classes = [
            'Barista\\TelegramNotifier\\EventHandler',
            'Barista\\TelegramNotifier\\Services\\ConfigService',
            'Barista\\TelegramNotifier\\Services\\TelegramService',
            'Barista\\TelegramNotifier\\Services\\QueueService'
        ];
        
        foreach ($classes as $class) {
            $exists = class_exists($class);
            $this->addResult('Модуль', basename(str_replace('\\', '/', $class)), $exists,
                $exists ? "Класс {$class} загружен" : "Класс {$class} не найден",
                $exists ? '' : 'Переустановите модуль'
            );
        }
    }
    
    private function checkConfiguration(): void
    {
        if (!Loader::includeModule('barista.telegramnotifier')) {
            return;
        }
        
        $config = \Barista\TelegramNotifier\Services\ConfigService::getAllSettings();
        
        $this->addResult('Конфигурация', 'Модуль включен', $config['enabled'],
            $config['enabled'] ? 'Модуль включен' : 'Модуль выключен',
            $config['enabled'] ? '' : 'Включите модуль в настройках'
        );
        
        $hasToken = !empty($config['bot_token']);
        $this->addResult('Конфигурация', 'Токен бота', $hasToken,
            $hasToken ? 'Токен бота настроен' : 'Токен бота не настроен',
            $hasToken ? '' : 'Получите токен у @BotFather и настройте в модуле'
        );
        
        $hasChats = !empty($config['chat_ids']);
        $this->addResult('Конфигурация', 'Chat ID', $hasChats,
            $hasChats ? 'Chat ID настроены (' . count($config['chat_ids']) . ')' : 'Chat ID не настроены',
            $hasChats ? '' : 'Настройте Chat ID для отправки уведомлений'
        );
    }
    
    private function checkConnectivity(): void
    {
        if (!Loader::includeModule('barista.telegramnotifier')) {
            return;
        }
        
        $config = \Barista\TelegramNotifier\Services\ConfigService::getAllSettings();
        
        if (empty($config['bot_token'])) {
            $this->addResult('Подключение', 'Telegram API', false, 
                'Невозможно проверить - токен не настроен',
                'Настройте токен бота'
            );
            return;
        }
        
        try {
            $telegram = new \Barista\TelegramNotifier\Services\TelegramService($config['bot_token']);
            $result = $telegram->testConnection();
            
            $this->addResult('Подключение', 'Telegram API', $result,
                $result ? 'Соединение с Telegram API работает' : 'Ошибка соединения с Telegram API',
                $result ? '' : 'Проверьте токен бота и доступность api.telegram.org'
            );
            
            if ($result && !empty($config['chat_ids'])) {
                $validChats = 0;
                foreach ($config['chat_ids'] as $chatId) {
                    $chatResult = $telegram->validateChatAccess($chatId);
                    if ($chatResult['valid']) {
                        $validChats++;
                    }
                }
                
                $allValid = $validChats === count($config['chat_ids']);
                $this->addResult('Подключение', 'Доступ к чатам', $allValid,
                    "Доступных чатов: {$validChats} из " . count($config['chat_ids']),
                    $allValid ? '' : 'Проверьте права бота в недоступных чатах'
                );
            }
            
        } catch (\Throwable $e) {
            $this->addResult('Подключение', 'Telegram API', false,
                'Ошибка: ' . $e->getMessage(),
                'Проверьте настройки и доступность интернета'
            );
        }
    }
    
    public function printResults(): void
    {
        $totalChecks = 0;
        $passedChecks = 0;
        
        foreach ($this->results as $category => $checks) {
            echo "\n=== {$category} ===\n";
            
            foreach ($checks as $check) {
                $totalChecks++;
                $status = $check['status'] ? '✅' : '❌';
                
                if ($check['status']) {
                    $passedChecks++;
                }
                
                echo "{$status} {$check['check']}: {$check['message']}\n";
                
                if (!$check['status'] && !empty($check['recommendation'])) {
                    echo "   💡 {$check['recommendation']}\n";
                }
            }
        }
        
        echo "\n=== Итого ===\n";
        echo "Пройдено проверок: {$passedChecks} из {$totalChecks}\n";
        
        if ($passedChecks === $totalChecks) {
            echo "🎉 Все проверки пройдены! Система готова к работе.\n";
        } else {
            echo "⚠️  Обнаружены проблемы. Исправьте их для корректной работы модуля.\n";
        }
    }
    
    public function getScore(): float
    {
        $total = 0;
        $passed = 0;
        
        foreach ($this->results as $checks) {
            foreach ($checks as $check) {
                $total++;
                if ($check['status']) {
                    $passed++;
                }
            }
        }
        
        return $total > 0 ? ($passed / $total) * 100 : 0;
    }
}

// Запуск проверки
if (php_sapi_name() === 'cli') {
    $checker = new SystemChecker();
    $checker->runAllChecks();
    $checker->printResults();
    
    $score = $checker->getScore();
    echo "\nОценка готовности системы: " . round($score, 1) . "%\n";
    
    exit($score >= 80 ? 0 : 1);
} else {
    echo "Этот скрипт предназначен для запуска из командной строки\n";
    echo "Запустите: php tools/system_check.php\n";
} 