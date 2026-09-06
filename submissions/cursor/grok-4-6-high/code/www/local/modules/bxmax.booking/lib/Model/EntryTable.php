<?php

declare(strict_types=1);

namespace Bxmax\Booking\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;

final class EntryTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'bxmax_booking_entry';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new IntegerField('SLOT_ID'))
                ->configureRequired()
                ->configureUnique(),

            (new IntegerField('SERVICE_ID'))
                ->configureRequired(),

            (new StringField('NAME'))
                ->configureRequired()
                ->configureSize(255)
                ->addValidator(new LengthValidator(2, 255)),

            (new StringField('PHONE'))
                ->configureRequired()
                ->configureSize(32)
                ->addValidator(new LengthValidator(5, 32)),

            (new DatetimeField('CONSENT_AT'))
                ->configureRequired(),

            (new Reference(
                'SLOT',
                SlotTable::class,
                Join::on('this.SLOT_ID', 'ref.ID'),
            ))->configureJoinType('INNER'),
        ];
    }
}
