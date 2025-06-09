<?php

namespace Barista\TelegramNotifier\Models;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

class NotificationTable extends Entity\DataManager
{
    public static function getTableName(): string
    {
        return 'barista_telegram_notifications';
    }

    public static function getMap(): array
    {
        return [
            new Entity\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
            ]),
            new Entity\StringField('EVENT_HASH', [
                'required' => true,
                'size' => 64,
            ]),
            new Entity\StringField('AUDIT_TYPE_ID', [
                'required' => true,
            ]),
            new Entity\StringField('ITEM_ID', [
                'required' => true,
            ]),
            new Entity\TextField('DESCRIPTION', [
                'required' => true,
            ]),
            new Entity\DatetimeField('CREATED_AT', [
                'default_value' => function() {
                    return new DateTime();
                },
            ]),
            new Entity\DatetimeField('SILENCE_UNTIL'),
        ];
    }

    public static function createEventHash(array $eventData): string
    {
        $hashData = [
            'AUDIT_TYPE_ID' => $eventData['AUDIT_TYPE_ID'] ?? '',
            'ITEM_ID' => $eventData['ITEM_ID'] ?? '',
            'DESCRIPTION' => $eventData['DESCRIPTION'] ?? '',
        ];

        return hash('sha256', serialize($hashData));
    }

    public static function isEventProcessed(string $eventHash): bool
    {
        $result = static::getList([
            'filter' => [
                'EVENT_HASH' => $eventHash,
                [
                    'LOGIC' => 'OR',
                    ['SILENCE_UNTIL' => null],
                    ['>SILENCE_UNTIL' => new DateTime()]
                ]
            ],
            'select' => ['ID'],
            'limit' => 1,
        ]);

        return (bool)$result->fetch();
    }

    public static function recordEvent(array $eventData, ?DateTime $silenceUntil = null): bool
    {
        $eventHash = static::createEventHash($eventData);

        if (static::isEventProcessed($eventHash)) {
            return false;
        }

        $result = static::add([
            'EVENT_HASH' => $eventHash,
            'AUDIT_TYPE_ID' => $eventData['AUDIT_TYPE_ID'] ?? '',
            'ITEM_ID' => $eventData['ITEM_ID'] ?? '',
            'DESCRIPTION' => $eventData['DESCRIPTION'] ?? '',
            'SILENCE_UNTIL' => $silenceUntil,
        ]);

        return $result->isSuccess();
    }

    public static function setSilenceMode(string $auditTypeId, int $silenceMinutes = 60): bool
    {
        $silenceUntil = new DateTime();
        $silenceUntil->add('+' . $silenceMinutes . ' minutes');

        $result = static::getList([
            'filter' => ['AUDIT_TYPE_ID' => $auditTypeId],
            'select' => ['ID'],
        ]);

        $updated = 0;
        while ($row = $result->fetch()) {
            static::update($row['ID'], ['SILENCE_UNTIL' => $silenceUntil]);
            $updated++;
        }

        return $updated > 0;
    }

    public static function cleanExpiredSilence(): int
    {
        $result = static::getList([
            'filter' => [
                '!SILENCE_UNTIL' => null,
                '<=SILENCE_UNTIL' => new DateTime(),
            ],
            'select' => ['ID'],
        ]);

        $cleaned = 0;
        while ($row = $result->fetch()) {
            static::update($row['ID'], ['SILENCE_UNTIL' => null]);
            $cleaned++;
        }

        return $cleaned;
    }

    public static function cleanOldRecords(int $daysOld = 90): int
    {
        $date = new DateTime();
        $date->add('-' . $daysOld . ' days');

        $result = static::getList([
            'filter' => ['<=CREATED_AT' => $date],
            'select' => ['ID'],
        ]);

        $deleted = 0;
        while ($row = $result->fetch()) {
            static::delete($row['ID']);
            $deleted++;
        }

        return $deleted;
    }
} 