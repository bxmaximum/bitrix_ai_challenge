<?php declare(strict_types=1);

namespace Bxmax\Booking\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\Type\DateTime;

final class BookingEntryTable extends DataManager
{
    use DeleteByFilterTrait;

    public static function getTableName(): string
    {
        return 'bxmax_booking_entry';
    }

    public static function getMap(): array
    {
        return [
            (new Fields\IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new Fields\IntegerField('SLOT_ID'))
                ->configureRequired(),
            (new Fields\IntegerField('SERVICE_ID'))
                ->configureRequired(),
            (new Fields\StringField('NAME'))
                ->configureRequired()
                ->configureSize(255)
                ->addValidator(new Fields\Validators\LengthValidator(1, 255)),
            (new Fields\StringField('PHONE'))
                ->configureRequired()
                ->configureSize(64)
                ->addValidator(new Fields\Validators\LengthValidator(3, 64)),
            (new Fields\DatetimeField('CONSENT_AT'))
                ->configureRequired(),
            (new Fields\DatetimeField('CREATED_AT'))
                ->configureDefaultValue(static fn (): DateTime => new DateTime()),
            (new Fields\Relations\Reference(
                'SLOT',
                SlotTable::class,
                ['=this.SLOT_ID' => 'ref.ID']
            ))->configureJoinType('LEFT'),
        ];
    }
}
