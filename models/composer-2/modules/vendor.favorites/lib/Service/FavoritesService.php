<?php

declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\FileTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Vendor\Favorites\Config\ModuleConfig;
use Vendor\Favorites\Repository\FavoritesRepository;

/**
 * Бизнес-логика избранного: БД, cookie, кэш, миграция при авторизации.
 */
final class FavoritesService
{
    private const TAG_USER = 'vendor_favorites_u_';
    private const TAG_ELEMENT = 'vendor_fav_el_';

    public function __construct(
        private readonly FavoritesRepository $repository = new FavoritesRepository(),
        private readonly CookieService $cookieService = new CookieService(),
        private readonly CatalogProductValidator $catalogValidator = new CatalogProductValidator(),
    ) {
    }

    public function isAuthorized(CurrentUser $user): bool
    {
        $id = $user->getId();

        return $id !== null && (int) $id > 0;
    }

    /**
     * @return list<int>
     */
    public function getFavoriteProductIds(CurrentUser $user): array
    {
        if (!$this->isAuthorized($user)) {
            return $this->cookieService->getProductIds();
        }

        return $this->getCachedUserIds((int) $user->getId());
    }

    /**
     * @return list<int>|null null при ошибке
     */
    public function add(CurrentUser $user, int $productId): ?array
    {
        if (!ModuleConfig::isEnabled() || !$this->catalogValidator->existsInCatalog($productId)) {
            return null;
        }

        if ($this->isAuthorized($user)) {
            $uid = (int) $user->getId();
            $ok = $this->repository->add($uid, $productId);
            if (!$ok) {
                return null;
            }
            $this->invalidateUserCache($uid);

            return $this->getFavoriteProductIds($user);
        }

        $ids = $this->cookieService->getProductIds();
        $ids[] = $productId;
        $newIds = array_values(array_unique($ids));
        $this->cookieService->setProductIds($newIds);

        return $newIds;
    }

    public function isFavorite(CurrentUser $user, int $productId): bool
    {
        return in_array($productId, $this->getFavoriteProductIds($user), true);
    }

    public function getFavoritesCount(CurrentUser $user): int
    {
        return count($this->getFavoriteProductIds($user));
    }

    /**
     * @return list<int>|null null при ошибке
     */
    public function remove(CurrentUser $user, int $productId): ?array
    {
        if (!ModuleConfig::isEnabled()) {
            return null;
        }

        if ($this->isAuthorized($user)) {
            $uid = (int) $user->getId();
            $ok = $this->repository->remove($uid, $productId);
            if (!$ok) {
                return null;
            }
            $this->invalidateUserCache($uid);

            return $this->getFavoriteProductIds($user);
        }

        $ids = array_values(array_filter(
            $this->cookieService->getProductIds(),
            static fn (int $id): bool => $id !== $productId
        ));
        $this->cookieService->setProductIds($ids);

        return $ids;
    }

    /**
     * Миграция cookie → БД после авторизации (без дубликатов).
     */
    public function migrateGuestCookieToUser(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $guestIds = $this->cookieService->getProductIds();
        if ($guestIds === []) {
            return;
        }

        $iblockId = ModuleConfig::getCatalogIblockId();
        $valid = [];
        if ($iblockId > 0 && Loader::includeModule('iblock')) {
            foreach ($guestIds as $pid) {
                $row = ElementTable::getRow([
                    'filter' => [
                        '=ID' => $pid,
                        '=IBLOCK_ID' => $iblockId,
                        '=ACTIVE' => 'Y',
                    ],
                    'select' => ['ID'],
                ]);
                if ($row) {
                    $valid[] = (int) $row['ID'];
                }
            }
        }

        $this->repository->mergeUnique($userId, $valid);
        $this->cookieService->clear();
        $this->invalidateUserCache($userId);
    }

    /**
     * Данные товаров для API (с кэшированием).
     *
     * @return list<array<string, mixed>>
     */
    public function getProductsData(CurrentUser $user): array
    {
        $ids = $this->getFavoriteProductIds($user);
        if ($ids === []) {
            return [];
        }
        sort($ids);

        $cache = Cache::createInstance();
        $cacheId = 'vendor_fav_products_' . md5(Json::encode($ids));
        $path = '/vendor.favorites/products/';
        $ttl = ModuleConfig::getListCacheTtl();

        $taggedCache = Application::getInstance()->getTaggedCache();

        if ($cache->initCache($ttl, $cacheId, $path)) {
            $data = $cache->getVars();

            return is_array($data) ? $data : [];
        }

        $data = [];
        if ($cache->startDataCache()) {
            $taggedCache->startTagCache($path);
            if ($this->isAuthorized($user)) {
                $taggedCache->registerTag(self::TAG_USER . (int) $user->getId());
            }
            foreach ($ids as $pid) {
                $taggedCache->registerTag(self::TAG_ELEMENT . $pid);
            }

            $data = $this->loadProductsFromIblock($ids);
            $taggedCache->endTagCache();
            $cache->endDataCache($data);
        }

        return $data;
    }

