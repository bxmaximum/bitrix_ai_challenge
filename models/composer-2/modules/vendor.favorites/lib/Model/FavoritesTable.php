<?php

declare(strict_types=1);

namespace Vendor\Favorites\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Validators\RangeValidator;
use Bitrix\Main\Type\DateTime;

/**
 * ORM-сущность таблицы избранного (авторизованные пользователи).
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
                ->addValidator(new RangeValidator(1, null)),
            (new IntegerField('PRODUCT_ID'))
                ->configureRequired(true)
                ->addValidator(new RangeValidator(1, null)),
            new DatetimeField('DATE_INSERT'),
        ];
    }

    public static function add(array $fields)
    {
        if (!isset($fields['DATE_INSERT'])) {
            $fields['DATE_INSERT'] = new DateTime();
        }

        return parent::add($fields);
    }
}
