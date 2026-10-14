<?php

declare(strict_types=1);

namespace Bxmax\Booking\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;

/**
 * Слот расписания мастера.
 *
 * Уникальность пары (MASTER_ID, STARTS_AT) держит индекс ux_bxmax_booking_slot_master_start,
 * поэтому генерация расписания идемпотентна.
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
            (new Fields\IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete()
                ->configureTitle(Loc::getMessage('BXMAX_BOOKING_SLOT_ID')),
            (new Fields\IntegerField('MASTER_ID'))
                ->configureRequired()
                ->configureTitle(Loc::getMessage('BXMAX_BOOKING_SLOT_MASTER_ID')),
            (new Fields\DatetimeField('STARTS_AT'))
                ->configureRequired()
                ->configureTitle(Loc::getMessage('BXMAX_BOOKING_SLOT_STARTS_AT')),
            (new Fields\DatetimeField('ENDS_AT'))
                ->configureRequired()
                ->configureTitle(Loc::getMessage('BXMAX_BOOKING_SLOT_ENDS_AT')),
            (new Fields\BooleanField('IS_CLOSED'))
                ->configureValues('N', 'Y')
                ->configureDefaultValue('N')
                ->configureTitle(Loc::getMessage('BXMAX_BOOKING_SLOT_IS_CLOSED')),
            (new Fields\Relations\Reference('ENTRY', EntryTable::class, ['=this.ID' => 'ref.SLOT_ID']))
                ->configureJoinType('LEFT'),
        ];
    }
}
