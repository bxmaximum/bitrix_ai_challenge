<?php

declare(strict_types=1);

namespace Vendor\Favorites\Repository;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Result;
use Vendor\Favorites\Model\FavoritesTable;

final class FavoritesRepository
{
    private const CACHE_DIR = '/vendor/favorites/user';
    private const CACHE_TTL = 3600;

    /**
     * Adds a product to a user's favorites and keeps the operation idempotent.
     */
    public function add(int $userId, int $productId): Result
    {
        $existingId = $this->findId($userId, $productId);
        if ($existingId > 0) {
            return new Result();
        }

        $result = FavoritesTable::add([
            'USER_ID' => $userId,
            'PRODUCT_ID' => $productId,
        ]);

        if ($result->isSuccess()) {
            $this->clearUserCache($userId);
            $this->clearProductCache($productId);
        }

        return $result;
    }

    public function remove(int $userId, int $productId): Result
    {
        $result = new Result();
        $rows = FavoritesTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=USER_ID' => $userId,
                '=PRODUCT_ID' => $productId,
            ],
        ]);

        while ($row = $rows->fetch()) {
            $deleteResult = FavoritesTable::delete((int)$row['ID']);
            if (!$deleteResult->isSuccess()) {
                $result->addErrors($deleteResult->getErrors());
            }
        }

        if ($result->isSuccess()) {
            $this->clearUserCache($userId);
            $this->clearProductCache($productId);
        }

        return $result;
    }

    /**
     * @return list<int>
     */
    public function getProductIds(int $userId): array
    {
        $cache = Cache::createInstance();
        $cacheId = 'user_' . $userId;

        if ($cache->initCache(self::CACHE_TTL, $cacheId, self::CACHE_DIR)) {
            $vars = $cache->getVars();
            return array_values(array_map('intval', $vars['PRODUCT_IDS'] ?? []));
        }

        if (!$cache->startDataCache()) {
            return [];
        }

        $productIds = [];
        $rows = FavoritesTable::getList([
            'select' => ['PRODUCT_ID'],
            'filter' => ['=USER_ID' => $userId],
            'order' => ['ID' => 'DESC'],
            'cache' => ['ttl' => self::CACHE_TTL],
        ]);

        while ($row = $rows->fetch()) {
            $productIds[] = (int)$row['PRODUCT_ID'];
        }

        $taggedCache = Application::getInstance()->getTaggedCache();
        $taggedCache->startTagCache(self::CACHE_DIR);
        $taggedCache->registerTag($this->getUserTag($userId));
        $taggedCache->registerTag('ORM_' . strtoupper(FavoritesTable::getTableName()));
        foreach ($productIds as $productId) {
            $taggedCache->registerTag($this->getProductTag($productId));
        }
        $taggedCache->endTagCache();

        $cache->endDataCache(['PRODUCT_IDS' => $productIds]);

        return $productIds;
    }

    public function isFavorite(int $userId, int $productId): bool
    {
        return $this->findId($userId, $productId) > 0;
    }

    public function countByProduct(int $productId): int
    {
        return FavoritesTable::getCount(['=PRODUCT_ID' => $productId]);
    }

    public function removeProductFromAll(int $productId): Result
    {
        $result = new Result();
        $rows = FavoritesTable::getList([
            'select' => ['ID', 'USER_ID'],
            'filter' => ['=PRODUCT_ID' => $productId],
        ]);

        $userIds = [];
        while ($row = $rows->fetch()) {
            $deleteResult = FavoritesTable::delete((int)$row['ID']);
            if (!$deleteResult->isSuccess()) {
                $result->addErrors($deleteResult->getErrors());
            }
            $userIds[] = (int)$row['USER_ID'];
        }

        if ($result->isSuccess()) {
            foreach (array_unique($userIds) as $userId) {
                $this->clearUserCache($userId);
            }
            $this->clearProductCache($productId);
        }

        return $result;
    }

    public function clearUserCache(int $userId): void
    {
        Application::getInstance()->getTaggedCache()->clearByTag($this->getUserTag($userId));
    }

    public function clearProductCache(int $productId): void
    {
        Application::getInstance()->getTaggedCache()->clearByTag($this->getProductTag($productId));
    }

    private function findId(int $userId, int $productId): int
    {
        $row = FavoritesTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=USER_ID' => $userId,
                '=PRODUCT_ID' => $productId,
            ],
            'limit' => 1,
        ])->fetch();

        return (int)($row['ID'] ?? 0);
    }

    private function getUserTag(int $userId): string
    {
        return 'vendor_favorites_user_' . $userId;
    }

    private function getProductTag(int $productId): string
    {
        return 'vendor_favorites_product_' . $productId;
    }
}
