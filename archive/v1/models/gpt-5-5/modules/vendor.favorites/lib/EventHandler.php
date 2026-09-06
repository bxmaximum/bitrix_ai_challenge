<?php

declare(strict_types=1);

namespace Vendor\Favorites;

use Bitrix\Main\Loader;
use Vendor\Favorites\Service\FavoritesService;

final class EventHandler
{
    /**
     * Migrates encrypted guest-cookie favorites to DB right after successful authorization.
     *
     * @param array<string, mixed> $params
     */
    public static function onAfterUserAuthorize(array $params): void
    {
        if (!Loader::includeModule('vendor.favorites')) {
            return;
        }

        $userFields = is_array($params['user_fields'] ?? null) ? $params['user_fields'] : [];
        $userId = (int)($userFields['ID'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        FavoritesService::create()->migrateGuestFavorites($userId);
    }

    /**
     * @param array<string, mixed> $fields
     */
    public static function onAfterIBlockElementDelete(array $fields): void
    {
        if (!Loader::includeModule('vendor.favorites')) {
            return;
        }

        $productId = (int)($fields['ID'] ?? 0);
        if ($productId > 0) {
            FavoritesService::create()->removeProductFromAll($productId);
        }
    }

    /**
     * @param array<string, mixed> $fields
     */
    public static function onAfterIBlockElementUpdate(array $fields): void
    {
        if (!Loader::includeModule('vendor.favorites')) {
            return;
        }

        $productId = (int)($fields['ID'] ?? 0);
        if ($productId > 0) {
            FavoritesService::create()->invalidateProductCache($productId);
        }
    }
}
