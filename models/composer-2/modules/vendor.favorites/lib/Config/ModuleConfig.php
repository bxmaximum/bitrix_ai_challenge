<?php

declare(strict_types=1);

namespace Vendor\Favorites\Config;

use Bitrix\Main\Config\Option;

/**
 * Настройки модуля из Option.
 */
final class ModuleConfig
{
    public const MODULE_ID = 'vendor.favorites';

    public static function isEnabled(): bool
    {
        return Option::get(self::MODULE_ID, 'module_enabled', 'Y') === 'Y';
    }

    public static function getCatalogIblockId(): int
    {
        return (int) Option::get(self::MODULE_ID, 'catalog_iblock_id', '0');
    }

    /**
     * Время жизни cookie гостя (секунды).
     */
    public static function getGuestCookieTtl(): int
    {
        $ttl = (int) Option::get(self::MODULE_ID, 'guest_cookie_ttl', '2592000');

        return max(60, $ttl);
    }

    public static function getListCacheTtl(): int
    {
        $ttl = (int) Option::get(self::MODULE_ID, 'list_cache_ttl', '3600');

        return max(60, $ttl);
    }
}
