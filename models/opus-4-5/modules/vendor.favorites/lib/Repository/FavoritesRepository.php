<?php

declare(strict_types=1);

namespace Vendor\Favorites\Repository;

use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\AddResult;
use Vendor\Favorites\Model\FavoritesTable;

/**
 * Репозиторий для работы с избранными товарами
 *
 * Инкапсулирует логику работы с базой данных
 * и инфоблоками для получения данных о товарах.
 *
 * @package Vendor\Favorites\Repository
 */
final class FavoritesRepository
{
    /**
     * Добавляет товар в избранное
     */
    public function add(int $userId, int $productId, int $iblockId): AddResult
    {
        return FavoritesTable::add([
            'USER_ID' => $userId,
            'PRODUCT_ID' => $productId,
            'IBLOCK_ID' => $iblockId,
        ]);
    }

    /**
     * Удаляет товар из избранного
     */
    public function remove(int $userId, int $productId): bool
    {
        return FavoritesTable::removeFavorite($userId, $productId);
    }

    /**
     * Проверяет, находится ли товар в избранном
     */
    public function isInFavorites(int $userId, int $productId): bool
    {
        return FavoritesTable::isProductInFavorites($userId, $productId);
    }

    /**
     * Получает список ID избранных товаров пользователя
     *
     * @return int[]
     */
    public function getListByUser(int $userId): array
    {
        return FavoritesTable::getProductIdsByUser($userId);
    }

    /**
     * Получает ID пользователей, у которых товар в избранном
     *
     * @return int[]
     */
    public function getUserIdsByProduct(int $productId): array
    {
        $result = FavoritesTable::getList([
            'filter' => ['=PRODUCT_ID' => $productId],
            'select' => ['USER_ID'],
        ]);

        $userIds = [];
        while ($row = $result->fetch()) {
            $userIds[] = (int)$row['USER_ID'];
        }

        return $userIds;
    }

    /**
     * Удаляет все записи для товара
     */
    public function removeByProduct(int $productId): int
    {
        return FavoritesTable::removeByProductId($productId);
    }

    /**
     * Получает данные о товарах из инфоблока
     *
     * @param int[] $productIds
     * @return array<int, array{ID: int, NAME: string, DETAIL_PAGE_URL: string, PREVIEW_PICTURE: ?int, PREVIEW_PICTURE_SRC: ?string}>
     */
    public function getProductsData(array $productIds, int $iblockId = 0): array
    {
        if (empty($productIds) || !Loader::includeModule('iblock')) {
            return [];
        }

        $filter = [
            '=ID' => $productIds,
            '=ACTIVE' => 'Y',
        ];

        if ($iblockId > 0) {
            $filter['=IBLOCK_ID'] = $iblockId;
        }

        $result = \Bitrix\Iblock\ElementTable::getList([
            'filter' => $filter,
            'select' => [
                'ID',
                'IBLOCK_ID',
                'NAME',
                'PREVIEW_PICTURE',
                'DETAIL_PAGE_URL' => 'IBLOCK.DETAIL_PAGE_URL',
            ],
            'runtime' => [
                new \Bitrix\Main\ORM\Fields\Relations\Reference(
                    'IBLOCK',
                    \Bitrix\Iblock\IblockTable::class,
                    \Bitrix\Main\ORM\Query\Join::on('this.IBLOCK_ID', 'ref.ID')
                ),
            ],
        ]);

        $products = [];
        while ($row = $result->fetch()) {
            $productId = (int)$row['ID'];

            // Формируем URL детальной страницы
            $detailPageUrl = $this->buildDetailPageUrl(
                $row['DETAIL_PAGE_URL'] ?? '',
                $row
            );

            // Получаем URL изображения
            $previewPictureSrc = null;
            if (!empty($row['PREVIEW_PICTURE'])) {
                $previewPictureSrc = \CFile::GetPath($row['PREVIEW_PICTURE']);
            }

            $products[$productId] = [
                'ID' => $productId,
                'IBLOCK_ID' => (int)$row['IBLOCK_ID'],
                'NAME' => $row['NAME'],
                'DETAIL_PAGE_URL' => $detailPageUrl,
                'PREVIEW_PICTURE' => $row['PREVIEW_PICTURE'] ? (int)$row['PREVIEW_PICTURE'] : null,
                'PREVIEW_PICTURE_SRC' => $previewPictureSrc,
            ];
        }

        // Сортируем по исходному порядку ID
        $sortedProducts = [];
        foreach ($productIds as $id) {
            if (isset($products[$id])) {
                $sortedProducts[] = $products[$id];
            }
        }

        return $sortedProducts;
    }

    /**
     * Формирует URL детальной страницы товара
     */
    private function buildDetailPageUrl(string $template, array $element): string
    {
        if (empty($template)) {
            return '/catalog/item/' . $element['ID'] . '/';
        }

        $replacements = [
            '#ID#' => $element['ID'],
            '#ELEMENT_ID#' => $element['ID'],
            '#IBLOCK_ID#' => $element['IBLOCK_ID'],
        ];

        return strtr($template, $replacements);
    }
}





