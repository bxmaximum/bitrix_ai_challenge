<?php

namespace Bxmax\Booking\Entity;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Query\Join;

/**
 * Client booking. One entry per slot is enforced by a unique index on SLOT_ID
 * (see install/db/mysql/install.sql), so a slot can never be double-booked.
 *
 * Contract columns: ID, SLOT_ID, SERVICE_ID, NAME, PHONE, CONSENT_AT.
 * MASTER_ID is a snapshot of the slot owner and makes the admin list cheap.
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
			(new IntegerField('ID'))
				->configurePrimary(true)
				->configureAutocomplete(true),
			(new IntegerField('SLOT_ID'))
				->configureRequired(true),
			(new IntegerField('MASTER_ID'))
				->configureRequired(true),
			(new IntegerField('SERVICE_ID'))
				->configureRequired(true),
			(new StringField('NAME'))
				->configureRequired(true)
				->configureSize(255),
			(new StringField('PHONE'))
				->configureRequired(true)
				->configureSize(32),
			(new DatetimeField('CONSENT_AT'))
				->configureRequired(true),
			new DatetimeField('CREATED_AT'),
			(new Reference('SLOT', SlotTable::class, Join::on('this.SLOT_ID', 'ref.ID')))
				->configureJoinType('inner'),
		];
	}
}
