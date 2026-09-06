<?php
declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\Config\Option;

/**
 * Provides strongly-typed access to module settings.
 */
final class ModuleSettings
{
    public const MODULE_ID = 'vendor.favorites';
    public const OPTION_ENABLED = 'enabled';
    public const OPTION_IBLOCK_ID = 'iblock_id';
    public const OPTION_COOKIE_LIFETIME_DAYS = 'cookie_lifetime_days';
    public const DEFAULT_ENABLED = 'Y';
    public const DEFAULT_COOKIE_LIFETIME_DAYS = 30;
    public const DEFAULT_CACHE_TTL = 3600;

    /**
     * Returns whether favorites feature is enabled in module settings.
     */
    public static function isEnabled(): bool
    {
        return Option::get(self::MODULE_ID, self::OPTION_ENABLED, self::DEFAULT_ENABLED) === 'Y';
    }

    /**
     * Returns configured iblock id or 0 when module is not configured yet.
     */
    public static function getIblockId(): int
    {
        return max(
            0,
            (int) Option::get(self::MODULE_ID, self::OPTION_IBLOCK_ID, '0')
        );
    }

    /**
     * Returns guest cookie lifetime in days.
     */
    public static function getCookieLifetimeDays(): int
    {
        return max(
            1,
            (int) Option::get(
                self::MODULE_ID,
                self::OPTION_COOKIE_LIFETIME_DAYS,
                (string) self::DEFAULT_COOKIE_LIFETIME_DAYS
            )
        );
    }

    /**
     * Returns cache ttl for user favorites collections.
     */
    public static function getCacheTtl(): int
    {
        return self::DEFAULT_CACHE_TTL;
    }
}
