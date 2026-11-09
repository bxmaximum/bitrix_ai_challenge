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

/**
 * Заявка клиента на слот. SLOT_ID уникален на уровне БД: один слот — одна запись.
 */
class EntryTable extends DataManager
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
				->configureRequired(),
			(new IntegerField('SERVICE_ID'))
				->configureRequired(),
			(new StringField('NAME'))
				->configureRequired()
				->addValidator(new LengthValidator(1, 100)),
			(new StringField('PHONE'))
				->configureRequired()
				->addValidator(new LengthValidator(1, 20)),
			(new DatetimeField('CONSENT_AT'))
				->configureRequired(),
			(new Reference('SLOT', SlotTable::class, Join::on('this.SLOT_ID', 'ref.ID')))
				->configureJoinType(Join::TYPE_INNER),
		];
	}
}
