<?php

declare(strict_types=1);

namespace Vendor\Favorites\Repository;

use Vendor\Favorites\Model\FavoritesTable;
use Bitrix\Main\ORM\Query\Query;

class FavoritesRepository
{
    /**
     * @param int $userId
     * @return int[]
     */
    public function getByUserId(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $result = FavoritesTable::getList([
            'select' => ['PRODUCT_ID'],
            'filter' => ['=USER_ID' => $userId],
            'cache' => ['ttl' => 3600, 'cache_joins' => true]
        ]);

        $ids = [];
        while ($row = $result->fetch()) {
            $ids[] = (int)$row['PRODUCT_ID'];
        }

        return $ids;
    }

    public function add(int $userId, int $productId): bool
    {
        $result = FavoritesTable::add([
            'USER_ID' => $userId,
            'PRODUCT_ID' => $productId,
        ]);

        return $result->isSuccess();
    }

    public function remove(int $userId, int $productId): bool
    {
        $res = FavoritesTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=USER_ID' => $userId,
                '=PRODUCT_ID' => $productId,
            ],
        ]);

        if ($row = $res->fetch()) {
            $result = FavoritesTable::delete($row['ID']);
            return $result->isSuccess();
        }

        return false;
    }

    public function removeByProductId(int $productId): void
    {
        $res = FavoritesTable::getList([
            'select' => ['ID'],
            'filter' => ['=PRODUCT_ID' => $productId],
        ]);

        while ($row = $res->fetch()) {
            FavoritesTable::delete($row['ID']);
        }
    }

    public function exists(int $userId, int $productId): bool
    {
        $res = FavoritesTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=USER_ID' => $userId,
                '=PRODUCT_ID' => $productId,
            ],
            'limit' => 1,
        ]);

        return (bool)$res->fetch();
    }
}

