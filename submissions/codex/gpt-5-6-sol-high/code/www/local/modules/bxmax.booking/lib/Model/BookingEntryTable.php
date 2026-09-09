<?php

declare(strict_types=1);

namespace Bxmax\Booking\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

final class BookingEntryTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'bxmax_booking_entry';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))->configurePrimary()->configureAutocomplete(),
            (new IntegerField('SLOT_ID'))->configureRequired(),
            (new IntegerField('SERVICE_ID'))->configureRequired(),
            (new StringField('NAME'))
                ->configureRequired()
                ->configureSize(120)
                ->addValidator(new LengthValidator(2, 120)),
            (new StringField('PHONE'))
                ->configureRequired()
                ->configureSize(32)
                ->addValidator(new LengthValidator(7, 32)),
            (new DatetimeField('CONSENT_AT'))->configureRequired(),
        ];
    }
}
