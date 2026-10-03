<?php

declare(strict_types=1);

namespace Bxmax\Booking\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;

/**
 * Слот расписания мастера. Занятость слота определяется наличием записи в EntryTable,
 * закрытие слота администратором — флагом IS_CLOSED.
 */
final class SlotTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'bxmax_booking_slot';
    }

    public static function getMap(): array
    {
        return [
            (new Fields\IntegerField('ID'))->configurePrimary()->configureAutocomplete(),
            (new Fields\IntegerField('MASTER_ID'))->configureRequired(),
            (new Fields\DatetimeField('STARTS_AT'))->configureRequired(),
            (new Fields\DatetimeField('ENDS_AT'))->configureRequired(),
            (new Fields\BooleanField('IS_CLOSED'))->configureValues('N', 'Y')->configureDefaultValue('N'),
            (new Fields\Relations\Reference(
                'ENTRY',
                EntryTable::class,
                ['=this.ID' => 'ref.SLOT_ID'],
            ))->configureJoinType('LEFT'),
        ];
    }
}
