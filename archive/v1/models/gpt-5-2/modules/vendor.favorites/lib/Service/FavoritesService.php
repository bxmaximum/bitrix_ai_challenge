<?php
declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Vendor\Favorites\Repository\FavoritesRepository;

final class FavoritesService
{
	private const CACHE_TTL_SECONDS = 3600;
	private const CACHE_PATH = '/vendor.favorites/';
	private const TAG_GLOBAL = 'vendor_favorites';

	public function __construct(
		private readonly FavoritesRepository $repository = new FavoritesRepository(),
		private readonly CookieService $cookieService = new CookieService(),
	)
	{
	}

	public function isEnabled(): bool
	{
		return Option::get('vendor.favorites', 'enabled', 'Y') === 'Y';
	}

	public function getCatalogIblockId(): int
	{
		return (int) Option::get('vendor.favorites', 'catalog_iblock_id', '0');
	}

	public function getGuestCookieTtlSeconds(): int
	{
		return max(60, (int) Option::get('vendor.favorites', 'cookie_ttl', (string) (60 * 60 * 24 * 30)));
	}

	public function add(int $productId, ?int $userId): bool
	{
		$this->guardEnabled();
		$this->guardProductId($productId);
		$this->guardProductExists($productId);

		if ($userId !== null && $userId > 0)
		{
			$added = $this->repository->add($userId, $productId);
			$this->invalidateUserCache($userId);
			return $added;
		}

		$ids = $this->cookieService->getIds();
		$ids[] = $productId;
		$this->cookieService->setIds($ids, $this->getGuestCookieTtlSeconds());

		return true;
	}

	public function remove(int $productId, ?int $userId): bool
	{
		$this->guardEnabled();
		$this->guardProductId($productId);

		if ($userId !== null && $userId > 0)
		{
			$this->repository->remove($userId, $productId);
			$this->invalidateUserCache($userId);
			return true;
		}

		$ids = array_values(array_filter(
			$this->cookieService->getIds(),
			static fn (int $id) => $id !== $productId
		));
		$this->cookieService->setIds($ids, $this->getGuestCookieTtlSeconds());

		return true;
	}

