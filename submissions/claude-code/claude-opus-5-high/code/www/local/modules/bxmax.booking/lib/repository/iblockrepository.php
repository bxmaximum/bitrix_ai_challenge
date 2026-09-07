<?php

declare(strict_types=1);

namespace Bxmax\Booking\Repository;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\ORM\ElementEntity;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;

/**
 * Общая часть репозиториев, читающих инфоблоки через D7-сущность инфоблока.
 */
abstract class IblockRepository
{
	private ?ElementEntity $entity = null;
	private ?int $iblockId = null;

	abstract protected function getIblockCode(): string;

	public function getIblockId(): int
	{
		$this->resolve();

		return (int)$this->iblockId;
	}

	/**
	 * @return \Bitrix\Iblock\ORM\CommonElementTable|string
	 */
	protected function dataClass(): string
	{
		$this->resolve();

		return $this->entity->getDataClass();
	}

	private function resolve(): void
	{
		if ($this->entity !== null)
		{
			return;
		}

		if (!Loader::includeModule('iblock'))
		{
			throw new SystemException('Module "iblock" is not installed.');
		}

		$iblock = IblockTable::getList([
			'select' => ['ID', 'API_CODE'],
			'filter' => ['=CODE' => $this->getIblockCode()],
			'limit' => 1,
		])->fetchObject();

		if (!$iblock)
		{
			throw new SystemException(sprintf('Iblock "%s" not found.', $this->getIblockCode()));
		}

		$entity = IblockTable::compileEntity($iblock);
		if (!$entity)
		{
			throw new SystemException(
				sprintf('Iblock "%s" has no API_CODE, ORM entity can not be compiled.', $this->getIblockCode())
			);
		}

		$this->entity = $entity;
		$this->iblockId = (int)$iblock->getId();
	}

	protected static function pictureUrl(mixed $fileId): ?string
	{
		$fileId = (int)$fileId;
		if ($fileId <= 0)
		{
			return null;
		}

		$path = \CFile::GetPath($fileId);

		return $path !== false && $path !== null && $path !== '' ? $path : null;
	}
}
