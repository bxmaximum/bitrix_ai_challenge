<?php

declare(strict_types=1);

namespace Vendor\Favorites;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Diag\Logger;
use Vendor\Favorites\Service\FavoritesService;

/**
 * Обработчики событий модуля (регистрируются в install/index.php).
 *
 * - main:OnAfterUserAuthorize        — миграция избранного гостя из cookie в БД;
 * - iblock:OnAfterIBlockElementDelete — удаление товара из избранного всех пользователей;
 * - iblock:OnAfterIBlockElementUpdate — инвалидация тегированного кэша.
 */
final class EventHandler
{
    /**
     * Миграция избранного из cookie в БД при авторизации.
     *
     * @param array $params Массив события: ['user_fields' => [...], 'save' => bool, ...]
     */
    public static function onAfterUserAuthorize(array $params): void
    {
        $userId = (int)($params['user_fields']['ID'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        try {
            $service = self::getService();
            if (!$service->isEnabled()) {
                return;
            }

            $result = $service->migrateGuestFavorites($userId);
            if (!$result->isSuccess()) {
                self::logger()->warning('Guest favorites migration finished with errors', [
                    'userId' => $userId,
                    'errors' => $result->getErrorMessages(),
                ]);
            }
        } catch (\Throwable $exception) {
            self::logger()->error('Guest favorites migration failed', [
                'userId' => $userId,
                'exception' => $exception,
            ]);
        }
    }

    /**
     * Удаление товара из избранного всех пользователей
     * при удалении элемента инфоблока каталога.
     *
     * @param array $fields Поля удалённого элемента (ID, IBLOCK_ID, ...)
     */
    public static function onAfterIBlockElementDelete(array $fields): void
    {
        $elementId = (int)($fields['ID'] ?? 0);
        $iblockId = (int)($fields['IBLOCK_ID'] ?? 0);
        if ($elementId <= 0) {
            return;
        }

        try {
            $service = self::getService();
            if ($iblockId !== $service->getIblockId()) {
                return;
            }

            $service->removeProductEverywhere($elementId);
        } catch (\Throwable $exception) {
            self::logger()->error('Favorites cleanup on element delete failed', [
                'elementId' => $elementId,
                'exception' => $exception,
            ]);
        }
    }

    /**
     * Инвалидация кэша избранного при изменении элемента инфоблока каталога.
     *
     * @param array $fields Поля обновлённого элемента
     */
    public static function onAfterIBlockElementUpdate(array $fields): void
    {
        $iblockId = (int)($fields['IBLOCK_ID'] ?? 0);

        try {
            $service = self::getService();
            if ($iblockId !== $service->getIblockId()) {
                return;
            }

            $service->clearIblockCache();
        } catch (\Throwable $exception) {
            self::logger()->error('Favorites cache invalidation failed', [
                'elementId' => (int)($fields['ID'] ?? 0),
                'exception' => $exception,
            ]);
        }
    }

    private static function getService(): FavoritesService
    {
        /** @var FavoritesService */
        return ServiceLocator::getInstance()->get('vendor.favorites.favoritesService');
    }

    private static function logger(): \Psr\Log\LoggerInterface
    {
        return Logger::create('vendor.favorites');
    }
}
