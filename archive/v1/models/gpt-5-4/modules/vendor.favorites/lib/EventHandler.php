<?php
declare(strict_types=1);

namespace Vendor\Favorites;

use Bitrix\Main\DI\ServiceLocator;
use Vendor\Favorites\Service\FavoritesService;
use Vendor\Favorites\Service\ModuleSettings;

/**
 * Handles system events required by favorites module.
 */
final class EventHandler
{
    /**
     * Migrates guest favorites to database after authorization.
     *
     * @param array<string, mixed> $params
     */
    public static function onAfterUserAuthorize(array $params): void
    {
        $userId = (int) (($params['user_fields']['ID'] ?? 0));
        if ($userId < 1) {
            return;
        }

        self::getFavoritesService()->migrateGuestFavoritesToUser($userId);
    }

    /**
     * Deletes removed product from all favorites and clears tagged cache.
     *
     * @param array<string, mixed> $fields
     */
    public static function onAfterIBlockElementDelete(array $fields): void
    {
        if ((int) ($fields['IBLOCK_ID'] ?? 0) !== ModuleSettings::getIblockId()) {
            return;
        }

        $productId = (int) ($fields['ID'] ?? 0);
        if ($productId < 1) {
            return;
        }

        self::getFavoritesService()->deleteProductFromAllFavorites($productId);
    }

    /**
     * Invalidates favorites cache when product changes.
     *
     * @param array<string, mixed> $fields
     */
    public static function onAfterIBlockElementUpdate(array &$fields): void
    {
        if (($fields['RESULT'] ?? true) === false) {
            return;
        }

        if ((int) ($fields['IBLOCK_ID'] ?? 0) !== ModuleSettings::getIblockId()) {
            return;
        }

        self::getFavoritesService()->invalidateIblockCache();
    }

    /**
     * Returns shared module service.
     */
    private static function getFavoritesService(): FavoritesService
    {
        /** @var FavoritesService $service */
        $service = ServiceLocator::getInstance()->get(FavoritesService::class);

        return $service;
    }
}
