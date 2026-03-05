<?php
declare(strict_types=1);

namespace Vendor\Favorites\Repository;

use Bitrix\Main\Application;
use RuntimeException;
use Vendor\Favorites\Model\FavoritesTable;

/**
 * Encapsulates all ORM operations for favorites storage.
 */
final class FavoritesRepository
{
    /**
     * Checks whether a product is already in user's favorites.
     */
    public function exists(int $userId, int $productId): bool
    {
        return FavoritesTable::getCount([
            '=USER_ID' => $userId,
            '=PRODUCT_ID' => $productId,
        ]) > 0;
    }

    /**
     * Adds product to favorites if there is no duplicate yet.
     */
    public function add(int $userId, int $productId): void
    {
        if ($this->exists($userId, $productId)) {
            return;
        }

        $result = FavoritesTable::add([
            'USER_ID' => $userId,
            'PRODUCT_ID' => $productId,
        ]);

        if (!$result->isSuccess()) {
            throw new RuntimeException(implode('; ', $result->getErrorMessages()));
        }
    }

    /**
     * Removes product from user's favorites.
     */
    public function remove(int $userId, int $productId): void
    {
        $rows = FavoritesTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=USER_ID' => $userId,
                '=PRODUCT_ID' => $productId,
            ],
        ]);

        while ($row = $rows->fetch()) {
            FavoritesTable::delete((int) $row['ID']);
        }
    }

    /**
     * Returns favorite product ids for user ordered from recent to old.
     *
     * @return int[]
     */
    public function getProductIdsByUserId(int $userId): array
    {
        $rows = FavoritesTable::getList([
            'select' => ['PRODUCT_ID'],
            'filter' => ['=USER_ID' => $userId],
            'order' => [
                'CREATED_AT' => 'DESC',
                'ID' => 'DESC',
            ],
        ])->fetchAll();

        return array_map(
            static fn(array $row): int => (int) $row['PRODUCT_ID'],
            $rows
        );
    }

    /**
     * Merges guest favorites into authorized storage without duplicates.
     *
     * @param int[] $productIds
     */
    public function mergeUserFavorites(int $userId, array $productIds): void
    {
        if ($productIds === []) {
            return;
        }

        $connection = Application::getConnection();
        $connection->startTransaction();

        try {
            foreach (array_unique($productIds) as $productId) {
                $normalizedId = (int) $productId;
                if ($normalizedId < 1) {
                    continue;
                }

                $this->add($userId, $normalizedId);
            }

            $connection->commitTransaction();
        } catch (\Throwable $exception) {
            $connection->rollbackTransaction();
            throw $exception;
        }
    }

    /**
     * Deletes product from favorites of all users.
     */
    public function deleteByProductId(int $productId): void
    {
        $rows = FavoritesTable::getList([
            'select' => ['ID'],
            'filter' => ['=PRODUCT_ID' => $productId],
        ]);

        while ($row = $rows->fetch()) {
            FavoritesTable::delete((int) $row['ID']);
        }
    }

    /**
     * Returns total number of database favorites for the product.
     */
    public function countByProductId(int $productId): int
    {
        return FavoritesTable::getCount([
            '=PRODUCT_ID' => $productId,
        ]);
    }
}
