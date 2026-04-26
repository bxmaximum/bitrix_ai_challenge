<?php

declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Vendor\Favorites\Config\ModuleOptions;
use Vendor\Favorites\Repository\FavoritesRepository;

final class FavoritesService
{
    public function __construct(
        private readonly FavoritesRepository $repository = new FavoritesRepository(),
        private readonly CookieService $cookieService = new CookieService(),
    ) {
        Loc::loadMessages(__FILE__);
    }

    public static function create(): self
    {
        $locator = ServiceLocator::getInstance();
        if ($locator->has(self::class)) {
            $service = $locator->get(self::class);
            if ($service instanceof self) {
                return $service;
            }
        }

        return new self();
    }

    public function add(int $productId, ?int $userId = null): Result
    {
        $validation = $this->validateProduct($productId);
        if (!$validation->isSuccess()) {
            return $validation;
        }

        if ($this->isAuthorizedUser($userId)) {
            $result = $this->repository->add($userId, $productId);
        } else {
            $productIds = $this->cookieService->add($productId);
            $result = (new Result())->setData($this->buildGuestState($productId, true, $productIds));
        }

        if ($result->isSuccess() && $this->isAuthorizedUser($userId)) {
            $result->setData($this->buildState($productId, $userId));
        }

        if ($result->isSuccess()) {
            $this->clearRenderedProductCaches();
        }

        return $result;
    }

    public function remove(int $productId, ?int $userId = null): Result
    {
        $validation = $this->validateProductId($productId);
        if (!$validation->isSuccess()) {
            return $validation;
        }

        if ($this->isAuthorizedUser($userId)) {
            $result = $this->repository->remove($userId, $productId);
        } else {
            $productIds = $this->cookieService->remove($productId);
            $result = (new Result())->setData($this->buildGuestState($productId, false, $productIds));
        }

        if ($result->isSuccess() && $this->isAuthorizedUser($userId)) {
            $result->setData($this->buildState($productId, $userId));
        }

        if ($result->isSuccess()) {
            $this->clearRenderedProductCaches();
        }

        return $result;
    }

