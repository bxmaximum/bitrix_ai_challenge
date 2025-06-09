<?php

namespace Barista\TelegramNotifier\Services;

use Bitrix\Main\Diag\Logger;
use Bitrix\Main\EventLog;

class LogService
{
    private const MODULE_ID = 'barista.telegramnotifier';
    
    public static function log(string $message, array $context = []): void
    {
        if (!ConfigService::isLoggingEnabled()) {
            return;
        }

        $level = ConfigService::getLogLevel();
        if ($level !== 'INFO' && $level !== 'DEBUG') {
            return;
        }

        static::writeLog('INFO', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        static::writeLog('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        $level = ConfigService::getLogLevel();
        if (!in_array($level, ['DEBUG', 'INFO', 'WARNING'])) {
            return;
        }

        static::writeLog('WARNING', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        if (ConfigService::getLogLevel() !== 'DEBUG') {
            return;
        }

        static::writeLog('DEBUG', $message, $context);
    }

    private static function writeLog(string $level, string $message, array $context = []): void
    {
        try {
            $logMessage = static::formatMessage($message, $context);
            
            EventLog::add([
                'SEVERITY' => static::mapLevelToSeverity($level),
                'AUDIT_TYPE_ID' => static::MODULE_ID,
                'MODULE_ID' => static::MODULE_ID,
                'ITEM_ID' => '',
                'DESCRIPTION' => $logMessage,
            ]);

            if (defined('BX_FILE_PERMISSIONS')) {
                $logFile = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tmp/' . static::MODULE_ID . '.log';
                $timestamp = date('Y-m-d H:i:s');
                $formattedMessage = "[{$timestamp}] {$level}: {$logMessage}" . PHP_EOL;
                
                file_put_contents($logFile, $formattedMessage, FILE_APPEND | LOCK_EX);
            }

        } catch (\Throwable $e) {
            // В случае ошибки логирования просто игнорируем
        }
    }

    private static function formatMessage(string $message, array $context = []): string
    {
        if (empty($context)) {
            return $message;
        }

        $contextString = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return $message . ' | Context: ' . $contextString;
    }

    private static function mapLevelToSeverity(string $level): string
    {
        return match($level) {
            'DEBUG' => 'INFO',
            'INFO' => 'INFO',
            'WARNING' => 'WARNING',
            'ERROR' => 'ERROR',
            default => 'INFO'
        };
    }

    public static function clearOldLogs(int $daysOld = 30): int
    {
        $cleared = 0;
        
        try {
            $logFile = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tmp/' . static::MODULE_ID . '.log';
            
            if (file_exists($logFile)) {
                $fileTime = filemtime($logFile);
                $cutoffTime = time() - ($daysOld * 24 * 3600);
                
                if ($fileTime < $cutoffTime) {
                    unlink($logFile);
                    $cleared = 1;
                }
            }
            
            $date = new \Bitrix\Main\Type\DateTime();
            $date->add('-' . $daysOld . ' days');
            
            $result = EventLog::getList(
                [],
                [
                    'AUDIT_TYPE_ID' => static::MODULE_ID,
                    '<TIMESTAMP_X' => $date
                ],
                false,
                false,
                ['ID']
            );
            
            while ($row = $result->fetch()) {
                EventLog::delete($row['ID']);
                $cleared++;
            }
            
        } catch (\Throwable $e) {
            // Игнорируем ошибки очистки логов
        }
        
        return $cleared;
    }

    public static function getRecentLogs(int $limit = 100): array
    {
        $logs = [];
        
        try {
            $result = EventLog::getList(
                ['TIMESTAMP_X' => 'DESC'],
                ['AUDIT_TYPE_ID' => static::MODULE_ID],
                false,
                ['nTopCount' => $limit],
                ['ID', 'TIMESTAMP_X', 'SEVERITY', 'DESCRIPTION']
            );
            
            while ($row = $result->fetch()) {
                $logs[] = [
                    'id' => $row['ID'],
                    'timestamp' => $row['TIMESTAMP_X'],
                    'level' => $row['SEVERITY'],
                    'message' => $row['DESCRIPTION']
                ];
            }
        } catch (\Throwable $e) {
            // В случае ошибки возвращаем пустой массив
        }
        
        return $logs;
    }

    public static function exportLogs(int $daysBack = 7): string
    {
        $logs = [];
        
        try {
            $date = new \Bitrix\Main\Type\DateTime();
            $date->add('-' . $daysBack . ' days');
            
            $result = EventLog::getList(
                ['TIMESTAMP_X' => 'DESC'],
                [
                    'AUDIT_TYPE_ID' => static::MODULE_ID,
                    '>TIMESTAMP_X' => $date
                ],
                false,
                false,
                ['TIMESTAMP_X', 'SEVERITY', 'DESCRIPTION']
            );
            
            while ($row = $result->fetch()) {
                $logs[] = sprintf(
                    '[%s] %s: %s',
                    $row['TIMESTAMP_X']->format('Y-m-d H:i:s'),
                    $row['SEVERITY'],
                    $row['DESCRIPTION']
                );
            }
        } catch (\Throwable $e) {
            $logs[] = 'Ошибка экспорта логов: ' . $e->getMessage();
        }
        
        return implode("\n", $logs);
    }

    public static function getLogStats(): array
    {
        $stats = [
            'total' => 0,
            'errors' => 0,
            'warnings' => 0,
            'info' => 0
        ];
        
        try {
            $result = EventLog::getList(
                [],
                ['AUDIT_TYPE_ID' => static::MODULE_ID],
                false,
                false,
                ['ID', 'SEVERITY']
            );
            
            while ($row = $result->fetch()) {
                $stats['total']++;
                
                switch ($row['SEVERITY']) {
                    case 'ERROR':
                        $stats['errors']++;
                        break;
                    case 'WARNING':
                        $stats['warnings']++;
                        break;
                    default:
                        $stats['info']++;
                        break;
                }
            }
        } catch (\Throwable $e) {
            // В случае ошибки возвращаем нули
        }
        
        return $stats;
    }
} 