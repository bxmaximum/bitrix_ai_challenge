<?php

declare(strict_types=1);

namespace Vendor\Favorites\Config;

use Bitrix\Main\Config\Option;

/**
 * Typed access to module options.
 */
final class ModuleOptions
{
	public const MODULE_ID = 'vendor.favorites';

	public const OPTION_ENABLED = 'is_enabled';
	public const OPTION_IBLOCK_ID = 'iblock_id';
	public const OPTION_COOKIE_TTL = 'cookie_ttl';

	private const DEFAULT_COOKIE_TTL = 2_592_000; // 30 days
	private const CACHE_TTL = 3600;

	public function isEnabled(): bool
	{
		return Option::get(self::MODULE_ID, self::OPTION_ENABLED, 'Y') === 'Y';
	}

	public function getIblockId(): int
	{
		return max(0, (int)Option::get(self::MODULE_ID, self::OPTION_IBLOCK_ID, '0'));
	}

	public function getCookieTtl(): int
	{
		$ttl = (int)Option::get(self::MODULE_ID, self::OPTION_COOKIE_TTL, (string)self::DEFAULT_COOKIE_TTL);

		return $ttl > 0 ? $ttl : self::DEFAULT_COOKIE_TTL;
	}

	public function getListCacheTtl(): int
	{
		return self::CACHE_TTL;
	}

	public function getProductsCacheTtl(): int
	{
		return self::CACHE_TTL;
	}
}
