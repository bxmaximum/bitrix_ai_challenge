<?php

declare(strict_types=1);

namespace Vendor\Favorites\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\Type\DateTime;

/**
 * ORM-таблет избранных товаров.
 *
 * Таблица: vendor_favorites
 *
 * Поля:
 * - ID         int, primary, autoincrement
 * - USER_ID    int — ID авторизованного пользователя
 * - PRODUCT_ID int — ID элемента инфоблока (товара)
 * - CREATED_AT datetime — дата добавления
 *
 * Уникальность пары (USER_ID, PRODUCT_ID) обеспечивается индексом,
 * создаваемым при установке модуля.
 */
final class FavoritesTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'vendor_favorites';
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

            (new Fields\Relations\Reference(
                'USER',
                \Bitrix\Main\UserTable::class,
                ['=this.USER_ID' => 'ref.ID'],
            ))->configureJoinType('LEFT'),
        ];
    }
}
