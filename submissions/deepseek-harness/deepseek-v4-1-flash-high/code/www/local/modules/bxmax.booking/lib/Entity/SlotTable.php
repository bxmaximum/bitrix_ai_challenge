<?php

namespace Bxmax\Booking\Entity;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Slot of the studio schedule: one master, one hour.
 *
 * Contract columns: ID, MASTER_ID, STARTS_AT, ENDS_AT.
 * CLOSED is an administrative flag: a closed slot is never bookable and is
 * reported as "taken" by the public API.
 */
final class SlotTable extends DataManager
{
	public const CLOSED_YES = 'Y';
	public const CLOSED_NO = 'N';

	public static function getTableName(): string
	{
		return 'bxmax_booking_slot';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary(true)
				->configureAutocomplete(true),
			(new IntegerField('MASTER_ID'))
				->configureRequired(true)
				->addValidator(new LengthValidator(1)),
			(new DatetimeField('STARTS_AT'))
				->configureRequired(true),
			(new DatetimeField('ENDS_AT'))
				->configureRequired(true),
			(new BooleanField('CLOSED'))
				->configureValues(self::CLOSED_NO, self::CLOSED_YES)
				->configureDefaultValue(self::CLOSED_NO),
			new DatetimeField('CREATED_AT'),
		];
	}
}
