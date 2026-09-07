<?php

declare(strict_types=1);

namespace Bxmax\Booking\Repository;

/**
 * Услуги студии. Источник — инфоблок с символьным кодом "services".
 */
final class ServiceRepository extends IblockRepository
{
	public const IBLOCK_CODE = 'services';

	/** @var array<int, array>|null */
	private ?array $cache = null;

	protected function getIblockCode(): string
	{
		return self::IBLOCK_CODE;
	}

	/**
	 * @return array<int, array{id:int,name:string,price:int,duration:int,description:string,picture:?string,alt:string}>
	 */
	public function getAll(): array
	{
		if ($this->cache !== null)
		{
			return $this->cache;
		}

		$dataClass = $this->dataClass();

		$rows = $dataClass::getList([
			'select' => [
				'ID',
				'NAME',
				'PREVIEW_TEXT',
				'PREVIEW_PICTURE',
				'PRICE_VALUE' => 'PRICE.VALUE',
				'DURATION_VALUE' => 'DURATION.VALUE',
				'PHOTO_ALT_VALUE' => 'PHOTO_ALT.VALUE',
			],
			'filter' => ['=ACTIVE' => 'Y'],
			'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
			'cache' => ['ttl' => 3600],
		])->fetchAll();

		$result = [];
		foreach ($rows as $row)
		{
			$id = (int)$row['ID'];
			$result[$id] = [
				'id' => $id,
				'name' => (string)$row['NAME'],
				'price' => (int)round((float)$row['PRICE_VALUE']),
				'duration' => (int)round((float)$row['DURATION_VALUE']),
				'description' => (string)$row['PREVIEW_TEXT'],
				'picture' => self::pictureUrl($row['PREVIEW_PICTURE'] ?? null),
				'alt' => (string)($row['PHOTO_ALT_VALUE'] ?? ''),
			];
		}

		return $this->cache = $result;
	}

	public function find(int $id): ?array
	{
		return $this->getAll()[$id] ?? null;
	}

	public function exists(int $id): bool
	{
		return $this->find($id) !== null;
	}
}
