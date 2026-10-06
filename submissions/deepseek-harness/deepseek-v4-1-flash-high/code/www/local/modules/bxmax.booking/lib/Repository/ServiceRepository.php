<?php

namespace Bxmax\Booking\Repository;

/**
 * Service (услуга) as it is stored in the "services" iblock.
 */
final class ServiceRepository
{
	public const IBLOCK_CODE = 'services';

	/** @var array<int, array>|null */
	private ?array $cache = null;

	public function isExists(int $serviceId): bool
	{
		return isset($this->getAll()[$serviceId]);
	}

	/**
	 * @return array<int, array>
	 */
	public function getAll(): array
	{
		if ($this->cache !== null)
		{
			return $this->cache;
		}

		$iblockId = IblockValueReader::getIblockId(self::IBLOCK_CODE);
		$elements = IblockValueReader::readAll($iblockId);

		$services = [];
		foreach ($elements as $id => $element)
		{
			$services[$id] = [
				'id' => $id,
				'name' => (string)$element['NAME'],
				'code' => (string)$element['CODE'],
				'price' => (int)IblockValueReader::propertyNumber($element, 'PRICE'),
				'duration' => (int)IblockValueReader::propertyNumber($element, 'DURATION'),
				'description' => IblockValueReader::propertyValue($element, 'DESCRIPTION'),
				'imageSrc' => IblockValueReader::propertyFileSrc($element, 'IMAGE'),
			];
		}

		$this->cache = $services;

		return $services;
	}

	public function getById(int $serviceId): ?array
	{
		return $this->getAll()[$serviceId] ?? null;
	}
}
