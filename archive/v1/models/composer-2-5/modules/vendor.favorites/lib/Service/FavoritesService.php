<?php

declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Vendor\Favorites\Config\ModuleOptions;
use Vendor\Favorites\Repository\FavoritesRepository;

/**
 * Бизнес-логика избранного для авторизованных пользователей и гостей.
 */
final class FavoritesService
{
    public function __construct(
        private readonly ModuleOptions $options,
        private readonly FavoritesRepository $repository,
        private readonly CookieService $cookieService,
        private readonly ProductService $productService,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->options->isEnabled();
    }

    public function add(int $productId): Result
    {
        $result = new Result();

        if (!$this->isEnabled()) {
            return $result->addError(new Error('Модуль избранного отключён', 'DISABLED'));
        }

        if ($productId <= 0) {
            return $result->addError(new Error('Некорректный ID товара', 'INVALID_PRODUCT_ID'));
        }

        if (!$this->productService->exists($productId)) {
            return $result->addError(new Error('Товар не найден', 'PRODUCT_NOT_FOUND'));
        }

        $userId = $this->getAuthorizedUserId();
        if ($userId !== null) {
            return $this->repository->add($userId, $productId);
        }

        $this->cookieService->add($productId);

        return $result;
    }

    public function remove(int $productId): Result
    {
        $result = new Result();

        if (!$this->isEnabled()) {
            return $result->addError(new Error('Модуль избранного отключён', 'DISABLED'));
        }

        if ($productId <= 0) {
            return $result->addError(new Error('Некорректный ID товара', 'INVALID_PRODUCT_ID'));
        }

        $userId = $this->getAuthorizedUserId();
        if ($userId !== null) {
            return $this->repository->remove($userId, $productId);
        }

        $this->cookieService->remove($productId);

        return $result;
    }

    /**
     * @return list<int>
     */
    public function getList(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $userId = $this->getAuthorizedUserId();
        if ($userId !== null) {
            return $this->repository->getProductIds($userId);
        }

        return $this->cookieService->getProductIds();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getProducts(): array
    {
        return $this->productService->getProductsByIds($this->getList());
    }

    public function isFavorite(int $productId): bool
    {
        if ($productId <= 0 || !$this->isEnabled()) {
            return false;
        }

        return in_array($productId, $this->getList(), true);
    }

    public function getFavoriteCount(int $productId): int
    {
        return $this->resolveFavoriteCount($productId, null);
    }

    /**
     * Счётчик сразу после add/remove (cookie в текущем запросе ещё не обновлены).
     */
    public function getFavoriteCountAfterMutation(int $productId, bool $isFavoriteNow): int
    {
        return $this->resolveFavoriteCount($productId, $isFavoriteNow);
    }

    private function resolveFavoriteCount(int $productId, ?bool $isFavoriteNow): int
    {
        if ($productId <= 0) {
            return 0;
        }

        $dbCount = $this->repository->countByProductId($productId);

        if ($this->getAuthorizedUserId() !== null) {
            return $dbCount;
        }

        $isFavorite = $isFavoriteNow ?? $this->isFavorite($productId);

        // Гость в cookie — в БД нет, для UX показываем минимум 1, если в избранном.
        if ($isFavorite) {
            return max($dbCount, 1);
        }

        return $dbCount;
    }

    /**
     * @param list<int> $productIds
     *
     * @return array<int, int>
     */
    public function getFavoriteCounts(array $productIds): array
    {
        $counts = [];
        foreach ($productIds as $productId) {
            $productId = (int) $productId;
            if ($productId > 0) {
                $counts[$productId] = $this->getFavoriteCount($productId);
            }
        }

        return $counts;
    }

    public function migrateGuestToUser(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $guestIds = $this->cookieService->getProductIds();
        if ($guestIds === []) {
            return;
        }

        $validIds = [];
        foreach ($guestIds as $productId) {
            if ($this->productService->exists($productId)) {
                $validIds[] = $productId;
            }
        }

        if ($validIds !== []) {
            $this->repository->mergeProducts($userId, $validIds);
        }

        $this->cookieService->clear();
    }

    private function getAuthorizedUserId(): ?int
    {
        $userId = (int) CurrentUser::get()->getId();

        return $userId > 0 ? $userId : null;
    }
}
