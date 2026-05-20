<?php

declare(strict_types=1);

namespace Vendor\Favorites\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Validators\RangeValidator;
use Bitrix\Main\Type\DateTime;

/**
 * ORM-таблица избранных товаров авторизованных пользователей.
 */
final class FavoritesTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_vendor_favorites';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),

            (new IntegerField('USER_ID'))
                ->configureRequired(true)
                ->addValidator(new RangeValidator(1)),

            (new IntegerField('PRODUCT_ID'))
                ->configureRequired(true)
                ->addValidator(new RangeValidator(1)),

            (new DatetimeField('DATE_CREATE'))
                ->configureRequired(true)
                ->configureDefaultValue(static fn () => new DateTime()),
        ];
    }
}