    /**
     * @return list<int>
     */
    public function getProductIds(?int $userId = null): array
    {
        if ($this->isAuthorizedUser($userId)) {
            return $this->repository->getProductIds($userId);
        }

        return $this->cookieService->getProductIds();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getProducts(?int $userId = null): array
    {
        $productIds = $this->getProductIds($userId);
        if ($productIds === []) {
            return [];
        }

        $products = $this->loadProducts($productIds);
        $sorted = [];
        foreach ($productIds as $productId) {
            if (isset($products[$productId])) {
                $sorted[] = $products[$productId];
            }
        }

        return $sorted;
    }

    public function isFavorite(int $productId, ?int $userId = null): bool
    {
        if ($productId <= 0) {
            return false;
        }

        if ($this->isAuthorizedUser($userId)) {
            return $this->repository->isFavorite($userId, $productId);
        }

        return in_array($productId, $this->cookieService->getProductIds(), true);
    }

    public function countByProduct(int $productId): int
    {
        if ($productId <= 0) {
            return 0;
        }

        return $this->repository->countByProduct($productId);
    }

    public function countForDisplay(int $productId, ?int $userId = null): int
    {
        $counter = $this->countByProduct($productId);

        if (!$this->isAuthorizedUser($userId) && $this->isFavorite($productId, null)) {
            return $counter + 1;
        }

        return $counter;
    }

    public function migrateGuestFavorites(int $userId): Result
    {
        $result = new Result();
        if ($userId <= 0) {
            return $result->addError(new Error((string)Loc::getMessage('VENDOR_FAVORITES_INVALID_USER'), 'INVALID_USER'));
        }

        foreach ($this->cookieService->getProductIds() as $productId) {
            if (!$this->productExists($productId)) {
                continue;
            }

            $addResult = $this->repository->add($userId, $productId);
            if (!$addResult->isSuccess()) {
                $result->addErrors($addResult->getErrors());
            }
        }

        if ($result->isSuccess()) {
            $this->cookieService->clear();
        }

        return $result;
    }

    public function removeProductFromAll(int $productId): Result
    {
        $validation = $this->validateProductId($productId);
        if (!$validation->isSuccess()) {
            return $validation;
        }

        $result = $this->repository->removeProductFromAll($productId);
        if ($result->isSuccess()) {
            $this->clearRenderedProductCaches();
        }

        return $result;
    }

    public function invalidateProductCache(int $productId): void
    {
        if ($productId > 0) {
            $this->repository->clearProductCache($productId);
        }
    }

    private function validateProduct(int $productId): Result
    {
        $result = $this->validateProductId($productId);
        if (!$result->isSuccess()) {
            return $result;
        }

        if (!ModuleOptions::isEnabled()) {
            return $result->addError(new Error((string)Loc::getMessage('VENDOR_FAVORITES_DISABLED'), 'MODULE_DISABLED'));
        }

        if (!$this->productExists($productId)) {
            return $result->addError(new Error((string)Loc::getMessage('VENDOR_FAVORITES_PRODUCT_NOT_FOUND'), 'PRODUCT_NOT_FOUND'));
        }

        return $result;
    }

    private function validateProductId(int $productId): Result
    {
        $result = new Result();
        if ($productId <= 0) {
            $result->addError(new Error((string)Loc::getMessage('VENDOR_FAVORITES_INVALID_PRODUCT'), 'INVALID_PRODUCT_ID'));
        }

        return $result;
    }

    private function productExists(int $productId): bool
    {
        if (!Loader::includeModule('iblock')) {
            return false;
        }

        $iblockId = ModuleOptions::getCatalogIblockId();
        $filter = [
            '=ID' => $productId,
            '=ACTIVE' => 'Y',
        ];

        if ($iblockId > 0) {
            $filter['=IBLOCK_ID'] = $iblockId;
        }

        return (bool)ElementTable::getList([
            'select' => ['ID'],
            'filter' => $filter,
            'limit' => 1,
            'cache' => ['ttl' => 3600],
        ])->fetch();
    }

    /**
     * @param list<int> $productIds
     * @return array<int, array<string, mixed>>
     */
    private function loadProducts(array $productIds): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $iblockId = ModuleOptions::getCatalogIblockId();
        $filter = ['@ID' => $productIds, '=ACTIVE' => 'Y'];
        if ($iblockId > 0) {
            $filter['=IBLOCK_ID'] = $iblockId;
        }

        $rows = $this->getElementDataClass($iblockId)::getList([
            'select' => ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'DETAIL_PAGE_URL', 'PREVIEW_PICTURE'],
            'filter' => $filter,
            'cache' => ['ttl' => 3600],
        ]);

        $products = [];
        while ($row = $rows->fetch()) {
            $productId = (int)$row['ID'];
            $products[$productId] = [
                'ID' => $productId,
                'IBLOCK_ID' => (int)($row['IBLOCK_ID'] ?? 0),
                'NAME' => (string)($row['NAME'] ?? ''),
                'CODE' => (string)($row['CODE'] ?? ''),
                'DETAIL_PAGE_URL' => (string)($row['DETAIL_PAGE_URL'] ?? ''),
                'PREVIEW_PICTURE' => (int)($row['PREVIEW_PICTURE'] ?? 0),
            ];
        }

        return $products;
    }

    /**
     * @return class-string
     */
    private function getElementDataClass(int $iblockId): string
    {
        if ($iblockId <= 0) {
            return ElementTable::class;
        }

        $iblock = IblockTable::getByPrimary($iblockId, ['cache' => ['ttl' => 3600]])->fetchObject();
        $dataClass = $iblock?->getEntityDataClass();

        return is_string($dataClass) && class_exists($dataClass)
            ? $dataClass
            : ElementTable::class;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildState(int $productId, ?int $userId): array
    {
        return [
            'productId' => $productId,
            'isFavorite' => $this->isFavorite($productId, $userId),
            'productIds' => $this->getProductIds($userId),
            'counter' => $this->countForDisplay($productId, $userId),
        ];
    }

    /**
     * @param list<int> $productIds
     * @return array<string, mixed>
     */
    private function buildGuestState(int $productId, bool $isFavorite, array $productIds): array
    {
        return [
            'productId' => $productId,
            'isFavorite' => $isFavorite,
            'productIds' => $productIds,
            'counter' => $isFavorite ? $this->countByProduct($productId) + 1 : $this->countByProduct($productId),
        ];
    }

    private function isAuthorizedUser(?int &$userId): bool
    {
        $userId = (int)$userId;
        return $userId > 0;
    }

    private function clearRenderedProductCaches(): void
    {
        \CBitrixComponent::clearComponentCache('bitrix:news.detail');
    }
}
