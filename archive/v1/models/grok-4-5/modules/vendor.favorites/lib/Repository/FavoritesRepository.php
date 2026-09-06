<?php

declare(strict_types=1);

namespace Vendor\Favorites\Repository;

use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\Result;
use Vendor\Favorites\Model\FavoritesTable;

/**
 * Persistence layer for favorites stored in the database.
 */
final class FavoritesRepository
{
	/**
	 * @return list<int>
	 */
	public function getProductIdsByUser(int $userId): array
	{
		if ($userId <= 0)
		{
			return [];
		}

		$rows = FavoritesTable::getList([
			'select' => ['PRODUCT_ID'],
			'filter' => ['=USER_ID' => $userId],
			'order' => ['DATE_CREATE' => 'DESC', 'ID' => 'DESC'],
		])->fetchAll();

		$ids = [];
		foreach ($rows as $row)
		{
			$id = (int)($row['PRODUCT_ID'] ?? 0);
			if ($id > 0)
			{
				$ids[] = $id;
			}
		}

		return array_values(array_unique($ids));
	}

	public function exists(int $userId, int $productId): bool
	{
		if ($userId <= 0 || $productId <= 0)
		{
			return false;
		}

		$row = FavoritesTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=USER_ID' => $userId,
				'=PRODUCT_ID' => $productId,
			],
			'limit' => 1,
		])->fetch();

		return $row !== false;
	}

	public function add(int $userId, int $productId): AddResult
	{
		return FavoritesTable::add([
			'USER_ID' => $userId,
			'PRODUCT_ID' => $productId,
		]);
	}

	public function remove(int $userId, int $productId): Result
	{
		$result = new Result();

		if ($userId <= 0 || $productId <= 0)
		{
			return $result;
		}

		$rows = FavoritesTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=USER_ID' => $userId,
				'=PRODUCT_ID' => $productId,
			],
		])->fetchAll();

		foreach ($rows as $row)
		{
			$deleteResult = FavoritesTable::delete((int)$row['ID']);
			if (!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	/**
	 * Adds products skipping duplicates. Returns number of newly inserted rows.
	 *
	 * @param list<int> $productIds
	 */
	public function addManyIgnoringDuplicates(int $userId, array $productIds): int
	{
		if ($userId <= 0 || $productIds === [])
		{
			return 0;
		}

		$existing = array_flip($this->getProductIdsByUser($userId));
		$added = 0;

		foreach (array_unique($productIds) as $productId)
		{
			$productId = (int)$productId;
			if ($productId <= 0 || isset($existing[$productId]))
			{
				continue;
			}

			$addResult = $this->add($userId, $productId);
			if ($addResult->isSuccess())
			{
				$existing[$productId] = true;
				$added++;
			}
		}

		return $added;
	}

	public function deleteByProductId(int $productId): DeleteResult|Result
	{
		$result = new Result();

		if ($productId <= 0)
		{
			return $result;
		}

		$rows = FavoritesTable::getList([
			'select' => ['ID'],
			'filter' => ['=PRODUCT_ID' => $productId],
		])->fetchAll();

		foreach ($rows as $row)
		{
			$deleteResult = FavoritesTable::delete((int)$row['ID']);
			if (!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	/**
	 * @return list<int>
	 */
	public function getUserIdsByProductId(int $productId): array
	{
		if ($productId <= 0)
		{
			return [];
		}

		$rows = FavoritesTable::getList([
			'select' => ['USER_ID'],
			'filter' => ['=PRODUCT_ID' => $productId],
		])->fetchAll();

		$userIds = [];
		foreach ($rows as $row)
		{
			$userId = (int)($row['USER_ID'] ?? 0);
			if ($userId > 0)
			{
				$userIds[] = $userId;
			}
		}

		return array_values(array_unique($userIds));
	}

	public function countByProductId(int $productId): int
	{
		if ($productId <= 0)
		{
			return 0;
		}

		return FavoritesTable::getCount(['=PRODUCT_ID' => $productId]);
	}
}
