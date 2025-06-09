<?php

namespace Barista\TelegramNotifier\Services;

use Barista\TelegramNotifier\Models\QueueTable;
use Barista\TelegramNotifier\EventHandler;

class QueueService
{
    public static function addNotification(array $eventData): bool
    {
        $chatIds = ConfigService::getChatIds();
        if (empty($chatIds)) {
            LogService::error('Не настроены Chat ID для отправки уведомлений');
            return false;
        }

        $message = EventHandler::formatMessage($eventData);
        $eventId = $eventData['AUDIT_TYPE_ID'] . '_' . ($eventData['ITEM_ID'] ?? time());

        $success = true;
        foreach ($chatIds as $chatId) {
            $chatId = trim($chatId);
            if (empty($chatId)) {
                continue;
            }

            $result = QueueTable::add([
                'EVENT_ID' => $eventId,
                'CHAT_ID' => $chatId,
                'MESSAGE' => $message,
                'STATUS' => 'PENDING',
            ]);

            if (!$result->isSuccess()) {
                LogService::error('Ошибка добавления в очередь', [
                    'chat_id' => $chatId,
                    'errors' => $result->getErrorMessages()
                ]);
                $success = false;
            }
        }

        return $success;
    }

    public static function processQueue(): int
    {
        $limit = ConfigService::getQueueProcessingLimit();
        $jobs = QueueTable::getNextPendingJobs($limit);

        if (empty($jobs)) {
            return 0;
        }

        $telegramService = TelegramService::createFromConfig();
        if (!$telegramService) {
            LogService::error('Не удалось создать Telegram сервис');
            return 0;
        }

        $processed = 0;
        foreach ($jobs as $job) {
            if (!QueueTable::markAsProcessing($job['ID'])) {
                continue;
            }

            try {
                $result = $telegramService->sendMessage(
                    $job['CHAT_ID'],
                    $job['MESSAGE']
                );

                if ($result['success']) {
                    QueueTable::markAsSent($job['ID']);
                    LogService::log('Уведомление отправлено', [
                        'queue_id' => $job['ID'],
                        'chat_id' => $job['CHAT_ID']
                    ]);
                    $processed++;
                } else {
                    $shouldRetry = static::shouldRetry($result);
                    QueueTable::markAsFailed(
                        $job['ID'],
                        $result['error'] ?? 'Неизвестная ошибка',
                        $shouldRetry
                    );
                    
                    LogService::error('Ошибка отправки уведомления', [
                        'queue_id' => $job['ID'],
                        'chat_id' => $job['CHAT_ID'],
                        'error' => $result['error'] ?? 'Неизвестная ошибка',
                        'retry' => $shouldRetry
                    ]);
                }
            } catch (\Throwable $e) {
                QueueTable::markAsFailed($job['ID'], $e->getMessage(), true);
                LogService::error('Исключение при отправке', [
                    'queue_id' => $job['ID'],
                    'exception' => get_class($e),
                    'message' => $e->getMessage()
                ]);
            }

            usleep(100000);
        }

        return $processed;
    }

    private static function shouldRetry(array $result): bool
    {
        $errorCode = $result['error_code'] ?? 0;
        
        $noRetryErrorCodes = [
            400, // Bad Request
            401, // Unauthorized
            403, // Forbidden
            404, // Not Found
        ];

        return !in_array($errorCode, $noRetryErrorCodes);
    }

    public static function getQueueStats(): array
    {
        $stats = [
            'pending' => 0,
            'processing' => 0,
            'sent' => 0,
            'failed' => 0,
            'total' => 0
        ];

        $statuses = ['PENDING', 'PROCESSING', 'SENT', 'FAILED'];
        
        foreach ($statuses as $status) {
            $result = QueueTable::getList([
                'filter' => ['STATUS' => $status],
                'select' => ['CNT'],
                'runtime' => [
                    new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')
                ]
            ]);
            
            if ($row = $result->fetch()) {
                $stats[strtolower($status)] = (int)$row['CNT'];
                $stats['total'] += (int)$row['CNT'];
            }
        }

        return $stats;
    }

    public static function clearQueue(string $status = null): int
    {
        $filter = [];
        if ($status) {
            $filter['STATUS'] = $status;
        }

        $result = QueueTable::getList([
            'filter' => $filter,
            'select' => ['ID']
        ]);

        $deleted = 0;
        while ($row = $result->fetch()) {
            QueueTable::delete($row['ID']);
            $deleted++;
        }

        return $deleted;
    }

    public static function cleanOldRecords(int $daysOld = 7): int
    {
        return QueueTable::cleanOldRecords($daysOld);
    }

    public static function retryFailedJobs(int $maxAge = 3600): int
    {
        $date = new \Bitrix\Main\Type\DateTime();
        $date->add('-' . $maxAge . ' seconds');

        $result = QueueTable::getList([
            'filter' => [
                'STATUS' => 'FAILED',
                '<=UPDATED_AT' => $date,
                '<ATTEMPTS' => ConfigService::getMaxRetries()
            ],
            'select' => ['ID']
        ]);

        $retried = 0;
        while ($row = $result->fetch()) {
            $updateResult = QueueTable::update($row['ID'], [
                'STATUS' => 'PENDING',
                'SCHEDULED_AT' => null,
                'ERROR_MESSAGE' => null
            ]);

            if ($updateResult->isSuccess()) {
                $retried++;
            }
        }

        return $retried;
    }

    public static function scheduleJob(int $jobId, int $delaySeconds): bool
    {
        $scheduledAt = new \Bitrix\Main\Type\DateTime();
        $scheduledAt->add('+' . $delaySeconds . ' seconds');

        $result = QueueTable::update($jobId, [
            'SCHEDULED_AT' => $scheduledAt
        ]);

        return $result->isSuccess();
    }

    public static function prioritizeJob(int $jobId): bool
    {
        return QueueTable::update($jobId, [
            'SCHEDULED_AT' => null,
            'STATUS' => 'PENDING'
        ])->isSuccess();
    }

    public static function getRecentJobs(int $limit = 50): array
    {
        $result = QueueTable::getList([
            'order' => ['ID' => 'DESC'],
            'limit' => $limit,
            'select' => [
                'ID', 'EVENT_ID', 'CHAT_ID', 'STATUS', 
                'ATTEMPTS', 'CREATED_AT', 'UPDATED_AT', 
                'ERROR_MESSAGE'
            ]
        ]);

        return $result->fetchAll();
    }
} 