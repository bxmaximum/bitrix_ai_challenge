<?php

declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;
use Vendor\Favorites\Repository\FavoritesRepository;
use Bitrix\Iblock\IblockTable;

class FavoritesService
{
    private FavoritesRepository $repository;
    private CookieService $cookieService;

    public function __construct()
    {
        $this->repository = new FavoritesRepository();
        $this->cookieService = new CookieService();
    }

    /**
     * @return int[]
     */
    public function getFavorites(): array
    {
        $userId = (int)CurrentUser::get()->getId();

        if ($userId > 0) {
            return $this->repository->getByUserId($userId);
        }

        return $this->cookieService->getFavoriteIds();
    }

    public function add(int $productId): bool
    {
        if (!$this->isValidProduct($productId)) {
            return false;
        }

        $userId = (int)CurrentUser::get()->getId();

        if ($userId > 0) {
            if ($this->repository->exists($userId, $productId)) {
                return true;
            }
            $result = $this->repository->add($userId, $productId);
            if ($result) {
                $this->clearCache();
            }
            return $result;
        }

        $ids = $this->cookieService->getFavoriteIds();
        if (!in_array($productId, $ids, true)) {
            $ids[] = $productId;
            $this->cookieService->setFavoriteIds($ids);
        }

        return true;
    }

    public function remove(int $productId): bool
    {
        $userId = (int)CurrentUser::get()->getId();

        if ($userId > 0) {
            $result = $this->repository->remove($userId, $productId);
            if ($result) {
                $this->clearCache();
            }
            return $result;
        }

        $ids = $this->cookieService->getFavoriteIds();
        $key = array_search($productId, $ids, true);
        if ($key !== false) {
            unset($ids[$key]);
            $this->cookieService->setFavoriteIds(array_values($ids));
        }

        return true;
    }

    public function migrateFromCookie(int $userId): void
    {
        $cookieIds = $this->cookieService->getFavoriteIds();
        if (empty($cookieIds)) {
            return;
        }

        $dbIds = $this->repository->getByUserId($userId);
        $newIds = array_diff($cookieIds, $dbIds);

        foreach ($newIds as $productId) {
            if ($this->isValidProduct($productId)) {
                $this->repository->add($userId, $productId);
            }
        }

        $this->cookieService->clear();
        $this->clearCache();
    }

    public function clearCache(): void
    {
        if (defined('BX_COMP_MANAGED_CACHE')) {
            $taggedCache = Application::getInstance()->getTaggedCache();
            $taggedCache->clearByTag('vendor_favorites_user_' . CurrentUser::get()->getId());
        }
    }

    private function isValidProduct(int $productId): bool
    {
        if ($productId <= 0) {
            return false;
        }

        if (!Loader::includeModule('iblock')) {
            return false;
        }

        $iblockId = (int)Option::get('vendor.favorites', 'catalog_iblock_id');
        if ($iblockId <= 0) {
            return false;
        }

        // Using D7 API for Elements as requested
        // \Bitrix\Iblock\Elements\Element{IblockApiName}::getList
        $iblock = IblockTable::getById($iblockId)->fetch();
        if (!$iblock || empty($iblock['API_CODE'])) {
            // Fallback if API_CODE is not set, though the requirement says use Elements
            $res = \Bitrix\Iblock\ElementTable::getList([
                'select' => ['ID'],
                'filter' => ['=ID' => $productId, '=IBLOCK_ID' => $iblockId, '=ACTIVE' => 'Y'],
                'limit' => 1
            ]);
            return (bool)$res->fetch();
        }

        $apiCode = $iblock['API_CODE'];
        $elementClass = "\\Bitrix\\Iblock\\Elements\\Element" . $apiCode . "Table";
        
        if (class_exists($elementClass)) {
            $res = $elementClass::getList([
                'select' => ['ID'],
                'filter' => ['=ID' => $productId, '=ACTIVE' => 'Y'],
                'limit' => 1
            ]);
            return (bool)$res->fetch();
        }

        return false;
    }
}

