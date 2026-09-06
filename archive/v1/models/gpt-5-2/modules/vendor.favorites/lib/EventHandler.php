<?php
declare(strict_types=1);

namespace Vendor\Favorites;

use Bitrix\Main\Loader;
use Vendor\Favorites\Service\FavoritesService;

final class EventHandler
{
	public static function onAfterUserAuthorize(array $params): void
	{
		if (!Loader::includeModule('vendor.favorites'))
		{
			return;
		}

		$userId = (int) ($params['user_fields']['ID'] ?? 0);
		if ($userId <= 0)
		{
			return;
		}

		(new FavoritesService())->migrateGuestFavoritesToUser($userId);
	}

	public static function onAfterIBlockElementDelete(array $fields): void
	{
		if (!Loader::includeModule('vendor.favorites'))
		{
			return;
		}

		$service = new FavoritesService();
		$catalogIblockId = $service->getCatalogIblockId();
		$iblockId = (int) ($fields['IBLOCK_ID'] ?? 0);
		if ($catalogIblockId > 0 && $iblockId !== $catalogIblockId)
		{
			return;
		}

		$productId = (int) ($fields['ID'] ?? 0);
		if ($productId <= 0)
		{
			return;
		}

		$service->deleteProductFromAll($productId);
		if ($iblockId > 0)
		{
			$service->invalidateByIblockId($iblockId);
		}
	}

	public static function onAfterIBlockElementUpdate(array &$fields): void
	{
		if (!Loader::includeModule('vendor.favorites'))
		{
			return;
		}

		$service = new FavoritesService();
		$catalogIblockId = $service->getCatalogIblockId();
		$iblockId = (int) ($fields['IBLOCK_ID'] ?? 0);
		if ($catalogIblockId > 0 && $iblockId !== $catalogIblockId)
		{
			return;
		}

		if ($iblockId > 0)
		{
			$service->invalidateByIblockId($iblockId);
		}
	}
}


