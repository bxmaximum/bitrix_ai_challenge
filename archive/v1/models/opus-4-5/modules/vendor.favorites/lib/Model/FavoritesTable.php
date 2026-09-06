<?php

declare(strict_types=1);

namespace Vendor\Favorites\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

/**
 * ORM-таблица избранных товаров
 *
 * Хранит связь между пользователями и их избранными товарами.
 * Использует уникальный индекс для предотвращения дубликатов.
 *
 * @package Vendor\Favorites\Model
 */
class FavoritesTable extends DataManager
{
    /**
     * Возвращает имя таблицы в БД
     */
    public static function getTableName(): string
    {
        return 'vendor_favorites';
    }

    /**
     * Возвращает карту полей сущности
     */
    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new IntegerField('USER_ID'))
                ->configureRequired(),

            (new IntegerField('PRODUCT_ID'))
                ->configureRequired(),

            (new IntegerField('IBLOCK_ID'))
                ->configureRequired(),

            (new DatetimeField('DATE_CREATED'))
                ->configureRequired()
                ->configureDefaultValue(static fn() => new DateTime()),

            // Связь с таблицей пользователей
            new Reference(
                'USER',
                \Bitrix\Main\UserTable::class,
                Join::on('this.USER_ID', 'ref.ID')
            ),
        ];
    }

    /**
     * Проверяет, находится ли товар в избранном пользователя
     */
    public static function isProductInFavorites(int $userId, int $productId): bool
    {
        $item = static::getRow([
            'filter' => [
                '=USER_ID' => $userId,
                '=PRODUCT_ID' => $productId,
            ],
            'select' => ['ID'],
            'cache' => ['ttl' => 3600],
        ]);

        return $item !== null;
    }

    /**
     * Возвращает список ID избранных товаров пользователя
     *
     * @return int[]
     */
    public static function getProductIdsByUser(int $userId): array
    {
        $result = static::getList([
            'filter' => ['=USER_ID' => $userId],
            'select' => ['PRODUCT_ID'],
            'cache' => ['ttl' => 3600],
        ]);

        $productIds = [];
        while ($row = $result->fetch()) {
            $productIds[] = (int)$row['PRODUCT_ID'];
        }

        return $productIds;
    }

    /**
     * Добавляет товар в избранное
     */
    public static function addFavorite(int $userId, int $productId, int $iblockId): \Bitrix\Main\ORM\Data\AddResult
    {
        return static::add([
            'USER_ID' => $userId,
            'PRODUCT_ID' => $productId,
            'IBLOCK_ID' => $iblockId,
            'DATE_CREATED' => new DateTime(),
        ]);
    }

    /**
     * Удаляет товар из избранного
     */
    public static function removeFavorite(int $userId, int $productId): bool
    {
        $item = static::getRow([
            'filter' => [
                '=USER_ID' => $userId,
                '=PRODUCT_ID' => $productId,
            ],
            'select' => ['ID'],
        ]);

        if ($item) {
            $result = static::delete($item['ID']);
            return $result->isSuccess();
        }

        return false;
    }

    /**
     * Удаляет все записи для указанного товара (при удалении товара из инфоблока)
     */
    public static function removeByProductId(int $productId): int
    {
        $connection = \Bitrix\Main\Application::getConnection();
        $tableName = static::getTableName();

        $connection->queryExecute(
            "DELETE FROM {$tableName} WHERE PRODUCT_ID = " . (int)$productId
        );

        return $connection->getAffectedRowsCount();
    }

    /**
     * Получает количество добавлений товара в избранное (счётчик популярности)
     */
    public static function getProductFavoritesCount(int $productId): int
    {
        $result = static::getList([
            'filter' => ['=PRODUCT_ID' => $productId],
            'select' => ['CNT'],
            'runtime' => [
                new \Bitrix\Main\ORM\Fields\ExpressionField('CNT', 'COUNT(*)'),
            ],
        ])->fetch();

        return (int)($result['CNT'] ?? 0);
    }
}