	/**
	 * @return int[]
	 */
	public function listIds(?int $userId): array
	{
		$this->guardEnabled();

		if ($userId !== null && $userId > 0)
		{
			return $this->getCachedUserFavoriteIds($userId);
		}

		return $this->cookieService->getIds();
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function getProducts(?int $userId): array
	{
		$this->guardEnabled();

		$ids = $this->listIds($userId);
		if ($ids === [])
		{
			return [];
		}

		if ($userId !== null && $userId > 0)
		{
			return $this->getCachedUserFavoriteProducts($userId, $ids);
		}

		return $this->loadProducts($ids);
	}

	public function migrateGuestFavoritesToUser(int $userId): void
	{
		$this->guardEnabled();

		$userId = (int) $userId;
		if ($userId <= 0)
		{
			return;
		}

		$guestIds = $this->cookieService->getIds();
		if ($guestIds === [])
		{
			return;
		}

		$validIds = [];
		foreach ($guestIds as $productId)
		{
			try
			{
				$this->guardProductExists($productId);
				$validIds[] = $productId;
			}
			catch (\Throwable)
			{
				// skip invalid/deleted products
			}
		}

		if ($validIds !== [])
		{
			$this->repository->addMany($userId, $validIds);
			$this->invalidateUserCache($userId);
		}

		$this->cookieService->clear();
	}

	public function deleteProductFromAll(int $productId): void
	{
		$productId = (int) $productId;
		if ($productId <= 0)
		{
			return;
		}

		$this->repository->deleteByProductId($productId);

		$this->invalidateAllCache();
	}

	public function invalidateByIblockId(int $iblockId): void
	{
		$iblockId = (int) $iblockId;
		if ($iblockId <= 0)
		{
			return;
		}

		Application::getInstance()->getTaggedCache()->clearByTag('iblock_id_' . $iblockId);
	}

	private function getCachedUserFavoriteIds(int $userId): array
	{
		$cache = Cache::createInstance();
		$cacheId = 'user_ids_' . $userId;

		if ($cache->initCache(self::CACHE_TTL_SECONDS, $cacheId, self::CACHE_PATH))
		{
			$vars = $cache->getVars();
			return is_array($vars['ids'] ?? null) ? $vars['ids'] : [];
		}

		if (!$cache->startDataCache())
		{
			return [];
		}

		$taggedCache = Application::getInstance()->getTaggedCache();
		$taggedCache->startTagCache(self::CACHE_PATH);
		$taggedCache->registerTag(self::TAG_GLOBAL);
		$taggedCache->registerTag($this->getUserTag($userId));
		$taggedCache->endTagCache();

		$ids = $this->repository->getIdsByUserId($userId);
		$cache->endDataCache(['ids' => $ids]);

		return $ids;
	}

	private function getCachedUserFavoriteProducts(int $userId, array $ids): array
	{
		$cache = Cache::createInstance();
		$cacheId = 'user_products_' . $userId . '_' . md5(implode(',', $ids));

		if ($cache->initCache(self::CACHE_TTL_SECONDS, $cacheId, self::CACHE_PATH))
		{
			$vars = $cache->getVars();
			return is_array($vars['items'] ?? null) ? $vars['items'] : [];
		}

		if (!$cache->startDataCache())
		{
			return [];
		}

		$iblockId = $this->getCatalogIblockId();
		$taggedCache = Application::getInstance()->getTaggedCache();
		$taggedCache->startTagCache(self::CACHE_PATH);
		$taggedCache->registerTag(self::TAG_GLOBAL);
		$taggedCache->registerTag($this->getUserTag($userId));
		if ($iblockId > 0)
		{
			$taggedCache->registerTag('iblock_id_' . $iblockId);
		}
		$taggedCache->endTagCache();

		$items = $this->loadProducts($ids);
		$cache->endDataCache(['items' => $items]);

		return $items;
	}

	private function invalidateUserCache(int $userId): void
	{
		$taggedCache = Application::getInstance()->getTaggedCache();
		$taggedCache->clearByTag($this->getUserTag($userId));
	}

	private function invalidateAllCache(): void
	{
		Application::getInstance()->getTaggedCache()->clearByTag(self::TAG_GLOBAL);
	}

	private function getUserTag(int $userId): string
	{
		return 'vendor_favorites_user_' . $userId;
	}

	private function guardEnabled(): void
	{
		if (!$this->isEnabled())
		{
			throw new SystemException('Favorites module is disabled.');
		}
	}

	private function guardProductId(int $productId): void
	{
		if ($productId <= 0)
		{
			throw new SystemException('Invalid PRODUCT_ID.');
		}
	}

	private function guardProductExists(int $productId): void
	{
		if (!Loader::includeModule('iblock'))
		{
			throw new SystemException('Iblock module is not available.');
		}

		$iblockId = $this->getCatalogIblockId();
		if ($iblockId <= 0)
		{
			throw new SystemException('Catalog iblock is not configured.');
		}

		$dataClass = \Bitrix\Iblock\Iblock::wakeUp($iblockId)->getEntityDataClass();
		$row = $dataClass::getRow([
			'select' => ['ID'],
			'filter' => [
				'=ID' => $productId,
				'=ACTIVE' => 'Y',
			],
		]);

		if (!$row)
		{
			throw new SystemException('Product not found.');
		}
	}

	/**
	 * @param int[] $ids
	 * @return array<int, array<string, mixed>>
	 */
	private function loadProducts(array $ids): array
	{
		if (!Loader::includeModule('iblock'))
		{
			return [];
		}

		$iblockId = $this->getCatalogIblockId();
		if ($iblockId <= 0)
		{
			return [];
		}

		$ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id) => $id > 0)));
		if ($ids === [])
		{
			return [];
		}

		$dataClass = \Bitrix\Iblock\Iblock::wakeUp($iblockId)->getEntityDataClass();
		$result = $dataClass::getList([
			'select' => ['ID', 'NAME', 'DETAIL_PAGE_URL'],
			'filter' => [
				'@ID' => $ids,
				'=ACTIVE' => 'Y',
			],
		]);

		$itemsById = [];
		while ($row = $result->fetch())
		{
			$itemsById[(int) $row['ID']] = [
				'ID' => (int) $row['ID'],
				'NAME' => (string) $row['NAME'],
				'DETAIL_PAGE_URL' => (string) ($row['DETAIL_PAGE_URL'] ?? ''),
			];
		}

		// keep original order, skip missing/deleted
		$items = [];
		foreach ($ids as $id)
		{
			if (isset($itemsById[$id]))
			{
				$items[] = $itemsById[$id];
			}
		}

		return $items;
	}
}


