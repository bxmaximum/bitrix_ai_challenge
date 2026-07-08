<?php

declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\Application;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Vendor\Favorites\Config\ModuleOptions;
use Vendor\Favorites\Repository\FavoritesRepository;

Loc::loadMessages(__FILE__);

/**
 * Business logic for product favorites (authorized + guest).
 */
final class FavoritesService
{
	private const CACHE_DIR = '/vendor/favorites';

	public function __construct(
		private readonly ModuleOptions $options,
		private readonly FavoritesRepository $repository,
		private readonly CookieService $cookieService,
		private readonly ProductService $productService,
	) {
		Loader::includeModule('iblock');
	}

	public function ensureEnabled(): Result
	{
		$result = new Result();

		if (!$this->options->isEnabled())
		{
			$result->addError(new Error(
				(string)Loc::getMessage('VENDOR_FAVORITES_ERROR_DISABLED'),
				'MODULE_DISABLED'
			));

			return $result;
		}

		if (!Loader::includeModule('iblock'))
		{
			$result->addError(new Error(
				(string)Loc::getMessage('VENDOR_FAVORITES_ERROR_IBLOCK_REQUIRED'),
				'IBLOCK_REQUIRED'
			));
		}

		return $result;
	}

	public function add(int $productId): Result
	{
		$result = $this->ensureEnabled();
		if (!$result->isSuccess())
		{
			return $result;
		}

		$productId = $this->normalizeProductId($productId);
		if ($productId <= 0)
		{
			$result->addError(new Error(
				(string)Loc::getMessage('VENDOR_FAVORITES_ERROR_INVALID_PRODUCT'),
				'INVALID_PRODUCT_ID'
			));

			return $result;
		}

		if (!$this->productService->productExists($productId))
		{
			$result->addError(new Error(
				(string)Loc::getMessage('VENDOR_FAVORITES_ERROR_PRODUCT_NOT_FOUND'),
				'PRODUCT_NOT_FOUND'
			));

			return $result;
		}

		$userId = $this->getCurrentUserId();
		if ($userId > 0)
		{
			if ($this->repository->exists($userId, $productId))
			{
				$result->setData([
					'added' => false,
					'inFavorites' => true,
					'productId' => $productId,
					'count' => $this->getFavoritesCount(),
				]);

				return $result;
			}

			$addResult = $this->repository->add($userId, $productId);
			if (!$addResult->isSuccess())
			{
				$result->addErrors($addResult->getErrors());

				return $result;
			}

			$this->clearUserCache($userId);
		}
		else
		{
			$ids = $this->cookieService->getProductIds();
			if (!in_array($productId, $ids, true))
			{
				$ids[] = $productId;
				$this->cookieService->setProductIds($ids);
			}
		}

		$result->setData([
			'added' => true,
			'inFavorites' => true,
			'productId' => $productId,
			'count' => $this->getFavoritesCount(),
		]);

		return $result;
	}

	public function remove(int $productId): Result
	{
		$result = $this->ensureEnabled();
		if (!$result->isSuccess())
		{
			return $result;
		}

		$productId = $this->normalizeProductId($productId);
		if ($productId <= 0)
		{
			$result->addError(new Error(
				(string)Loc::getMessage('VENDOR_FAVORITES_ERROR_INVALID_PRODUCT'),
				'INVALID_PRODUCT_ID'
			));

			return $result;
		}

		$userId = $this->getCurrentUserId();
		if ($userId > 0)
		{
			$removeResult = $this->repository->remove($userId, $productId);
			if (!$removeResult->isSuccess())
			{
				$result->addErrors($removeResult->getErrors());

				return $result;
			}

			$this->clearUserCache($userId);
		}
		else
		{
			$ids = array_values(array_filter(
				$this->cookieService->getProductIds(),
				static fn (int $id): bool => $id !== $productId
			));
			$this->cookieService->setProductIds($ids);
		}

		$result->setData([
			'removed' => true,
			'inFavorites' => false,
			'productId' => $productId,
			'count' => $this->getFavoritesCount(),
		]);

		return $result;
	}

