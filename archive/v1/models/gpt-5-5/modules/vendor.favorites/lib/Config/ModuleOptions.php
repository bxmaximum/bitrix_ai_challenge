<?php

declare(strict_types=1);

namespace Vendor\Favorites\Config;

use Bitrix\Main\Config\Option;

final class ModuleOptions
{
    public const MODULE_ID = 'vendor.favorites';
    public const OPTION_ENABLED = 'enabled';
    public const OPTION_CATALOG_IBLOCK_ID = 'catalog_iblock_id';
    public const OPTION_COOKIE_TTL = 'cookie_ttl';

    private const DEFAULT_COOKIE_TTL = 2_592_000;

    public static function isEnabled(): bool
    {
        return Option::get(self::MODULE_ID, self::OPTION_ENABLED, 'Y') === 'Y';
    }

    public static function getCatalogIblockId(): int
    {
        return max(0, (int)Option::get(self::MODULE_ID, self::OPTION_CATALOG_IBLOCK_ID, '0'));
    }

    public static function getCookieTtl(): int
    {
        return max(3600, (int)Option::get(self::MODULE_ID, self::OPTION_COOKIE_TTL, (string)self::DEFAULT_COOKIE_TTL));
    }
}
