<?php

namespace Barista\TelegramNotifier;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Barista\TelegramNotifier\Services\QueueService;
use Barista\TelegramNotifier\Services\ConfigService;
use Barista\TelegramNotifier\Services\AntiSpamService;
use Barista\TelegramNotifier\Services\LogService;

class EventHandler
{
    private static $criticalEventTypes = [
        'SECURITY',
        'ERROR', 
        'EXCEPTION',
        'MAIN',
        'PERFMON'
    ];

    private static $criticalSeverities = [
        'ERROR',
        'CRITICAL',
        'ALERT',
        'EMERGENCY'
    ];

    public static function onEventLogAdd(&$arFields): void
    {
        try {
            if (!Loader::includeModule('barista.telegramnotifier')) {
                return;
            }

            if (!ConfigService::isModuleEnabled()) {
                return;
            }

            if (!static::isCriticalEvent($arFields)) {
                return;
            }

            $eventData = static::prepareEventData($arFields);
            
            if (!AntiSpamService::shouldSendNotification($eventData)) {
                LogService::log('Уведомление заблокировано антиспам системой', $eventData);
                return;
            }

            QueueService::addNotification($eventData);
            LogService::log('Событие добавлено в очередь', $eventData);

        } catch (\Throwable $e) {
            LogService::error('Ошибка в обработчике событий: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private static function isCriticalEvent(array $arFields): bool
    {
        $auditTypeId = $arFields['AUDIT_TYPE_ID'] ?? '';
        $severity = $arFields['SEVERITY'] ?? '';
        $description = $arFields['DESCRIPTION'] ?? '';

        $criticalTypes = ConfigService::getCriticalEventTypes();
        if (!empty($criticalTypes)) {
            if (!in_array($auditTypeId, $criticalTypes)) {
                return false;
            }
        } else {
            if (!in_array($auditTypeId, static::$criticalEventTypes)) {
                return false;
            }
        }

        if (!empty($severity) && in_array($severity, static::$criticalSeverities)) {
            return true;
        }

        $criticalKeywords = ConfigService::getCriticalKeywords();
        if (!empty($criticalKeywords)) {
            foreach ($criticalKeywords as $keyword) {
                if (stripos($description, $keyword) !== false) {
                    return true;
                }
            }
        }

        $defaultKeywords = ['ошибка', 'критическая', 'авария', 'недоступен', 'failed', 'critical', 'error'];
        foreach ($defaultKeywords as $keyword) {
            if (stripos($description, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function prepareEventData(array $arFields): array
    {
        return [
            'AUDIT_TYPE_ID' => $arFields['AUDIT_TYPE_ID'] ?? '',
            'ITEM_ID' => $arFields['ITEM_ID'] ?? '',
            'DESCRIPTION' => $arFields['DESCRIPTION'] ?? '',
            'REMOTE_ADDR' => $arFields['REMOTE_ADDR'] ?? '',
            'USER_AGENT' => $arFields['USER_AGENT'] ?? '',
            'REQUEST_URI' => $arFields['REQUEST_URI'] ?? '',
            'SITE_ID' => $arFields['SITE_ID'] ?? '',
            'USER_ID' => $arFields['USER_ID'] ?? 0,
            'GUEST_ID' => $arFields['GUEST_ID'] ?? 0,
            'SEVERITY' => $arFields['SEVERITY'] ?? '',
            'TIMESTAMP' => time(),
        ];
    }

    public static function formatMessage(array $eventData): string
    {
        $message = "🚨 *Критическое событие*\n\n";
        
        $message .= "**Тип:** " . ($eventData['AUDIT_TYPE_ID'] ?? 'Неизвестно') . "\n";
        
        if (!empty($eventData['SEVERITY'])) {
            $severity = match($eventData['SEVERITY']) {
                'EMERGENCY' => '🔥 Аварийная',
                'ALERT' => '🚨 Тревога', 
                'CRITICAL' => '💥 Критическая',
                'ERROR' => '❌ Ошибка',
                default => $eventData['SEVERITY']
            };
            $message .= "**Серьезность:** " . $severity . "\n";
        }
        
        if (!empty($eventData['DESCRIPTION'])) {
            $description = mb_substr($eventData['DESCRIPTION'], 0, 500);
            if (mb_strlen($eventData['DESCRIPTION']) > 500) {
                $description .= '...';
            }
            $message .= "**Описание:** " . $description . "\n";
        }
        
        if (!empty($eventData['REQUEST_URI'])) {
            $message .= "**URL:** " . $eventData['REQUEST_URI'] . "\n";
        }
        
        if (!empty($eventData['REMOTE_ADDR'])) {
            $message .= "**IP:** " . $eventData['REMOTE_ADDR'] . "\n";
        }
        
        if (!empty($eventData['USER_ID']) && $eventData['USER_ID'] > 0) {
            $message .= "**Пользователь ID:** " . $eventData['USER_ID'] . "\n";
        }
        
        if (!empty($eventData['SITE_ID'])) {
            $message .= "**Сайт:** " . $eventData['SITE_ID'] . "\n";
        }
        
        $message .= "\n**Время:** " . date('d.m.Y H:i:s', $eventData['TIMESTAMP']);
        
        return $message;
    }

    public static function processQueue(): int
    {
        if (!Loader::includeModule('barista.telegramnotifier')) {
            return 0;
        }

        return QueueService::processQueue();
    }

    public static function cleanupOldData(): array
    {
        if (!Loader::includeModule('barista.telegramnotifier')) {
            return ['queue' => 0, 'notifications' => 0];
        }

        return [
            'queue' => QueueService::cleanOldRecords(),
            'notifications' => AntiSpamService::cleanOldRecords(),
        ];
    }
} 