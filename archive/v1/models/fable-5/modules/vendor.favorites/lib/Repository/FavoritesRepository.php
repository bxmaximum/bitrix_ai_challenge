<?php

declare(strict_types=1);

namespace Vendor\Favorites\Repository;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Vendor\Favorites\Model\FavoritesTable;

/**
 * Репозиторий избранного для авторизованных пользователей.
 *
 * Единственная точка доступа к таблице vendor_favorites (через ORM).
 */
final class FavoritesRepository
{
    /**
     * Возвращает список ID товаров пользователя (от новых к старым).
     *
     * @return int[]
     */
    public function getProductIds(int $userId): array
    {
        $rows = FavoritesTable::getList([
            'select' => ['PRODUCT_ID'],
            'filter' => ['=USER_ID' => $userId],
            'order' => ['ID' => 'DESC'],
        ])->fetchAll();

        return array_map(static fn (array $row): int => (int)$row['PRODUCT_ID'], $rows);
    }

    /**
     * Проверяет, есть ли товар в избранном пользователя.
     */
    public function exists(int $userId, int $productId): bool
    {
        $row = FavoritesTable::getList([
            'select' => ['ID'],
            'filter' => ['=USER_ID' => $userId, '=PRODUCT_ID' => $productId],
            'limit' => 1,
        ])->fetch();

        return $row !== false;
    }

    /**
     * Добавляет товар в избранное. Дубликаты молча игнорируются.
     */
    public function add(int $userId, int $productId): Result
    {
        if ($this->exists($userId, $productId)) {
            return new Result();
        }

        $addResult = FavoritesTable::add([
            'USER_ID' => $userId,
            'PRODUCT_ID' => $productId,
        ]);

        $result = new Result();
        if (!$addResult->isSuccess()) {
            $result->addErrors($addResult->getErrors());
        }

        return $result;
    }

    /**
     * Массовое добавление товаров (миграция из cookie). Дубликаты пропускаются.
     *
     * @param int[] $productIds
     */
    public function addMany(int $userId, array $productIds): Result
    {
        $result = new Result();
        if ($productIds === []) {
            return $result;
        }

        $existing = array_flip($this->getProductIds($userId));

        foreach (array_unique($productIds) as $productId) {
            if (isset($existing[$productId])) {
                continue;
            }

            $addResult = FavoritesTable::add([
                'USER_ID' => $userId,
                'PRODUCT_ID' => $productId,
            ]);

            if (!$addResult->isSuccess()) {
                $result->addError(new Error(
                    implode('; ', $addResult->getErrorMessages()),
                    'FAVORITES_MIGRATION_FAILED',
                    ['productId' => $productId],
                ));
            }
        }

        return $result;
    }

    /**
     * Удаляет товар из избранного пользователя.
     */
    public function remove(int $userId, int $productId): Result
    {
        $rows = FavoritesTable::getList([
            'select' => ['ID'],
            'filter' => ['=USER_ID' => $userId, '=PRODUCT_ID' => $productId],
        ])->fetchAll();

        $result = new Result();
        foreach ($rows as $row) {
            $deleteResult = FavoritesTable::delete((int)$row['ID']);
            if (!$deleteResult->isSuccess()) {
                $result->addErrors($deleteResult->getErrors());
            }
        }

        return $result;
    }

    /**
     * Удаляет товар из избранного всех пользователей
     * (вызывается при удалении элемента инфоблока).
     *
     * @return int[] ID пользователей, у которых товар был в избранном
     */
    public function removeProductForAllUsers(int $productId): array
    {
        $rows = FavoritesTable::getList([
            'select' => ['ID', 'USER_ID'],
            'filter' => ['=PRODUCT_ID' => $productId],
        ])->fetchAll();

        $userIds = [];
        foreach ($rows as $row) {
            FavoritesTable::delete((int)$row['ID']);
            $userIds[] = (int)$row['USER_ID'];
        }

        return array_values(array_unique($userIds));
    }

    /**
     * Количество пользователей, добавивших товар в избранное.
     */
    public function countByProduct(int $productId): int
    {
        return FavoritesTable::getCount(['=PRODUCT_ID' => $productId]);
    }
}
