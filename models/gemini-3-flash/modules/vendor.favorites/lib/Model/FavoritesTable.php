<?php

declare(strict_types=1);

namespace Vendor\Favorites\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\Type\DateTime;

/**
 * Class FavoritesTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int mandatory
 * <li> PRODUCT_ID int mandatory
 * <li> DATE_CREATE datetime optional default current datetime
 * </ul>
 *
 * @package Vendor\Favorites\Model
 **/

class FavoritesTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'v_favorites';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap(): array
    {
        return [
            new IntegerField(
                'ID',
                [
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => 'ID',
                ]
            ),
            new IntegerField(
                'USER_ID',
                [
                    'required' => true,
                    'title' => 'User ID',
                ]
            ),
            new IntegerField(
                'PRODUCT_ID',
                [
                    'required' => true,
                    'title' => 'Product ID',
                ]
            ),
            new DatetimeField(
                'DATE_CREATE',
                [
                    'default_value' => function() {
                        return new DateTime();
                    },
                    'title' => 'Date Create',
                ]
            ),
        ];
    }
}

