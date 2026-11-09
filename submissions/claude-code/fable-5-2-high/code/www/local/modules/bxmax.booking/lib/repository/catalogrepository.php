<?php

declare(strict_types=1);

namespace Bxmax\Booking\Repository;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\Iblock;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;

/**
 * Справочники студии из инфоблоков «Услуги» (services) и «Мастера» (masters).
 */
class CatalogRepository
{
	public const IBLOCK_SERVICES = 'services';
	public const IBLOCK_MASTERS = 'masters';

	/** @var array<string, int|null> */
	private array $iblockIds = [];

	public function __construct()
	{
		Loader::requireModule('iblock');
	}

	public function getIblockId(string $code): ?int
	{
		if (!array_key_exists($code, $this->iblockIds))
		{
			$row = IblockTable::getList([
				'select' => ['ID'],
				'filter' => ['=CODE' => $code, '=ACTIVE' => 'Y'],
				'limit' => 1,
				'cache' => ['ttl' => 3600],
			])->fetch();

			$this->iblockIds[$code] = $row ? (int)$row['ID'] : null;
		}

		return $this->iblockIds[$code];
	}

	/**
	 * @return list<array{id: int, name: string, description: string, price: int, duration: int, pictureId: int}>
	 */
	public function getServices(): array
	{
		return $this->fetchServices([]);
	}

	/**
	 * @return array{id: int, name: string, description: string, price: int, duration: int, pictureId: int}|null
	 */
	public function findService(int $id): ?array
	{
		if ($id <= 0)
		{
			return null;
		}

		return $this->fetchServices(['=ID' => $id])[0] ?? null;
	}

	/**
	 * @return list<array{id: int, name: string, specialization: string, experience: string, about: string, pictureId: int, serviceIds: list<int>}>
	 */
	public function getMasters(): array
	{
		return $this->fetchMasters([]);
	}

	/**
	 * @return array{id: int, name: string, specialization: string, experience: string, about: string, pictureId: int, serviceIds: list<int>}|null
	 */
	public function findMaster(int $id): ?array
	{
		if ($id <= 0)
		{
			return null;
		}

		return $this->fetchMasters(['=ID' => $id])[0] ?? null;
	}

	/**
	 * Названия элементов по ID без учёта активности: для списков админки и писем,
	 * где мастер или услуга могли быть сняты с публикации после записи.
	 *
	 * @param int[] $ids
	 * @return array<int, string>
	 */
	public function getNames(array $ids): array
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (!$ids)
		{
			return [];
		}

		$names = [];
		$rows = ElementTable::getList([
			'select' => ['ID', 'NAME'],
			'filter' => ['@ID' => $ids],
		]);
		while ($row = $rows->fetch())
		{
			$names[(int)$row['ID']] = (string)$row['NAME'];
		}

		return $names;
	}

	private function fetchServices(array $filter): array
	{
		$dataClass = $this->getDataClass(self::IBLOCK_SERVICES);
		if ($dataClass === null)
		{
			return [];
		}

		$rows = $dataClass::getList([
			'select' => [
				'ID', 'NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE',
				'PRICE_VALUE' => 'PRICE.VALUE',
				'DURATION_VALUE' => 'DURATION.VALUE',
			],
			'filter' => ['=ACTIVE' => 'Y'] + $filter,
			'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
		]);

		$services = [];
		while ($row = $rows->fetch())
		{
			$services[] = [
				'id' => (int)$row['ID'],
				'name' => (string)$row['NAME'],
				'description' => (string)$row['PREVIEW_TEXT'],
				'price' => (int)round((float)$row['PRICE_VALUE']),
				'duration' => (int)$row['DURATION_VALUE'],
				'pictureId' => (int)$row['PREVIEW_PICTURE'],
			];
		}

		return $services;
	}

	private function fetchMasters(array $filter): array
	{
		$dataClass = $this->getDataClass(self::IBLOCK_MASTERS);
		if ($dataClass === null)
		{
			return [];
		}

		$rows = $dataClass::getList([
			'select' => [
				'ID', 'NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE',
				'SPECIALIZATION_VALUE' => 'SPECIALIZATION.VALUE',
				'EXPERIENCE_VALUE' => 'EXPERIENCE.VALUE',
				// Множественное свойство: строка мастера повторяется на каждую услугу.
				'SERVICE_ID' => 'SERVICES.VALUE',
			],
			'filter' => ['=ACTIVE' => 'Y'] + $filter,
			'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
		]);

		$masters = [];
		while ($row = $rows->fetch())
		{
			$id = (int)$row['ID'];
			$masters[$id] ??= [
				'id' => $id,
				'name' => (string)$row['NAME'],
				'specialization' => (string)$row['SPECIALIZATION_VALUE'],
				'experience' => (string)$row['EXPERIENCE_VALUE'],
				'about' => (string)$row['PREVIEW_TEXT'],
				'pictureId' => (int)$row['PREVIEW_PICTURE'],
				'serviceIds' => [],
			];

			$serviceId = (int)$row['SERVICE_ID'];
			if ($serviceId > 0 && !in_array($serviceId, $masters[$id]['serviceIds'], true))
			{
				$masters[$id]['serviceIds'][] = $serviceId;
			}
		}

		return array_values($masters);
	}

	/**
	 * @return class-string<\Bitrix\Iblock\ORM\CommonElementTable>|null
	 */
	private function getDataClass(string $code): ?string
	{
		$iblockId = $this->getIblockId($code);
		if ($iblockId === null)
		{
			return null;
		}

		// Требует API_CODE у инфоблока: задаётся миграцией проекта.
		$dataClass = Iblock::wakeUp($iblockId)->getEntityDataClass();

		return $dataClass ?: null;
	}
}
