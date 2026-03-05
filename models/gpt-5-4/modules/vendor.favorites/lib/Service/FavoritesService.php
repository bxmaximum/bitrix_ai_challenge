<?php
declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Engine\CurrentUser;
use RuntimeException;
use Vendor\Favorites\Repository\FavoritesRepository;

/**
 * Coordinates favorites storage for authorized users and guests.
 */
final class FavoritesService
{
    private const CACHE_PATH = '/vendor.favorites/list/';

    public function __construct(
        private readonly FavoritesRepository $repository,
        private readonly CookieService $cookieService,
        private readonly ProductService $productService
    ) {
    }

    /**
     * Adds product to current user's favorites.
     *
     * @return array<string, mixed>
     */
    public function add(int $productId, ?int $userId = null): array
    {
        $this->assertModuleEnabled();

        if ($productId < 1) {
            throw new RuntimeException('Некорректный ID товара.');
        }

        if (!$this->productService->exists($productId)) {
            throw new RuntimeException('Товар не найден в выбранном инфоблоке.');
        }

        $resolvedUserId = $this->resolveUserId($userId);
        if ($resolvedUserId > 0) {
            $this->repository->add($resolvedUserId, $productId);
            $this->clearUserCache($resolvedUserId);
        } else {
            $this->cookieService->addProduct($productId);
        }

        return $this->buildState($productId, $resolvedUserId);
    }

    /**
     * Removes product from current user's favorites.
     *
     * @return array<string, mixed>
     */
    public function remove(int $productId, ?int $userId = null): array
    {
        $this->assertModuleEnabled();

        if ($productId < 1) {
            throw new RuntimeException('Некорректный ID товара.');
        }

        $resolvedUserId = $this->resolveUserId($userId);
        if ($resolvedUserId > 0) {
            $this->repository->remove($resolvedUserId, $productId);
            $this->clearUserCache($resolvedUserId);
        } else {
            $this->cookieService->removeProduct($productId);
        }

        return $this->buildState($productId, $resolvedUserId);
    }

    /**
     * Returns current user favorite product ids.
     *
     * @return int[]
     */
    public function getFavoriteProductIds(?int $userId = null): array
    {
        if (!ModuleSettings::isEnabled()) {
            return [];
        }

        $resolvedUserId = $this->resolveUserId($userId);
        if ($resolvedUserId < 1) {
            return $this->cookieService->getProductIds();
        }

        return $this->getCachedProductIds($resolvedUserId);
    }

    /**
     * Returns whether product is currently favorited.
     */
    public function isFavorite(int $productId, ?int $userId = null): bool
    {
        return in_array($productId, $this->getFavoriteProductIds($userId), true);
    }

    /**
     * Returns current favorite products with data from iblock.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFavoriteProducts(?int $userId = null): array
    {
        return $this->productService->getProductsByIds(
            $this->getFavoriteProductIds($userId)
        );
    }

    /**
     * Returns current favorites state for product and UI counter.
     *
     * @return array<string, mixed>
     */
    public function buildState(int $productId, ?int $userId = null): array
    {
        $resolvedUserId = $this->resolveUserId($userId);
        $favoriteIds = $this->getFavoriteProductIds($resolvedUserId);

        return [
            'productId' => $productId,
            'isFavorite' => in_array($productId, $favoriteIds, true),
            'favoriteIds' => $favoriteIds,
            'totalCount' => count($favoriteIds),
            'isAuthorized' => $resolvedUserId > 0,
        ];
    }

    /**
     * Moves guest favorites to authorized storage after login.
     */
    public function migrateGuestFavoritesToUser(int $userId): void
    {
        if ($userId < 1 || !ModuleSettings::isEnabled()) {
            return;
        }

        $guestProductIds = $this->cookieService->getProductIds();
        if ($guestProductIds === []) {
            return;
        }

        $existingIds = $this->productService->filterExistingProductIds($guestProductIds);
        $this->repository->mergeUserFavorites($userId, $existingIds);
        $this->cookieService->clear();
        $this->clearUserCache($userId);
    }

    /**
     * Removes deleted product from favorites of all users.
     */
    public function deleteProductFromAllFavorites(int $productId): void
    {
        if ($productId < 1) {
            return;
        }

        $this->repository->deleteByProductId($productId);
        $this->clearIblockCache();
    }

    /**
     * Invalidates cached favorites lists for configured iblock.
     */
    public function invalidateIblockCache(): void
    {
        $this->clearIblockCache();
    }

    /**
     * Resolves current authorized user id when not passed explicitly.
     */
    public function resolveUserId(?int $userId = null): int
    {
        if ($userId !== null) {
            return max(0, $userId);
        }

        return max(0, (int) CurrentUser::get()->getId());
    }

    /**
     * Returns cached favorites list for authorized user.
     *
     * @return int[]
     */
    private function getCachedProductIds(int $userId): array
    {
        $cacheId = 'favorites_user_' . $userId;
        $cache = Cache::createInstance();

        if ($cache->initCache(ModuleSettings::getCacheTtl(), $cacheId, self::CACHE_PATH)) {
            $cached = $cache->getVars();

            return is_array($cached['ids'] ?? null) ? $cached['ids'] : [];
        }

        $ids = $this->repository->getProductIdsByUserId($userId);

        if ($cache->startDataCache()) {
            $taggedCache = Application::getInstance()->getTaggedCache();
            $taggedCache->startTagCache(self::CACHE_PATH);
            $taggedCache->registerTag($this->getUserCacheTag($userId));

            $iblockTag = $this->productService->getIblockTag();
            if ($iblockTag !== null) {
                $taggedCache->registerTag($iblockTag);
            }

            $taggedCache->endTagCache();
            $cache->endDataCache(['ids' => $ids]);
        }

        return $ids;
    }

    /**
     * Clears tagged cache for one authorized user.
     */
    private function clearUserCache(int $userId): void
    {
        Application::getInstance()
            ->getTaggedCache()
            ->clearByTag($this->getUserCacheTag($userId));
    }

    /**
     * Clears caches linked to configured catalog iblock.
     */
    private function clearIblockCache(): void
    {
        $iblockTag = $this->productService->getIblockTag();
        if ($iblockTag === null) {
            return;
        }

        Application::getInstance()
            ->getTaggedCache()
            ->clearByTag($iblockTag);
    }

    /**
     * Builds tagged cache key for user favorites.
     */
    private function getUserCacheTag(int $userId): string
    {
        return 'vendor_favorites_user_' . $userId;
    }

    /**
     * Stops write operations when module was disabled by administrator.
     */
    private function assertModuleEnabled(): void
    {
        if (!ModuleSettings::isEnabled()) {
            throw new RuntimeException('Модуль избранного отключен в настройках.');
        }
    }
}
