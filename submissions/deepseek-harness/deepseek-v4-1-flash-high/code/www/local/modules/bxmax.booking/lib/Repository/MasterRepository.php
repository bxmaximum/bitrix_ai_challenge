<?php

namespace Bxmax\Booking\Repository;

/**
 * Master (специалист студии) as it is stored in the "masters" iblock.
 */
final class MasterRepository
{
	public const IBLOCK_CODE = 'masters';

	/** @var array<int, array>|null */
	private ?array $cache = null;

	public function isExists(int $masterId): bool
	{
		return isset($this->getAll()[$masterId]);
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

		$masters = [];
		foreach ($elements as $id => $element)
		{
			$masters[$id] = [
				'id' => $id,
				'name' => (string)$element['NAME'],
				'code' => (string)$element['CODE'],
				'specialization' => IblockValueReader::propertyValue($element, 'SPECIALIZATION'),
				'experience' => (int)IblockValueReader::propertyNumber($element, 'EXPERIENCE'),
				'about' => IblockValueReader::propertyValue($element, 'ABOUT'),
				'photoSrc' => IblockValueReader::propertyFileSrc($element, 'PHOTO'),
			];
		}

		$this->cache = $masters;

		return $masters;
	}

	/**
	 * @return array<int, int>
	 */
	public function getActiveIds(): array
	{
		return array_keys($this->getAll());
	}

	public function getById(int $masterId): ?array
	{
		return $this->getAll()[$masterId] ?? null;
	}
}
