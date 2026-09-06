<?php

declare(strict_types=1);

namespace Vendor\Favorites;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Vendor\Favorites\Service\FavoritesService;

/**
 * Module event handlers registered in install/index.php.
 */
final class EventHandler
{
	/**
	 * Migrates guest favorites from CryptoCookie into DB after authorization.
	 *
	 * @param array<string, mixed> $fields
	 */
	public static function onAfterUserAuthorize(array $fields): void
	{
		$userId = (int)($fields['user_fields']['ID'] ?? $fields['ID'] ?? 0);
		if ($userId <= 0)
		{
			return;
		}

		$service = self::getFavoritesService();
		if ($service === null)
		{
			return;
		}

		$service->migrateGuestFavorites($userId);
	}

	/**
	 * Removes product from all favorites and invalidates related cache.
	 *
	 * @param array<string, mixed> $fields
	 */
	public static function onAfterIBlockElementDelete(array $fields): void
	{
		$productId = (int)($fields['ID'] ?? 0);
		if ($productId <= 0)
		{
			return;
		}

		$service = self::getFavoritesService();
		if ($service === null)
		{
			return;
		}

		$service->removeProductFromAllUsers($productId);
	}

	/**
	 * Invalidates favorites product cache after iblock element update.
	 *
	 * @param array<string, mixed> $fields
	 */
	public static function onAfterIBlockElementUpdate(array &$fields): void
	{
		$productId = (int)($fields['ID'] ?? 0);
		if ($productId <= 0)
		{
			return;
		}

		$service = self::getFavoritesService();
		if ($service === null)
		{
			return;
		}

		$iblockId = isset($fields['IBLOCK_ID']) ? (int)$fields['IBLOCK_ID'] : null;
		$service->invalidateProductCache($productId, $iblockId);
	}

	private static function getFavoritesService(): ?FavoritesService
	{
		if (!Loader::includeModule('vendor.favorites'))
		{
			return null;
		}

		$locator = ServiceLocator::getInstance();
		if (!$locator->has(FavoritesService::class))
		{
			return null;
		}

		/** @var FavoritesService $service */
		$service = $locator->get(FavoritesService::class);

		return $service;
	}
}
