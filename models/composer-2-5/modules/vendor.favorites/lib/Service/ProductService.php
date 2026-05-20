<?php

declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use Vendor\Favorites\Config\ModuleOptions;
use Vendor\Favorites\Repository\FavoritesRepository;

/**
 * Проверка и загрузка товаров каталога через D7 ORM инфоблоков.
 */
final class ProductService
{
    private const CACHE_DIR = '/vendor_favorites/products/';

    public function __construct(
        private readonly ModuleOptions $options,
        private readonly FavoritesRepository $repository,
    ) {
    }

    public function exists(int $productId): bool
    {
        if ($productId <= 0) {
            return false;
        }

        $iblockId = $this->options->getIblockId();
        if ($iblockId <= 0) {
            return false;
        }

        if (!Loader::includeModule('iblock')) {
            return false;
        }

        $dataClass = $this->resolveElementDataClass($iblockId);
        if ($dataClass === null) {
            return false;
        }

        $row = $dataClass::getList([
            'select' => ['ID'],
            'filter' => [
                '=ID' => $productId,
                '=IBLOCK_ID' => $iblockId,
                '=ACTIVE' => 'Y',
            ],
            'limit' => 1,
        ])->fetch();

        return $row !== false;
    }

    /**
     * @param list<int> $productIds
     *
     * @return list<array<string, mixed>>
     */
    public function getProductsByIds(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(
            array_map('intval', $productIds),
            static fn (int $id): bool => $id > 0,
        )));

        if ($productIds === []) {
            return [];
        }

        $iblockId = $this->options->getIblockId();
        if ($iblockId <= 0 || !Loader::includeModule('iblock')) {
            return [];
        }

        sort($productIds);
        $cache = Cache::createInstance();
        $cacheId = 'products_' . md5(implode(',', $productIds));
        $cacheTtl = 3600;

        if ($cache->initCache($cacheTtl, $cacheId, self::CACHE_DIR)) {
            $vars = $cache->getVars();

            return is_array($vars['items'] ?? null) ? $vars['items'] : [];
        }

        $dataClass = $this->resolveElementDataClass($iblockId);
        if ($dataClass === null) {
            return [];
        }

        $items = [];
        $result = $dataClass::getList([
            'select' => [
                'ID',
                'NAME',
                'CODE',
                'PREVIEW_PICTURE',
                'PREVIEW_TEXT',
            ],
            'filter' => [
                '@ID' => $productIds,
                '=IBLOCK_ID' => $iblockId,
                '=ACTIVE' => 'Y',
            ],
        ]);

        while ($element = $result->fetchObject()) {
            $pictureSrc = null;
            $pictureId = (int) $element->getPreviewPicture();
            if ($pictureId > 0) {
                $pictureSrc = \CFile::GetPath($pictureId);
            }

            $items[] = [
                'ID' => (int) $element->getId(),
                'NAME' => (string) $element->getName(),
                'CODE' => (string) $element->getCode(),
                'DETAIL_PAGE_URL' => (string) $element->getDetailPageUrl(),
                'PREVIEW_PICTURE' => $pictureSrc,
                'PREVIEW_TEXT' => (string) $element->getPreviewText(),
            ];
        }

        if ($cache->startDataCache()) {
            $taggedCache = Application::getInstance()->getTaggedCache();
            $taggedCache->startTagCache(self::CACHE_DIR);
            $taggedCache->registerTag($this->repository->getCacheTagForIblock($iblockId));
            foreach ($productIds as $productId) {
                $taggedCache->registerTag($this->repository->getCacheTagForProduct($productId));
            }
            $taggedCache->endTagCache();
            $cache->endDataCache(['items' => $items]);
        }

        return $items;
    }

    /**
     * @return class-string|null
     */
    private function resolveElementDataClass(int $iblockId): ?string
    {
        $iblock = IblockTable::getByPrimary($iblockId, [
            'select' => ['ID', 'API_CODE'],
        ])->fetchObject();

        if ($iblock === null) {
            return null;
        }

        $iblock->fillApiCode();

        if ($iblock->getApiCode() === null || $iblock->getApiCode() === '') {
            return null;
        }

        $entity = IblockTable::compileEntity($iblock);
        if ($entity === false) {
            return null;
        }

        return $entity->getDataClass();
    }
}
