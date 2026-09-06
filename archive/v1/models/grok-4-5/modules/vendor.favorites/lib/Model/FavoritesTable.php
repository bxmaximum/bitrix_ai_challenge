<?php

declare(strict_types=1);

namespace Vendor\Favorites\Model;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\Validators\ForeignValidator;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;

/**
 * ORM table for authorized users' favorites.
 *
 * Fields:
 * <ul>
 * <li> ID int primary
 * <li> USER_ID int
 * <li> PRODUCT_ID int (iblock element id)
 * <li> DATE_CREATE datetime
 * </ul>
 */
final class FavoritesTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_vendor_favorites';
	}

	public static function getMap(): array
	{
		if (!Loader::includeModule('iblock'))
		{
			throw new SystemException('Module iblock is required for vendor.favorites');
		}

		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new IntegerField('USER_ID'))
				->configureRequired()
				->addValidator(new ForeignValidator(UserTable::getEntity()->getField('ID'))),

			(new IntegerField('PRODUCT_ID'))
				->configureRequired()
				->addValidator(new ForeignValidator(ElementTable::getEntity()->getField('ID'))),

			(new DatetimeField('DATE_CREATE'))
				->configureRequired()
				->configureDefaultValue(static fn (): DateTime => new DateTime()),

			(new Reference(
				'USER',
				UserTable::class,
				Join::on('this.USER_ID', 'ref.ID')
			))->configureJoinType(Join::TYPE_INNER),

			(new Reference(
				'PRODUCT',
				ElementTable::class,
				Join::on('this.PRODUCT_ID', 'ref.ID')
			))->configureJoinType(Join::TYPE_INNER),
		];
	}

	public static function isCacheable(): bool
	{
		return true;
	}
}
