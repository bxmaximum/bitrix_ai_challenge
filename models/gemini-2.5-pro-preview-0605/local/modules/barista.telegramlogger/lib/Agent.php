<?php

namespace Barista\Telegramlogger;

use Barista\Telegramlogger\Orm\HistoryTable;
use Barista\Telegramlogger\Orm\QueueTable;
use Barista\Telegramlogger\Service\TelegramService;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ORM\Query\Filter;
use Bitrix\Main\Type\DateTime;

class Agent
{
    private const MAX_RETRIES = 3;
    private const LOCK_TIME_MIN = 5; // Time to lock item for processing

    public static function processQueue(): string
    {
        $moduleId = 'barista.telegramlogger';
        if (Option::get($moduleId, 'is_enabled', 'N') !== 'Y') {
            return self::getAgentName();
        }

        $botToken = Option::get($moduleId, 'bot_token', '');
        $chatId = Option::get($moduleId, 'chat_id', '');
        $silencePeriod = (int)Option::get($moduleId, 'silence_period_min', '5');

        if (empty($botToken) || empty($chatId)) {
            return self::getAgentName();
        }

        $items = self::getQueueItems();
        if (empty($items)) {
            return self::getAgentName();
        }

        $telegramService = new TelegramService($botToken, $chatId);

        foreach ($items as $item) {
            $eventData = unserialize($item['EVENT_DATA'], ['allowed_classes' => false]);
            if (!$eventData) {
                QueueTable::update($item['ID'], ['STATUS' => 'ERROR']);
                continue;
            }

            $eventHash = md5($eventData['AUDIT_TYPE_ID'] . $eventData['ITEM_ID']);
            if (self::isDuplicate($eventHash, $silencePeriod)) {
                QueueTable::update($item['ID'], ['STATUS' => 'SKIPPED']);
                continue;
            }

            $message = self::formatMessage($eventData);
            $isSent = $telegramService->sendMessage($message);

            if ($isSent) {
                QueueTable::update($item['ID'], ['STATUS' => 'SENT']);
                self::updateHistory($eventHash);
            } else {
                QueueTable::update($item['ID'], [
                    'STATUS' => 'FAILED',
                    'RETRY_COUNT' => $item['RETRY_COUNT'] + 1,
                ]);
            }
        }

        return self::getAgentName();
    }

    private static function getQueueItems(): array
    {
        $lockTime = (new DateTime())->add('-T' . self::LOCK_TIME_MIN . 'M');

        $query = QueueTable::query()
            ->setSelect(['ID', 'EVENT_DATA', 'RETRY_COUNT'])
            ->where('RETRY_COUNT', '<', self::MAX_RETRIES)
            ->where(
                (new Filter())
                    ->logic('or')
                    ->where('STATUS', '=', 'NEW')
                    ->where('STATUS', '=', 'PROCESSING')
                    ->where('TIMESTAMP_X', '<', $lockTime) // Unlock stale items
            )
            ->setLimit(20); // Process 20 items per run

        $items = $query->fetchAll();

        if (!empty($items)) {
            $itemIds = array_column($items, 'ID');
            QueueTable::updateMulti($itemIds, ['STATUS' => 'PROCESSING', 'TIMESTAMP_X' => new DateTime()]);
        }

        return $items;
    }

    private static function isDuplicate(string $eventHash, int $silencePeriod): bool
    {
        $historyItem = HistoryTable::query()
            ->setSelect(['LAST_SENT_TIMESTAMP_X'])
            ->where('EVENT_HASH', '=', $eventHash)
            ->fetch();

        if (!$historyItem) {
            return false;
        }

        $silenceUntil = (clone $historyItem['LAST_SENT_TIMESTAMP_X'])->add("{$silencePeriod} minutes");

        return $silenceUntil->getTimestamp() > (new DateTime())->getTimestamp();
    }

    private static function updateHistory(string $eventHash): void
    {
        $now = new DateTime();
        $historyItem = HistoryTable::query()
            ->setSelect(['ID'])
            ->where('EVENT_HASH', '=', $eventHash)
            ->fetch();

        if ($historyItem) {
            HistoryTable::update($historyItem['ID'], ['LAST_SENT_TIMESTAMP_X' => $now]);
        } else {
            HistoryTable::add(['EVENT_HASH' => $eventHash, 'LAST_SENT_TIMESTAMP_X' => $now]);
        }
    }

    private static function formatMessage(array $eventData): string
    {
        $severity = htmlspecialcharsbx($eventData['SEVERITY']);
        $auditType = htmlspecialcharsbx($eventData['AUDIT_TYPE_ID']);
        $module = htmlspecialcharsbx($eventData['MODULE_ID']);
        $item = htmlspecialcharsbx($eventData['ITEM_ID']);
        $description = strip_tags($eventData['DESCRIPTION']);

        return "<b>❗️ Security Event</b>\n\n"
            . "<b>Severity:</b> {$severity}\n"
            . "<b>Type:</b> {$auditType}\n"
            . "<b>Module:</b> {$module}\n"
            . "<b>Item:</b> {$item}\n\n"
            . "<i>{$description}</i>";
    }
    
    private static function getAgentName(): string
    {
        return '\\Barista\\Telegramlogger\\Agent::processQueue();';
    }
} 