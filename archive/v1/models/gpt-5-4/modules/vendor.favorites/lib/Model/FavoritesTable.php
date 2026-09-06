<?php
declare(strict_types=1);

namespace Vendor\Favorites\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\Validators\RangeValidator;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;

/**
 * Stores favorites for authorized users.
 */
final class FavoritesTable extends DataManager
{
    /**
     * Returns database table name.
     */
    public static function getTableName(): string
    {
        return 'vendor_favorites_item';
    }

    /**
     * Returns ORM entity field map.
     *
     * @return array<int, object>
     */
    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new IntegerField('USER_ID'))
                ->configureRequired()
                ->addValidator(new RangeValidator(1, null)),
            (new IntegerField('PRODUCT_ID'))
                ->configureRequired()
                ->addValidator(new RangeValidator(1, null)),
            (new DatetimeField('CREATED_AT'))
                ->configureDefaultValue(static fn(): DateTime => new DateTime()),
            new Reference(
                'USER',
                UserTable::class,
                ['=this.USER_ID' => 'ref.ID']
            ),
        ];
    }
}
