<?php

namespace Barista\Telegramlogger;

use Barista\Telegramlogger\Orm\QueueTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;

class EventHandler
{
    public static function handleEventLog(array $event): void
    {
        $moduleId = 'barista.telegramlogger';
        
        if (Option::get($moduleId, 'is_enabled', 'N') !== 'Y') {
            return;
        }

        // We are interested only in high-level security events
        if ($event['SEVERITY'] !== 'SECURITY') {
            return;
        }

        QueueTable::add([
            'TIMESTAMP_X' => new DateTime(),
            'EVENT_ID' => (int)$event['ID'],
            'EVENT_DATA' => serialize($event),
        ]);
    }
} 