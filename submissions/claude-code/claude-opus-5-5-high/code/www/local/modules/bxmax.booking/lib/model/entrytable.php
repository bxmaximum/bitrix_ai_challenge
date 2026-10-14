<?php

declare(strict_types=1);

namespace Bxmax\Booking\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\Type\DateTime;

/**
 * Заявка на запись.
 *
 * Одна заявка на слот гарантируется уникальным индексом ux_bxmax_booking_entry_slot по SLOT_ID:
 * вторая вставка в тот же слот падает с DuplicateEntryException на уровне БД.
 */
final class EntryTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'bxmax_booking_entry';
    }

    public static function getMap(): array
    {
        return [
            (new Fields\IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete()
                ->configureTitle(Loc::getMessage('BXMAX_BOOKING_ENTRY_ID')),
            (new Fields\IntegerField('SLOT_ID'))
                ->configureRequired()
                ->configureTitle(Loc::getMessage('BXMAX_BOOKING_ENTRY_SLOT_ID')),
            (new Fields\IntegerField('SERVICE_ID'))
                ->configureRequired()
                ->configureTitle(Loc::getMessage('BXMAX_BOOKING_ENTRY_SERVICE_ID')),
            (new Fields\StringField('NAME'))
                ->configureRequired()
                ->configureSize(100)
                ->addValidator(new Fields\Validators\LengthValidator(1, 100))
                ->configureTitle(Loc::getMessage('BXMAX_BOOKING_ENTRY_NAME')),
            (new Fields\StringField('PHONE'))
                ->configureRequired()
                ->configureSize(20)
                ->addValidator(new Fields\Validators\LengthValidator(1, 20))
                ->configureTitle(Loc::getMessage('BXMAX_BOOKING_ENTRY_PHONE')),
            (new Fields\DatetimeField('CONSENT_AT'))
                ->configureRequired()
                ->configureTitle(Loc::getMessage('BXMAX_BOOKING_ENTRY_CONSENT_AT')),
            (new Fields\DatetimeField('CREATED_AT'))
                ->configureDefaultValue(static fn () => new DateTime())
                ->configureTitle(Loc::getMessage('BXMAX_BOOKING_ENTRY_CREATED_AT')),
            (new Fields\Relations\Reference('SLOT', SlotTable::class, ['=this.SLOT_ID' => 'ref.ID']))
                ->configureJoinType('INNER'),
        ];
    }
}
