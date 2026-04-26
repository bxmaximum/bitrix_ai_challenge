<?php

declare(strict_types=1);

namespace Vendor\Favorites\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

final class FavoritesTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'vendor_favorites_favorite';
    }

    public static function isCacheable(): bool
    {
        return true;
    }

    public static function getMap(): array
    {
        return [
            (new Fields\IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new Fields\IntegerField('USER_ID'))
                ->configureRequired(),

            (new Fields\IntegerField('PRODUCT_ID'))
                ->configureRequired(),

            (new Fields\DatetimeField('CREATED_AT'))
                ->configureRequired()
                ->configureDefaultValue(static fn (): DateTime => new DateTime()),

            (new Reference(
                'USER',
                \Bitrix\Main\UserTable::class,
                Join::on('this.USER_ID', 'ref.ID'),
            ))->configureJoinType('INNER'),
        ];
    }
}
