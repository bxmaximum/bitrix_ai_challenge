<?php

declare(strict_types=1);

namespace Vendor\Favorites\Repository;

use Vendor\Favorites\Model\FavoritesTable;

/**
 * Доступ к данным избранного в БД.
 */
final class FavoritesRepository
{
    public function add(int $userId, int $productId): bool
    {
        $exists = FavoritesTable::getList([
            'filter' => [
                '=USER_ID' => $userId,
                '=PRODUCT_ID' => $productId,
            ],
            'select' => ['ID'],
            'limit' => 1,
        ])->fetch();

        if ($exists) {
            return true;
        }

        $r = FavoritesTable::add([
            'USER_ID' => $userId,
            'PRODUCT_ID' => $productId,
        ]);

        return $r->isSuccess();
    }

    public function remove(int $userId, int $productId): bool
    {
        $row = FavoritesTable::getList([
            'filter' => [
                '=USER_ID' => $userId,
                '=PRODUCT_ID' => $productId,
            ],
            'select' => ['ID'],
            'limit' => 1,
        ])->fetch();

        if (!$row) {
            return true;
        }

        $r = FavoritesTable::delete($row['ID']);

        return $r->isSuccess();
    }

    /**
     * @return list<int>
     */
    public function getProductIdsForUser(int $userId): array
    {
        $res = FavoritesTable::getList([
            'filter' => ['=USER_ID' => $userId],
            'select' => ['PRODUCT_ID'],
            'order' => ['ID' => 'DESC'],
        ]);

        $ids = [];
        while ($row = $res->fetch()) {
            $ids[] = (int) $row['PRODUCT_ID'];
        }

        return $ids;
    }

    public function deleteByProductId(int $productId): void
    {
        $res = FavoritesTable::getList([
            'filter' => ['=PRODUCT_ID' => $productId],
            'select' => ['ID'],
        ]);
        while ($row = $res->fetch()) {
            FavoritesTable::delete((int) $row['ID']);
        }
    }

    /**
     * Добавляет ID без дубликатов.
     *
     * @param list<int> $productIds
     */
    public function mergeUnique(int $userId, array $productIds): void
    {
        $existing = array_flip($this->getProductIdsForUser($userId));

        foreach ($productIds as $pid) {
            $pid = (int) $pid;
            if ($pid <= 0 || isset($existing[$pid])) {
                continue;
            }
            if ($this->add($userId, $pid)) {
                $existing[$pid] = true;
            }
        }
    }
}
