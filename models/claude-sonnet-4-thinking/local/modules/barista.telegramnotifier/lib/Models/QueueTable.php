<?php

namespace Barista\TelegramNotifier\Models;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

class QueueTable extends Entity\DataManager
{
    public static function getTableName(): string
    {
        return 'barista_telegram_queue';
    }

    public static function getMap(): array
    {
        return [
            new Entity\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
            ]),
            new Entity\StringField('EVENT_ID', [
                'required' => true,
                'validation' => [__CLASS__, 'validateEventId'],
            ]),
            new Entity\StringField('CHAT_ID', [
                'required' => true,
                'validation' => [__CLASS__, 'validateChatId'],
            ]),
            new Entity\TextField('MESSAGE', [
                'required' => true,
            ]),
            new Entity\IntegerField('ATTEMPTS', [
                'default_value' => 0,
            ]),
            new Entity\EnumField('STATUS', [
                'values' => ['PENDING', 'PROCESSING', 'SENT', 'FAILED'],
                'default_value' => 'PENDING',
            ]),
            new Entity\DatetimeField('CREATED_AT', [
                'default_value' => function() {
                    return new DateTime();
                },
            ]),
            new Entity\DatetimeField('UPDATED_AT', [
                'default_value' => function() {
                    return new DateTime();
                },
            ]),
            new Entity\DatetimeField('SCHEDULED_AT'),
            new Entity\TextField('ERROR_MESSAGE'),
        ];
    }

    public static function validateEventId(): array
    {
        return [
            function($value) {
                if (strlen($value) > 255) {
                    return 'EVENT_ID слишком длинный';
                }
                return true;
            }
        ];
    }

    public static function validateChatId(): array
    {
        return [
            function($value) {
                if (strlen($value) > 255) {
                    return 'CHAT_ID слишком длинный';
                }
                if (!preg_match('/^-?\d+$/', $value)) {
                    return 'CHAT_ID должен быть числом';
                }
                return true;
            }
        ];
    }

    public static function getNextPendingJobs(int $limit = 10): array
    {
        $result = static::getList([
            'filter' => [
                'STATUS' => 'PENDING',
                [
                    'LOGIC' => 'OR',
                    ['SCHEDULED_AT' => null],
                    ['<=SCHEDULED_AT' => new DateTime()]
                ]
            ],
            'order' => ['ID' => 'ASC'],
            'limit' => $limit,
        ]);

        return $result->fetchAll();
    }

    public static function markAsProcessing(int $id): bool
    {
        $result = static::update($id, [
            'STATUS' => 'PROCESSING',
            'UPDATED_AT' => new DateTime(),
        ]);

        return $result->isSuccess();
    }

    public static function markAsSent(int $id): bool
    {
        $result = static::update($id, [
            'STATUS' => 'SENT',
            'UPDATED_AT' => new DateTime(),
        ]);

        return $result->isSuccess();
    }

    public static function markAsFailed(int $id, string $error = '', bool $retry = true): bool
    {
        $current = static::getById($id)->fetch();
        if (!$current) {
            return false;
        }

        $attempts = (int)$current['ATTEMPTS'] + 1;
        $maxAttempts = 5;

        $data = [
            'ATTEMPTS' => $attempts,
            'UPDATED_AT' => new DateTime(),
            'ERROR_MESSAGE' => $error,
        ];

        if (!$retry || $attempts >= $maxAttempts) {
            $data['STATUS'] = 'FAILED';
        } else {
            $data['STATUS'] = 'PENDING';
            $data['SCHEDULED_AT'] = new DateTime(time() + (60 * pow(2, $attempts - 1)));
        }

        $result = static::update($id, $data);
        return $result->isSuccess();
    }

    public static function cleanOldRecords(int $daysOld = 30): int
    {
        $date = new DateTime();
        $date->add('-' . $daysOld . ' days');

        $result = static::getList([
            'filter' => [
                '<=CREATED_AT' => $date,
                ['LOGIC' => 'OR', 'STATUS' => 'SENT', 'STATUS' => 'FAILED']
            ],
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