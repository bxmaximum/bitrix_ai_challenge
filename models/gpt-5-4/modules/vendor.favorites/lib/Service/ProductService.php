<?php
declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;

/**
 * Reads product data from configured catalog iblock through D7 ORM.
 */
final class ProductService
{
    /**
     * Checks whether product exists in configured catalog iblock.
     */
    public function exists(int $productId): bool
    {
        if ($productId < 1) {
            return false;
        }

        $dataClass = $this->getElementDataClass();
        if ($dataClass === null) {
            return false;
        }

        $row = $dataClass::query()
            ->setSelect(['ID'])
            ->where('ID', $productId)
            ->where('IBLOCK_ID', ModuleSettings::getIblockId())
            ->setLimit(1)
            ->fetch();

        return is_array($row);
    }

    /**
     * Returns existing products preserving requested ids order.
     *
     * @param int[] $productIds
     * @return array<int, array<string, mixed>>
     */
    public function getProductsByIds(array $productIds): array
    {
        $normalizedIds = array_values(array_unique(array_filter(
            array_map(static fn(mixed $id): int => (int) $id, $productIds),
            static fn(int $id): bool => $id > 0
        )));

        if ($normalizedIds === []) {
            return [];
        }

        $dataClass = $this->getElementDataClass();
        if ($dataClass === null) {
            return [];
        }

        $rows = $dataClass::query()
            ->setSelect([
                'ID',
                'IBLOCK_ID',
                'NAME',
                'CODE',
                'ACTIVE',
                'PREVIEW_TEXT',
                'DETAIL_TEXT',
                'PREVIEW_PICTURE',
                'DETAIL_PICTURE',
            ])
            ->whereIn('ID', $normalizedIds)
            ->where('IBLOCK_ID', ModuleSettings::getIblockId())
            ->fetchAll();

        $indexed = [];
        foreach ($rows as $row) {
            $productId = (int) $row['ID'];
            $indexed[$productId] = [
                'id' => $productId,
                'iblockId' => (int) $row['IBLOCK_ID'],
                'name' => (string) $row['NAME'],
                'code' => (string) ($row['CODE'] ?? ''),
                'active' => (string) ($row['ACTIVE'] ?? 'N') === 'Y',
                'previewText' => (string) ($row['PREVIEW_TEXT'] ?? ''),
                'detailText' => (string) ($row['DETAIL_TEXT'] ?? ''),
                'previewPictureSrc' => !empty($row['PREVIEW_PICTURE'])
                    ? \CFile::GetPath((int) $row['PREVIEW_PICTURE'])
                    : null,
                'detailPictureSrc' => !empty($row['DETAIL_PICTURE'])
                    ? \CFile::GetPath((int) $row['DETAIL_PICTURE'])
                    : null,
            ];
        }

        $products = [];
        foreach ($normalizedIds as $productId) {
            if (isset($indexed[$productId])) {
                $products[] = $indexed[$productId];
            }
        }

        return $products;
    }

    /**
     * Returns ids that still exist in catalog.
     *
     * @param int[] $productIds
     * @return int[]
     */
    public function filterExistingProductIds(array $productIds): array
    {
        return array_map(
            static fn(array $product): int => (int) $product['id'],
            $this->getProductsByIds($productIds)
        );
    }

    /**
     * Returns current iblock tag used by tagged cache.
     */
    public function getIblockTag(): ?string
    {
        $iblockId = ModuleSettings::getIblockId();

        return $iblockId > 0 ? 'iblock_id_' . $iblockId : null;
    }

    /**
     * Resolves dynamic ORM data class for configured iblock.
     */
    private function getElementDataClass(): ?string
    {
        if (!Loader::includeModule('iblock')) {
            return null;
        }

        $iblockId = ModuleSettings::getIblockId();
        if ($iblockId < 1) {
            return null;
        }

        $iblock = IblockTable::getList([
            'select' => ['ID', 'API_CODE'],
            'filter' => ['=ID' => $iblockId],
            'limit' => 1,
        ])->fetchObject();

        if ($iblock === null || (string) $iblock->getApiCode() === '') {
            return null;
        }

        $entity = IblockTable::compileEntity($iblock);
        if ($entity === false) {
            return null;
        }

        return $entity->getDataClass();
    }
}
