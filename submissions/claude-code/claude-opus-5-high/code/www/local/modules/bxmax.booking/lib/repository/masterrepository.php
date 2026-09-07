<?php

declare(strict_types=1);

namespace Bxmax\Booking\Repository;

/**
 * Мастера студии. Источник — инфоблок с символьным кодом "masters".
 */
final class MasterRepository extends IblockRepository
{
	public const IBLOCK_CODE = 'masters';

	/** @var array<int, array>|null */
	private ?array $cache = null;

	protected function getIblockCode(): string
	{
		return self::IBLOCK_CODE;
	}

	/**
	 * @return array<int, array{id:int,name:string,speciality:string,experience:string,about:string,picture:?string,alt:string}>
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
				'SPECIALITY_VALUE' => 'SPECIALITY.VALUE',
				'EXPERIENCE_VALUE' => 'EXPERIENCE.VALUE',
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
				'speciality' => (string)($row['SPECIALITY_VALUE'] ?? ''),
				'experience' => (string)($row['EXPERIENCE_VALUE'] ?? ''),
				'about' => (string)$row['PREVIEW_TEXT'],
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

	public function getName(int $id): string
	{
		return (string)($this->find($id)['name'] ?? '');
	}
}
