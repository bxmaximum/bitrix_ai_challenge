<?php

namespace Barista\Telegramlogger\Orm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\Type\DateTime;

class QueueTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'barista_telegramlogger_queue';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->setPrimary(true)
                ->setAutocomplete(true),

            (new DatetimeField('TIMESTAMP_X'))
                ->setRequired(true)
                ->setDefaultValue(new DateTime()),

            (new IntegerField('EVENT_ID'))
                ->setRequired(true),

            (new TextField('EVENT_DATA'))
                ->setRequired(true),

            (new StringField('STATUS'))
                ->setRequired(true)
                ->setDefaultValue('NEW'),

            (new IntegerField('RETRY_COUNT'))
                ->setRequired(true)
                ->setDefaultValue(0),
        ];
    }
} 