	/**
	 * @return list<int>
	 */
	public function getProductIds(): array
	{
		if (!$this->options->isEnabled())
		{
			return [];
		}

		$userId = $this->getCurrentUserId();
		if ($userId <= 0)
		{
			return $this->cookieService->getProductIds();
		}

		$cache = Application::getInstance()->getCache();
		$cacheId = $this->getUserListCacheId($userId);
		$cacheDir = $this->getUserCacheDir($userId);
		$ttl = $this->options->getListCacheTtl();

		if ($cache->initCache($ttl, $cacheId, $cacheDir))
		{
			$vars = $cache->getVars();

			return is_array($vars['ids'] ?? null) ? array_map('intval', $vars['ids']) : [];
		}

		if ($cache->startDataCache())
		{
			$ids = $this->repository->getProductIdsByUser($userId);
			$taggedCache = Application::getInstance()->getTaggedCache();
			$taggedCache->startTagCache($cacheDir);
			$taggedCache->registerTag($this->getUserTag($userId));
			$taggedCache->registerTag($this->getIblockTag());
			foreach ($ids as $productId)
			{
				$taggedCache->registerTag($this->getProductTag($productId));
			}
			$taggedCache->endTagCache();
			$cache->endDataCache(['ids' => $ids]);

			return $ids;
		}

		return [];
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function getProducts(): array
	{
		if (!$this->options->isEnabled())
		{
			return [];
		}

		$ids = $this->getProductIds();
		if ($ids === [])
		{
			return [];
		}

		$userId = $this->getCurrentUserId();
		$cache = Application::getInstance()->getCache();
		$cacheId = 'products_' . md5(implode(',', $ids) . '|' . $userId . '|' . $this->options->getIblockId());
		$cacheDir = self::CACHE_DIR . '/products';
		$ttl = $this->options->getProductsCacheTtl();

		if ($cache->initCache($ttl, $cacheId, $cacheDir))
		{
			$vars = $cache->getVars();

			return is_array($vars['items'] ?? null) ? $vars['items'] : [];
		}

		if ($cache->startDataCache())
		{
			$items = $this->productService->getProductsData($ids);
			$taggedCache = Application::getInstance()->getTaggedCache();
			$taggedCache->startTagCache($cacheDir);
			$taggedCache->registerTag($this->getIblockTag());
			if ($userId > 0)
			{
				$taggedCache->registerTag($this->getUserTag($userId));
			}
			foreach ($ids as $productId)
			{
				$taggedCache->registerTag($this->getProductTag($productId));
			}
			$taggedCache->endTagCache();
			$cache->endDataCache(['items' => $items]);

			return $items;
		}

		return [];
	}

	public function isInFavorites(int $productId): bool
	{
		$productId = $this->normalizeProductId($productId);
		if ($productId <= 0)
		{
			return false;
		}

		return in_array($productId, $this->getProductIds(), true);
	}

	/**
	 * Number of products in the current visitor's favorites (DB or cookie).
	 */
	public function getFavoritesCount(): int
	{
		return count($this->getProductIds());
	}

	/**
	 * Migrates guest cookie favorites into DB for the authorized user.
	 */
	public function migrateGuestFavorites(int $userId): Result
	{
		$result = new Result();

		if ($userId <= 0 || !$this->options->isEnabled())
		{
			return $result;
		}

		$guestIds = $this->cookieService->getProductIds();
		if ($guestIds === [])
		{
			return $result;
		}

		$existingProductIds = $this->productService->filterExistingProducts($guestIds);
		$added = $this->repository->addManyIgnoringDuplicates($userId, $existingProductIds);

		$this->cookieService->clear();
		$this->clearUserCache($userId);

		$result->setData([
			'migrated' => $added,
			'sourceCount' => count($guestIds),
		]);

		return $result;
	}

	public function removeProductFromAllUsers(int $productId): void
	{
		$productId = $this->normalizeProductId($productId);
		if ($productId <= 0)
		{
			return;
		}

		$userIds = $this->repository->getUserIdsByProductId($productId);
		$this->repository->deleteByProductId($productId);

		foreach ($userIds as $userId)
		{
			$this->clearUserCache($userId);
		}

		$this->clearProductCache($productId);
		$this->clearIblockCache();
	}

	public function invalidateProductCache(int $productId, ?int $iblockId = null): void
	{
		$productId = $this->normalizeProductId($productId);
		if ($productId > 0)
		{
			$this->clearProductCache($productId);

			$userIds = $this->repository->getUserIdsByProductId($productId);
			foreach ($userIds as $userId)
			{
				$this->clearUserCache($userId);
			}
		}

		$configuredIblockId = $this->options->getIblockId();
		if ($iblockId === null || $iblockId === $configuredIblockId || $configuredIblockId <= 0)
		{
			$this->clearIblockCache();
		}
	}

	public function clearUserCache(int $userId): void
	{
		if ($userId <= 0)
		{
			return;
		}

		$taggedCache = Application::getInstance()->getTaggedCache();
		$taggedCache->clearByTag($this->getUserTag($userId));
		Application::getInstance()->getCache()->cleanDir($this->getUserCacheDir($userId));
	}

	public function clearProductCache(int $productId): void
	{
		if ($productId <= 0)
		{
			return;
		}

		Application::getInstance()->getTaggedCache()->clearByTag($this->getProductTag($productId));
	}

	public function clearIblockCache(): void
	{
		Application::getInstance()->getTaggedCache()->clearByTag($this->getIblockTag());
		Application::getInstance()->getCache()->cleanDir(self::CACHE_DIR . '/products');
	}

	private function getCurrentUserId(): int
	{
		return (int)CurrentUser::get()->getId();
	}

	private function normalizeProductId(int $productId): int
	{
		return $productId > 0 ? $productId : 0;
	}

	private function getUserListCacheId(int $userId): string
	{
		return 'list_' . $userId . '_' . $this->options->getIblockId();
	}

	private function getUserCacheDir(int $userId): string
	{
		return self::CACHE_DIR . '/user/' . $userId;
	}

	private function getUserTag(int $userId): string
	{
		return 'vendor_favorites_user_' . $userId;
	}

	private function getProductTag(int $productId): string
	{
		return 'vendor_favorites_product_' . $productId;
	}

	private function getIblockTag(): string
	{
		$iblockId = $this->options->getIblockId();

		return $iblockId > 0
			? 'iblock_id_' . $iblockId
			: 'vendor_favorites_iblock_all';
	}
}
