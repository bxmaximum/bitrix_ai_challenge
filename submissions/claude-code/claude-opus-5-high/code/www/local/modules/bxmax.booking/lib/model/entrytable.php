<?php

declare(strict_types=1);

namespace Bxmax\Booking\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

/**
 * Заявка на запись: один слот — одна заявка (гарантируется UNIQUE-индексом по SLOT_ID).
 *
 * @method static \Bitrix\Main\ORM\Query\Query query()
 */
final class EntryTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'bxmax_booking_entry';
	}

	public static function getMap(): array
	{
		Loc::loadMessages(__FILE__);

		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle(Loc::getMessage('BXMAX_BOOKING_ENTRY_FIELD_ID')),

			(new IntegerField('SLOT_ID'))
				->configureRequired()
				->configureUnique()
				->configureTitle(Loc::getMessage('BXMAX_BOOKING_ENTRY_FIELD_SLOT_ID')),

			(new IntegerField('SERVICE_ID'))
				->configureRequired()
				->configureTitle(Loc::getMessage('BXMAX_BOOKING_ENTRY_FIELD_SERVICE_ID')),

			(new StringField('NAME'))
				->configureRequired()
				->addValidator(new LengthValidator(2, 120))
				->configureTitle(Loc::getMessage('BXMAX_BOOKING_ENTRY_FIELD_NAME')),

			(new StringField('PHONE'))
				->configureRequired()
				->addValidator(new LengthValidator(5, 32))
				->configureTitle(Loc::getMessage('BXMAX_BOOKING_ENTRY_FIELD_PHONE')),

			(new DatetimeField('CONSENT_AT'))
				->configureRequired()
				->configureTitle(Loc::getMessage('BXMAX_BOOKING_ENTRY_FIELD_CONSENT_AT')),

			(new DatetimeField('CREATED_AT'))
				->configureRequired()
				->configureDefaultValue(static fn(): DateTime => new DateTime())
				->configureTitle(Loc::getMessage('BXMAX_BOOKING_ENTRY_FIELD_CREATED_AT')),

			(new Reference(
				'SLOT',
				SlotTable::class,
				Join::on('this.SLOT_ID', 'ref.ID')
			))->configureJoinType(Join::TYPE_INNER),
		];
	}
}
