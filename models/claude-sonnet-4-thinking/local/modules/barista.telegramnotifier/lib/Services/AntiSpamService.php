<?php

namespace Barista\TelegramNotifier\Services;

use Barista\TelegramNotifier\Models\NotificationTable;

class AntiSpamService
{
    public static function shouldSendNotification(array $eventData): bool
    {
        $eventHash = NotificationTable::createEventHash($eventData);
        
        if (NotificationTable::isEventProcessed($eventHash)) {
            return false;
        }

        $silenceUntil = null;
        if (ConfigService::getSilenceMode()) {
            $silenceDuration = ConfigService::getSilenceDuration();
            $silenceUntil = new \Bitrix\Main\Type\DateTime();
            $silenceUntil->add('+' . $silenceDuration . ' seconds');
        }

        return NotificationTable::recordEvent($eventData, $silenceUntil);
    }

    public static function setSilenceForEventType(string $auditTypeId, int $durationMinutes = null): bool
    {
        $duration = $durationMinutes ?? (ConfigService::getSilenceDuration() / 60);
        return NotificationTable::setSilenceMode($auditTypeId, $duration);
    }

    public static function clearSilenceForEventType(string $auditTypeId): bool
    {
        $result = NotificationTable::getList([
            'filter' => ['AUDIT_TYPE_ID' => $auditTypeId],
            'select' => ['ID']
        ]);

        $cleared = 0;
        while ($row = $result->fetch()) {
            NotificationTable::update($row['ID'], ['SILENCE_UNTIL' => null]);
            $cleared++;
        }

        return $cleared > 0;
    }

    public static function getActiveSpamProtections(): array
    {
        $protections = [];

        try {
            $result = NotificationTable::getList([
                'filter' => [
                    '!SILENCE_UNTIL' => null,
                    '>SILENCE_UNTIL' => new \Bitrix\Main\Type\DateTime()
                ],
                'select' => ['AUDIT_TYPE_ID', 'SILENCE_UNTIL', 'CREATED_AT'],
                'group' => ['AUDIT_TYPE_ID']
            ]);

            while ($row = $result->fetch()) {
                $protections[] = [
                    'event_type' => $row['AUDIT_TYPE_ID'],
                    'silence_until' => $row['SILENCE_UNTIL'],
                    'created_at' => $row['CREATED_AT']
                ];
            }
        } catch (\Throwable $e) {
            LogService::error('Ошибка получения активных защит от спама', [
                'exception' => get_class($e),
                'message' => $e->getMessage()
            ]);
        }

        return $protections;
    }

    public static function cleanExpiredProtections(): int
    {
        return NotificationTable::cleanExpiredSilence();
    }

    public static function cleanOldRecords(int $daysOld = 30): int
    {
        return NotificationTable::cleanOldRecords($daysOld);
    }

    public static function getSpamStats(): array
    {
        $stats = [
            'total_events' => 0,
            'blocked_events' => 0,
            'active_silences' => 0,
            'most_frequent_events' => []
        ];

        try {
            $result = NotificationTable::getList([
                'select' => ['CNT'],
                'runtime' => [
                    new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')
                ]
            ]);

            if ($row = $result->fetch()) {
                $stats['total_events'] = (int)$row['CNT'];
            }

            $result = NotificationTable::getList([
                'filter' => [
                    '!SILENCE_UNTIL' => null,
                    '>SILENCE_UNTIL' => new \Bitrix\Main\Type\DateTime()
                ],
                'select' => ['CNT'],
                'runtime' => [
                    new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')
                ]
            ]);

            if ($row = $result->fetch()) {
                $stats['active_silences'] = (int)$row['CNT'];
            }

            $result = NotificationTable::getList([
                'select' => ['AUDIT_TYPE_ID', 'CNT'],
                'runtime' => [
                    new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')
                ],
                'group' => ['AUDIT_TYPE_ID'],
                'order' => ['CNT' => 'DESC'],
                'limit' => 10
            ]);

            while ($row = $result->fetch()) {
                $stats['most_frequent_events'][] = [
                    'event_type' => $row['AUDIT_TYPE_ID'],
                    'count' => (int)$row['CNT']
                ];
            }

        } catch (\Throwable $e) {
            LogService::error('Ошибка получения статистики спама', [
                'exception' => get_class($e),
                'message' => $e->getMessage()
            ]);
        }

        return $stats;
    }

    public static function isEventTypeInSilence(string $auditTypeId): bool
    {
        try {
            $result = NotificationTable::getList([
                'filter' => [
                    'AUDIT_TYPE_ID' => $auditTypeId,
                    '!SILENCE_UNTIL' => null,
                    '>SILENCE_UNTIL' => new \Bitrix\Main\Type\DateTime()
                ],
                'select' => ['ID'],
                'limit' => 1
            ]);

            return (bool)$result->fetch();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function getEventHistory(string $auditTypeId, int $limit = 50): array
    {
        $history = [];

        try {
            $result = NotificationTable::getList([
                'filter' => ['AUDIT_TYPE_ID' => $auditTypeId],
                'order' => ['CREATED_AT' => 'DESC'],
                'limit' => $limit,
                'select' => ['CREATED_AT', 'DESCRIPTION', 'SILENCE_UNTIL']
            ]);

            while ($row = $result->fetch()) {
                $history[] = [
                    'created_at' => $row['CREATED_AT'],
                    'description' => $row['DESCRIPTION'],
                    'was_silenced' => !empty($row['SILENCE_UNTIL'])
                ];
            }
        } catch (\Throwable $e) {
            LogService::error('Ошибка получения истории событий', [
                'audit_type_id' => $auditTypeId,
                'exception' => get_class($e),
                'message' => $e->getMessage()
            ]);
        }

        return $history;
    }

    public static function bulkSetSilence(array $eventTypes, int $durationMinutes): array
    {
        $results = [];
        
        foreach ($eventTypes as $eventType) {
            $results[$eventType] = static::setSilenceForEventType($eventType, $durationMinutes);
        }
        
        return $results;
    }

    public static function bulkClearSilence(array $eventTypes): array
    {
        $results = [];
        
        foreach ($eventTypes as $eventType) {
            $results[$eventType] = static::clearSilenceForEventType($eventType);
        }
        
        return $results;
    }

    public static function getRecommendedSilenceSettings(): array
    {
        $stats = static::getSpamStats();
        $recommendations = [];
        
        foreach ($stats['most_frequent_events'] as $event) {
            if ($event['count'] > 10) {
                $recommendations[] = [
                    'event_type' => $event['event_type'],
                    'current_count' => $event['count'],
                    'recommended_silence' => min(60, $event['count'] * 5),
                    'reason' => 'Высокая частота событий'
                ];
            }
        }
        
        return $recommendations;
    }
} 