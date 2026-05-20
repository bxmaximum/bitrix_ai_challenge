<?php

declare(strict_types=1);

namespace Vendor\Favorites\Config;

use Bitrix\Main\Config\Option;

/**
 * Настройки модуля из административной панели.
 */
final class ModuleOptions
{
    public const MODULE_ID = 'vendor.favorites';

    private const OPTION_IBLOCK_ID = 'iblock_id';
    private const OPTION_COOKIE_TTL = 'cookie_ttl';
    private const OPTION_ENABLED = 'enabled';

    private const DEFAULT_COOKIE_TTL = 2592000;

    public function isEnabled(): bool
    {
        return Option::get(self::MODULE_ID, self::OPTION_ENABLED, 'Y') === 'Y';
    }

    public function getIblockId(): int
    {
        return max(0, (int) Option::get(self::MODULE_ID, self::OPTION_IBLOCK_ID, '0'));
    }

    public function getCookieTtl(): int
    {
        $ttl = (int) Option::get(self::MODULE_ID, self::OPTION_COOKIE_TTL, (string) self::DEFAULT_COOKIE_TTL);

        return $ttl > 0 ? $ttl : self::DEFAULT_COOKIE_TTL;
    }
}
