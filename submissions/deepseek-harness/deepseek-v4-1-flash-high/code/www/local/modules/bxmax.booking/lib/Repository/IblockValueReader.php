<?php

namespace Bxmax\Booking\Repository;

use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;
use CFile;
use CIBlockElement;

/**
 * Read layer over the project iblocks ("services", "masters").
 *
 * Properties are read through CIBlockElement, which abstracts the storage
 * layout of the iblock (shared table b_iblock_element_prop_sN or the separate
 * b_iblock_element_property) and keeps the iblock cache warm.
 *
 * The reader is deliberately tolerant: if an iblock does not exist yet, the
 * landing still renders, just with empty lists.
 */
final class IblockValueReader
{
	/** @var array<string, int> */
	private static array $iblockIds = [];

	public static function getIblockId(string $code): int
	{
		if (array_key_exists($code, self::$iblockIds))
		{
			return self::$iblockIds[$code];
		}

		$iblockId = 0;
		if (Loader::includeModule('iblock'))
		{
			$row = IblockTable::getList([
				'select' => ['ID'],
				'filter' => ['=CODE' => $code],
				'order' => ['ID' => 'ASC'],
				'limit' => 1,
			])->fetch();

			$iblockId = $row ? (int)$row['ID'] : 0;
		}

		self::$iblockIds[$code] = $iblockId;

		return $iblockId;
	}

	/**
	 * Active elements of an iblock with their property values.
	 *
	 * @return array<int, array>
	 */
	public static function readAll(int $iblockId): array
	{
		if ($iblockId <= 0 || !Loader::includeModule('iblock'))
		{
			return [];
		}

		$items = [];
		$result = CIBlockElement::GetList(
			['SORT' => 'ASC', 'ID' => 'ASC'],
			['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y']
		);

		while ($element = $result->GetNextElement())
		{
			$fields = $element->GetFields();
			$fields['PROPERTIES'] = $element->GetProperties();
			$items[(int)$fields['ID']] = $fields;
		}

		return $items;
	}

	public static function propertyValue(array $element, string $code): string
	{
		$value = $element['PROPERTIES'][$code]['VALUE'] ?? '';

		return is_scalar($value) ? trim((string)$value) : '';
	}

	public static function propertyNumber(array $element, string $code): float
	{
		$property = $element['PROPERTIES'][$code] ?? null;
		if (!is_array($property))
		{
			return 0.0;
		}

		$value = $property['VALUE'] ?? '';

		return is_scalar($value) ? (float)$value : 0.0;
	}

	public static function propertyFileSrc(array $element, string $code): string
	{
		$fileId = (int)($element['PROPERTIES'][$code]['VALUE'] ?? 0);
		if ($fileId <= 0)
		{
			return '';
		}

		$file = CFile::GetFileArray($fileId);

		return is_array($file) ? (string)$file['SRC'] : '';
	}
}
