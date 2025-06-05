<?php

namespace Barista\Telegramlogger\Orm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

class HistoryTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'barista_telegramlogger_history';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->setPrimary(true)
                ->setAutocomplete(true),

            (new StringField('EVENT_HASH'))
                ->setRequired(true),

            (new DatetimeField('LAST_SENT_TIMESTAMP_X'))
                ->setRequired(true)
                ->setDefaultValue(new DateTime()),
        ];
    }
} 