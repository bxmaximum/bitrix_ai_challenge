<?php

declare(strict_types=1);

namespace Vendor\Favorites;

use Vendor\Favorites\Config\ModuleConfig;
use Vendor\Favorites\Repository\FavoritesRepository;
use Vendor\Favorites\Service\FavoritesService;

/**
 * Обработчики событий ядра (регистрация в install/index.php).
 */
final class EventHandler
{
    /**
     * main: OnAfterUserAuthorize — перенос избранного из cookie в БД.
     *
     * @param array{user_fields?: array{ID?: int|string}} $params
     */
    public static function onAfterUserAuthorize(array $params): void
    {
        if (!ModuleConfig::isEnabled()) {
            return;
        }

        $userId = isset($params['user_fields']['ID']) ? (int) $params['user_fields']['ID'] : 0;
        if ($userId <= 0) {
            return;
        }

        $service = new FavoritesService();
        $service->migrateGuestCookieToUser($userId);
    }

    /**
     * iblock: OnAfterIBlockElementDelete — удалить товар из избранного у всех.
     *
     * @param array{ID?: int|string, IBLOCK_ID?: int|string} $fields
     */
    public static function onAfterIBlockElementDelete(array $fields): void
    {
        if (!ModuleConfig::isEnabled()) {
            return;
        }

        $iblockId = ModuleConfig::getCatalogIblockId();
        if ($iblockId <= 0 || (int) ($fields['IBLOCK_ID'] ?? 0) !== $iblockId) {
            return;
        }

        $elementId = (int) ($fields['ID'] ?? 0);
        if ($elementId <= 0) {
            return;
        }

        $repo = new FavoritesRepository();
        $repo->deleteByProductId($elementId);
        FavoritesService::invalidateByElementId($elementId);
    }

    /**
     * iblock: OnAfterIBlockElementUpdate — сброс кэша по тегу элемента.
     *
     * @param array{ID?: int|string, IBLOCK_ID?: int|string} $fields
     */
    public static function onAfterIBlockElementUpdate(array &$fields): void
    {
        if (!ModuleConfig::isEnabled()) {
            return;
        }

        $iblockId = ModuleConfig::getCatalogIblockId();
        if ($iblockId <= 0 || (int) ($fields['IBLOCK_ID'] ?? 0) !== $iblockId) {
            return;
        }

        $elementId = (int) ($fields['ID'] ?? 0);
        if ($elementId <= 0) {
            return;
        }

        FavoritesService::invalidateByElementId($elementId);
    }
}
