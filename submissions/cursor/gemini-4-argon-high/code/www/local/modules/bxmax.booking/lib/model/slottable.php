<?php declare(strict_types=1);

namespace Bxmax\Booking\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\Type\DateTime;

final class SlotTable extends DataManager
{
    use DeleteByFilterTrait;

    public static function getTableName(): string
    {
        return 'bxmax_booking_slot';
    }

    public static function getMap(): array
    {
        return [
            (new Fields\IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new Fields\IntegerField('MASTER_ID'))
                ->configureRequired(),
            (new Fields\DatetimeField('STARTS_AT'))
                ->configureRequired(),
            (new Fields\DatetimeField('ENDS_AT'))
                ->configureRequired(),
            (new Fields\BooleanField('IS_CLOSED'))
                ->configureValues('N', 'Y')
                ->configureDefaultValue('N'),
            (new Fields\DatetimeField('CREATED_AT'))
                ->configureDefaultValue(static fn (): DateTime => new DateTime()),
            (new Fields\Relations\Reference(
                'ENTRY',
                BookingEntryTable::class,
                ['=this.ID' => 'ref.SLOT_ID']
            ))->configureJoinType('LEFT'),
        ];
    }
}
