<?php

declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Catalog\PriceTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\Iblock;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\FileTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Vendor\Favorites\Config\ModuleOptions;

/**
 * Catalog product helpers (existence checks and product DTO loading).
 */
final class ProductService
{
	public function __construct(
		private readonly ModuleOptions $options,
	) {
	}

	public function productExists(int $productId): bool
	{
		if ($productId <= 0)
		{
			return false;
		}

		$iblockId = $this->options->getIblockId();
		$entityClass = $this->getElementDataClass($iblockId);

		$filter = [
			'=ID' => $productId,
			'=ACTIVE' => 'Y',
		];

		if ($iblockId > 0)
		{
			$filter['=IBLOCK_ID'] = $iblockId;
		}

		$row = $entityClass::getList([
			'select' => ['ID'],
			'filter' => $filter,
			'limit' => 1,
		])->fetch();

		return $row !== false;
	}

	/**
	 * @param list<int> $productIds
	 * @return list<int>
	 */
	public function filterExistingProducts(array $productIds): array
	{
		$productIds = array_values(array_unique(array_filter(
			array_map(static fn ($id): int => (int)$id, $productIds),
			static fn (int $id): bool => $id > 0
		)));

		if ($productIds === [])
		{
			return [];
		}

		$iblockId = $this->options->getIblockId();
		$entityClass = $this->getElementDataClass($iblockId);

		$filter = [
			'@ID' => $productIds,
			'=ACTIVE' => 'Y',
		];

		if ($iblockId > 0)
		{
			$filter['=IBLOCK_ID'] = $iblockId;
		}

		$rows = $entityClass::getList([
			'select' => ['ID'],
			'filter' => $filter,
		])->fetchAll();

		$existing = [];
		foreach ($rows as $row)
		{
			$existing[] = (int)$row['ID'];
		}

		$existingMap = array_flip($existing);
		$result = [];
		foreach ($productIds as $id)
		{
			if (isset($existingMap[$id]))
			{
				$result[] = $id;
			}
		}

		return $result;
	}

	/**
	 * Loads product cards for favorites list. Prices are fetched in one query (no N+1).
	 *
	 * @param list<int> $productIds
	 * @return list<array{
	 *     id: int,
	 *     name: string,
	 *     code: string,
	 *     previewText: string,
	 *     detailPageUrl: string,
	 *     previewPicture: ?array{id: int, src: string},
	 *     price: ?array{value: float, currency: string}
	 * }>
	 */
	public function getProductsData(array $productIds): array
	{
		$productIds = array_values(array_unique(array_filter(
			array_map(static fn ($id): int => (int)$id, $productIds),
			static fn (int $id): bool => $id > 0
		)));

		if ($productIds === [])
		{
			return [];
		}

		$iblockId = $this->options->getIblockId();
		$entityClass = $this->getElementDataClass($iblockId);

		$filter = [
			'@ID' => $productIds,
			'=ACTIVE' => 'Y',
		];

		if ($iblockId > 0)
		{
			$filter['=IBLOCK_ID'] = $iblockId;
		}

		$rows = $entityClass::getList([
			'select' => [
				'ID',
				'NAME',
				'CODE',
				'PREVIEW_TEXT',
				'PREVIEW_PICTURE',
				'IBLOCK_ID',
				'IBLOCK_SECTION_ID',
			],
			'filter' => $filter,
		])->fetchAll();

		$byId = [];
		$pictureIds = [];
		$iblockIds = [];
		foreach ($rows as $row)
		{
			$id = (int)$row['ID'];
			$byId[$id] = $row;
			$pictureId = (int)($row['PREVIEW_PICTURE'] ?? 0);
			if ($pictureId > 0)
			{
				$pictureIds[$pictureId] = $pictureId;
			}
			$iblockIds[(int)($row['IBLOCK_ID'] ?? 0)] = true;
		}

		$urlTemplates = $this->loadDetailUrlTemplates(array_keys($iblockIds));
		$pictures = $this->loadPictures(array_values($pictureIds));
		$prices = $this->loadPrices($productIds);

		$result = [];
		foreach ($productIds as $productId)
		{
			if (!isset($byId[$productId]))
			{
				continue;
			}

			$row = $byId[$productId];
			$pictureId = (int)($row['PREVIEW_PICTURE'] ?? 0);
			$elementIblockId = (int)($row['IBLOCK_ID'] ?? 0);

			$result[] = [
				'id' => $productId,
				'name' => (string)($row['NAME'] ?? ''),
				'code' => (string)($row['CODE'] ?? ''),
				'previewText' => (string)($row['PREVIEW_TEXT'] ?? ''),
				'detailPageUrl' => $this->buildDetailUrl(
					$row,
					$urlTemplates[$elementIblockId] ?? ''
				),
				'previewPicture' => $pictures[$pictureId] ?? null,
				'price' => $prices[$productId] ?? null,
			];
		}

		return $result;
	}

