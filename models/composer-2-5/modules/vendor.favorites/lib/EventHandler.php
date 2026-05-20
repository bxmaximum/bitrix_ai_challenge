<?php

declare(strict_types=1);

namespace Vendor\Favorites;

use Bitrix\Main\Loader;
use Vendor\Favorites\Config\ModuleOptions;
use Vendor\Favorites\Repository\FavoritesRepository;
use Vendor\Favorites\Service\FavoritesService;

/**
 * Обработчики событий main и iblock.
 */
final class EventHandler
{
    /**
     * Миграция избранного гостя в БД при авторизации.
     *
     * @param array<string, mixed> $params
     */
    public static function onAfterUserAuthorize(array $params): void
    {
        if (!self::ensureModule()) {
            return;
        }

        $userId = (int) ($params['user_fields']['ID'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        self::favoritesService()->migrateGuestToUser($userId);
    }

    /**
     * Удаление товара из избранного всех пользователей.
     *
     * @param array<string, mixed> $fields
     */
    public static function onAfterIBlockElementDelete(array $fields): void
    {
        if (!self::ensureModule()) {
            return;
        }

        $productId = (int) ($fields['ID'] ?? 0);
        if ($productId <= 0) {
            return;
        }

        if (!self::isConfiguredIblock((int) ($fields['IBLOCK_ID'] ?? 0))) {
            return;
        }

        self::repository()->deleteByProductId($productId);
    }

    /**
     * Инвалидация кэша при изменении товара.
     *
     * @param array<string, mixed> $fields
     */
    public static function onAfterIBlockElementUpdate(array $fields): void
    {
        if (!self::ensureModule()) {
            return;
        }

        $productId = (int) ($fields['ID'] ?? 0);
        $iblockId = (int) ($fields['IBLOCK_ID'] ?? 0);

        if ($productId <= 0 || !self::isConfiguredIblock($iblockId)) {
            return;
        }

        self::repository()->clearProductCache($productId, $iblockId);
    }

    private static function ensureModule(): bool
    {
        return Loader::includeModule('vendor.favorites');
    }

    private static function isConfiguredIblock(int $iblockId): bool
    {
        $configuredId = self::options()->getIblockId();

        return $configuredId > 0 && $configuredId === $iblockId;
    }

    private static function options(): ModuleOptions
    {
        return \Bitrix\Main\DI\ServiceLocator::getInstance()->get(
            ModuleOptions::class,
        );
    }

    private static function favoritesService(): FavoritesService
    {
        return \Bitrix\Main\DI\ServiceLocator::getInstance()->get(
            FavoritesService::class,
        );
    }

    private static function repository(): FavoritesRepository
    {
        return \Bitrix\Main\DI\ServiceLocator::getInstance()->get(
            FavoritesRepository::class,
        );
    }
}
