<?php

declare(strict_types=1);

namespace Bxmax\Booking\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

/**
 * Слот расписания мастера.
 *
 * @method static \Bitrix\Main\ORM\Query\Query query()
 */
final class SlotTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'bxmax_booking_slot';
	}

	public static function getMap(): array
	{
		Loc::loadMessages(__FILE__);

		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle(Loc::getMessage('BXMAX_BOOKING_SLOT_FIELD_ID')),

			(new IntegerField('MASTER_ID'))
				->configureRequired()
				->configureTitle(Loc::getMessage('BXMAX_BOOKING_SLOT_FIELD_MASTER_ID')),

			(new DatetimeField('STARTS_AT'))
				->configureRequired()
				->configureTitle(Loc::getMessage('BXMAX_BOOKING_SLOT_FIELD_STARTS_AT')),

			(new DatetimeField('ENDS_AT'))
				->configureRequired()
				->configureTitle(Loc::getMessage('BXMAX_BOOKING_SLOT_FIELD_ENDS_AT')),

			(new BooleanField('IS_CLOSED'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(false)
				->configureTitle(Loc::getMessage('BXMAX_BOOKING_SLOT_FIELD_IS_CLOSED')),

			(new DatetimeField('CREATED_AT'))
				->configureRequired()
				->configureDefaultValue(static fn(): DateTime => new DateTime())
				->configureTitle(Loc::getMessage('BXMAX_BOOKING_SLOT_FIELD_CREATED_AT')),

			(new Reference(
				'ENTRY',
				EntryTable::class,
				Join::on('this.ID', 'ref.SLOT_ID')
			))->configureJoinType(Join::TYPE_LEFT),
		];
	}
}