	/**
	 * @return class-string<ElementTable>
	 */
	private function getElementDataClass(int $iblockId): string
	{
		if ($iblockId <= 0 || !Loader::includeModule('iblock'))
		{
			return ElementTable::class;
		}

		$iblock = Iblock::wakeUp($iblockId);
		$apiCode = (string)$iblock->fillApiCode();
		if ($apiCode === '')
		{
			return ElementTable::class;
		}

		IblockTable::compileEntity($iblock);
		$dataClass = $iblock->getEntityDataClass();

		return is_string($dataClass) && $dataClass !== '' ? $dataClass : ElementTable::class;
	}

	/**
	 * @param list<int> $fileIds
	 * @return array<int, array{id: int, src: string}>
	 */
	private function loadPictures(array $fileIds): array
	{
		if ($fileIds === [])
		{
			return [];
		}

		$rows = FileTable::getList([
			'select' => ['ID', 'SUBDIR', 'FILE_NAME'],
			'filter' => ['@ID' => $fileIds],
		])->fetchAll();

		$result = [];
		foreach ($rows as $row)
		{
			$id = (int)$row['ID'];
			$src = '/upload/' . trim((string)$row['SUBDIR'], '/') . '/' . (string)$row['FILE_NAME'];
			$src = str_replace('//', '/', $src);
			$result[$id] = [
				'id' => $id,
				'src' => $src,
			];
		}

		return $result;
	}

	/**
	 * @param list<int> $productIds
	 * @return array<int, array{value: float, currency: string}>
	 */
	private function loadPrices(array $productIds): array
	{
		if ($productIds === [] || !Loader::includeModule('catalog'))
		{
			return [];
		}

		$rows = PriceTable::getList([
			'select' => ['PRODUCT_ID', 'PRICE', 'CURRENCY'],
			'filter' => [
				'@PRODUCT_ID' => $productIds,
				'=CATALOG_GROUP_ID' => 1,
			],
		])->fetchAll();

		$result = [];
		foreach ($rows as $row)
		{
			$productId = (int)$row['PRODUCT_ID'];
			if (isset($result[$productId]))
			{
				continue;
			}

			$result[$productId] = [
				'value' => (float)$row['PRICE'],
				'currency' => (string)$row['CURRENCY'],
			];
		}

		return $result;
	}

	/**
	 * @param list<int> $iblockIds
	 * @return array<int, string>
	 */
	private function loadDetailUrlTemplates(array $iblockIds): array
	{
		$iblockIds = array_values(array_filter(array_map('intval', $iblockIds)));
		if ($iblockIds === [] || !Loader::includeModule('iblock'))
		{
			return [];
		}

		$rows = IblockTable::getList([
			'select' => ['ID', 'DETAIL_PAGE_URL'],
			'filter' => ['@ID' => $iblockIds],
			'cache' => ['ttl' => 3600],
		])->fetchAll();

		$result = [];
		foreach ($rows as $row)
		{
			$result[(int)$row['ID']] = (string)($row['DETAIL_PAGE_URL'] ?? '');
		}

		return $result;
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function buildDetailUrl(array $row, string $template): string
	{
		if ($template === '')
		{
			return '';
		}

		$replacements = [
			'#ID#' => (string)(int)($row['ID'] ?? 0),
			'#ELEMENT_ID#' => (string)(int)($row['ID'] ?? 0),
			'#CODE#' => rawurlencode((string)($row['CODE'] ?? '')),
			'#ELEMENT_CODE#' => rawurlencode((string)($row['CODE'] ?? '')),
			'#IBLOCK_ID#' => (string)(int)($row['IBLOCK_ID'] ?? 0),
			'#SECTION_ID#' => (string)(int)($row['IBLOCK_SECTION_ID'] ?? 0),
		];

		$url = str_replace(array_keys($replacements), array_values($replacements), $template);
		$url = preg_replace('/#[A-Z0-9_]+#/', '', $url) ?? $url;

		return (new Uri($url))->getUri();
	}
}
