<?php

declare(strict_types=1);

namespace Bxmax\Booking\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

final class SlotTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'bxmax_booking_slot';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new IntegerField('MASTER_ID'))
                ->configureRequired(),

            (new DatetimeField('STARTS_AT'))
                ->configureRequired(),

            (new DatetimeField('ENDS_AT'))
                ->configureRequired(),

            (new BooleanField('IS_CLOSED'))
                ->configureValues('N', 'Y')
                ->configureDefaultValue('N'),

            (new Reference(
                'ENTRY',
                EntryTable::class,
                Join::on('this.ID', 'ref.SLOT_ID'),
            ))->configureJoinType('LEFT'),
        ];
    }
}