    /**
     * @param list<int> $ids
     * @return list<array<string, mixed>>
     */
    private function loadProductsFromIblock(array $ids): array
    {
        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $iblockId = ModuleConfig::getCatalogIblockId();
        if ($iblockId <= 0) {
            return [];
        }

        $iblock = IblockTable::getRow([
            'filter' => ['=ID' => $iblockId],
            'select' => ['ID', 'DETAIL_PAGE_URL', 'LIST_PAGE_URL'],
        ]);
        if (!$iblock) {
            return [];
        }

        $res = ElementTable::getList([
            'filter' => [
                '@ID' => $ids,
                '=IBLOCK_ID' => $iblockId,
            ],
            'select' => [
                'ID', 'NAME', 'CODE', 'PREVIEW_PICTURE', 'IBLOCK_ID',
            ],
        ]);
        $byId = [];
        while ($row = $res->fetch()) {
            $previewSrc = '';
            if (!empty($row['PREVIEW_PICTURE'])) {
                $previewSrc = $this->getFilePublicPath((int) $row['PREVIEW_PICTURE']);
            }
            $detailUrl = $this->buildDetailUrl($iblock, $row);
            $byId[(int) $row['ID']] = [
                'id' => (int) $row['ID'],
                'name' => (string) $row['NAME'],
                'code' => (string) ($row['CODE'] ?? ''),
                'previewPicture' => $previewSrc,
                'detailUrl' => $detailUrl,
                'iblockId' => (int) $row['IBLOCK_ID'],
            ];
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    /**
     * @param array<string, mixed> $iblock
     * @param array<string, mixed> $element
     */
    private function buildDetailUrl(array $iblock, array $element): string
    {
        $template = (string) ($iblock['DETAIL_PAGE_URL'] ?? '');
        if ($template === '') {
            return '';
        }

        $replacements = [
            '#ELEMENT_ID#' => (string) (int) $element['ID'],
            '#ELEMENT_CODE#' => (string) ($element['CODE'] ?? ''),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * @return list<int>
     */
    private function getCachedUserIds(int $userId): array
    {
        $cache = Cache::createInstance();
        $cacheId = 'vendor_fav_ids_' . $userId;
        $path = '/vendor.favorites/list/';
        $ttl = ModuleConfig::getListCacheTtl();
        $taggedCache = Application::getInstance()->getTaggedCache();

        if ($cache->initCache($ttl, $cacheId, $path)) {
            $vars = $cache->getVars();

            return is_array($vars) ? $vars : [];
        }

        $ids = [];
        if ($cache->startDataCache()) {
            $taggedCache->startTagCache($path);
            $taggedCache->registerTag(self::TAG_USER . $userId);
            $ids = $this->repository->getProductIdsForUser($userId);
            foreach ($ids as $pid) {
                $taggedCache->registerTag(self::TAG_ELEMENT . $pid);
            }
            $taggedCache->endTagCache();
            $cache->endDataCache($ids);
        }

        return $ids;
    }

    private function getFilePublicPath(int $fileId): string
    {
        if ($fileId <= 0) {
            return '';
        }
        $file = FileTable::getById($fileId)->fetch();
        if (!$file) {
            return '';
        }
        $subdir = trim((string) ($file['SUBDIR'] ?? ''), '/');
        $name = (string) ($file['FILE_NAME'] ?? '');
        if ($name === '') {
            return '';
        }
        $middle = $subdir !== '' ? '/' . $subdir . '/' : '/';

        return '/upload' . $middle . $name;
    }

    private function invalidateUserCache(int $userId): void
    {
        Application::getInstance()->getTaggedCache()->clearByTag(self::TAG_USER . $userId);
    }

    public static function invalidateByElementId(int $elementId): void
    {
        if ($elementId <= 0) {
            return;
        }
        Application::getInstance()->getTaggedCache()->clearByTag(self::TAG_ELEMENT . $elementId);
    }
}
