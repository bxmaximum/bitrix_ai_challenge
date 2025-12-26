<?php
declare(strict_types=1);

namespace Vendor\Favorites\Repository;

use Bitrix\Main\ORM\Query\Query;
use Vendor\Favorites\Model\FavoritesTable;

final class FavoritesRepository
{
	public function exists(int $userId, int $productId): bool
	{
		return (bool) FavoritesTable::getRow([
			'select' => ['ID'],
			'filter' => [
				'=USER_ID' => $userId,
				'=PRODUCT_ID' => $productId,
			],
		]);
	}

	public function add(int $userId, int $productId): bool
	{
		if ($this->exists($userId, $productId))
		{
			return false;
		}

		$result = FavoritesTable::add([
			'USER_ID' => $userId,
			'PRODUCT_ID' => $productId,
		]);

		return $result->isSuccess();
	}

	public function remove(int $userId, int $productId): void
	{
		$rows = FavoritesTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=USER_ID' => $userId,
				'=PRODUCT_ID' => $productId,
			],
		]);

		while ($row = $rows->fetch())
		{
			FavoritesTable::delete((int) $row['ID']);
		}
	}

	/**
	 * @return int[]
	 */
	public function getIdsByUserId(int $userId): array
	{
		$ids = [];
		$rows = FavoritesTable::getList([
			'select' => ['PRODUCT_ID'],
			'filter' => ['=USER_ID' => $userId],
			'order' => ['ID' => 'DESC'],
		]);

		while ($row = $rows->fetch())
		{
			$ids[] = (int) $row['PRODUCT_ID'];
		}

		return array_values(array_unique(array_filter($ids, static fn (int $id) => $id > 0)));
	}

	public function deleteByProductId(int $productId): void
	{
		$rows = FavoritesTable::getList([
			'select' => ['ID'],
			'filter' => ['=PRODUCT_ID' => $productId],
		]);

		while ($row = $rows->fetch())
		{
			FavoritesTable::delete((int) $row['ID']);
		}
	}

	public function addMany(int $userId, array $productIds): void
	{
		foreach ($productIds as $productId)
		{
			$productId = (int) $productId;
			if ($productId <= 0)
			{
				continue;
			}

			$this->add($userId, $productId);
		}
	}
}


