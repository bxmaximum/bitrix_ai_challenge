<?php

declare(strict_types=1);

namespace Vendor\Favorites\Repository;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Result;
use Vendor\Favorites\Model\FavoritesTable;

/**
 * Работа с избранным в базе данных и кэшем авторизованных пользователей.
 */
final class FavoritesRepository
{
    private const CACHE_DIR = '/vendor_favorites/';

    public function getCacheTagForUser(int $userId): string
    {
        return 'vendor_favorites_user_' . $userId;
    }

    public function getCacheTagForProduct(int $productId): string
    {
        return 'vendor_favorites_product_' . $productId;
    }

    public function getCacheTagForIblock(int $iblockId): string
    {
        return 'vendor_favorites_iblock_' . $iblockId;
    }

    /**
     * @return list<int>
     */
    public function getProductIds(int $userId): array
    {
        $cache = Cache::createInstance();
        $cacheId = 'user_favorites_' . $userId;
        $cacheTtl = 3600;

        if ($cache->initCache($cacheTtl, $cacheId, self::CACHE_DIR)) {
            $vars = $cache->getVars();

            return is_array($vars['ids'] ?? null) ? array_map('intval', $vars['ids']) : [];
        }

        $ids = [];
        $result = FavoritesTable::getList([
            'select' => ['PRODUCT_ID'],
            'filter' => ['=USER_ID' => $userId],
            'order' => ['DATE_CREATE' => 'DESC'],
        ]);

        while ($row = $result->fetch()) {
            $ids[] = (int) $row['PRODUCT_ID'];
        }

        if ($cache->startDataCache()) {
            $taggedCache = Application::getInstance()->getTaggedCache();
            $taggedCache->startTagCache(self::CACHE_DIR);
            $taggedCache->registerTag($this->getCacheTagForUser($userId));
            $taggedCache->endTagCache();

            $cache->endDataCache(['ids' => $ids]);
        }

        return $ids;
    }

    public function add(int $userId, int $productId): Result
    {
        $exists = FavoritesTable::getCount([
            '=USER_ID' => $userId,
            '=PRODUCT_ID' => $productId,
        ]) > 0;

        if ($exists) {
            return new Result();
        }

        $result = FavoritesTable::add([
            'USER_ID' => $userId,
            'PRODUCT_ID' => $productId,
        ]);

        if ($result->isSuccess()) {
            $this->clearUserCache($userId);
        }

        return $result;
    }

    public function remove(int $userId, int $productId): Result
    {
        $row = FavoritesTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=USER_ID' => $userId,
                '=PRODUCT_ID' => $productId,
            ],
            'limit' => 1,
        ])->fetch();

        if ($row === false) {
            return new Result();
        }

        $result = FavoritesTable::delete((int) $row['ID']);

        if ($result->isSuccess()) {
            $this->clearUserCache($userId);
        }

        return $result;
    }

    /**
     * @param list<int> $productIds
     */
    public function mergeProducts(int $userId, array $productIds): void
    {
        $productIds = array_values(array_unique(array_filter(
            array_map('intval', $productIds),
            static fn (int $id): bool => $id > 0,
        )));

        if ($productIds === []) {
            return;
        }

        $existing = array_flip($this->getProductIds($userId));

        foreach ($productIds as $productId) {
            if (isset($existing[$productId])) {
                continue;
            }

            FavoritesTable::add([
                'USER_ID' => $userId,
                'PRODUCT_ID' => $productId,
            ]);
        }

        $this->clearUserCache($userId);
    }

    public function deleteByProductId(int $productId): void
    {
        $rows = FavoritesTable::getList([
            'select' => ['ID', 'USER_ID'],
            'filter' => ['=PRODUCT_ID' => $productId],
        ]);

        $affectedUsers = [];
        while ($row = $rows->fetch()) {
            FavoritesTable::delete((int) $row['ID']);
            $affectedUsers[(int) $row['USER_ID']] = true;
        }

        foreach (array_keys($affectedUsers) as $userId) {
            $this->clearUserCache($userId);
        }

        Application::getInstance()->getTaggedCache()->clearByTag(
            $this->getCacheTagForProduct($productId),
        );
    }

    public function countByProductId(int $productId): int
    {
        return (int) FavoritesTable::getCount(['=PRODUCT_ID' => $productId]);
    }

    public function clearUserCache(int $userId): void
    {
        Application::getInstance()->getTaggedCache()->clearByTag(
            $this->getCacheTagForUser($userId),
        );
    }

    public function clearProductCache(int $productId, int $iblockId): void
    {
        $taggedCache = Application::getInstance()->getTaggedCache();
        $taggedCache->clearByTag($this->getCacheTagForProduct($productId));
        $taggedCache->clearByTag($this->getCacheTagForIblock($iblockId));
    }
}
