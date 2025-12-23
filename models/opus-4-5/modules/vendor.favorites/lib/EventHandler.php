<?php

declare(strict_types=1);

namespace Vendor\Favorites;

use Bitrix\Main\Loader;
use Vendor\Favorites\Model\FavoritesTable;
use Vendor\Favorites\Service\FavoritesService;

/**
 * Обработчик событий модуля
 *
 * Содержит статические методы для обработки событий:
 * - Авторизация пользователя (миграция избранного из cookie)
 * - Удаление товара из инфоблока
 * - Обновление товара в инфоблоке (инвалидация кэша)
 *
 * @package Vendor\Favorites
 */
final class EventHandler
{
    /**
     * Обработчик события авторизации пользователя
     *
     * Мигрирует избранное из cookie в базу данных.
     * Дубликаты автоматически исключаются.
     *
     * @param array $arParams Параметры события авторизации
     */
    public static function onAfterUserAuthorize(array $arParams): void
    {
        // Проверяем успешность авторизации
        if (empty($arParams['user_fields']['ID'])) {
            return;
        }

        // Проверяем, что это не запоминание пользователя
        if (!empty($arParams['remember'])) {
            return;
        }

        $userId = (int)$arParams['user_fields']['ID'];

        if ($userId <= 0) {
            return;
        }

        try {
            if (!Loader::includeModule('vendor.favorites')) {
                return;
            }

            $favoritesService = new FavoritesService();
            $favoritesService->migrateFromCookies($userId);
        } catch (\Throwable $e) {
            // Логируем ошибку, но не прерываем авторизацию
            self::logError('onAfterUserAuthorize', $e);
        }
    }

    /**
     * Обработчик события удаления элемента инфоблока
     *
     * Удаляет товар из избранного всех пользователей
     * и очищает связанные кэши.
     *
     * @param array $arFields Поля удаляемого элемента
     */
    public static function onAfterIBlockElementDelete(array $arFields): void
    {
        $productId = (int)($arFields['ID'] ?? 0);

        if ($productId <= 0) {
            return;
        }

        try {
            if (!Loader::includeModule('vendor.favorites')) {
                return;
            }

            // Получаем ID пользователей для инвалидации кэша
            $favoritesService = new FavoritesService();
            $favoritesService->clearCacheByProduct($productId);

            // Удаляем записи из таблицы
            FavoritesTable::removeByProductId($productId);
        } catch (\Throwable $e) {
            self::logError('onAfterIBlockElementDelete', $e);
        }
    }

    /**
     * Обработчик события обновления элемента инфоблока
     *
     * Инвалидирует кэш избранного для пользователей,
     * у которых данный товар в избранном.
     *
     * @param array $arFields Поля обновляемого элемента
     */
    public static function onAfterIBlockElementUpdate(array $arFields): void
    {
        $productId = (int)($arFields['ID'] ?? 0);

        if ($productId <= 0) {
            return;
        }

        try {
            if (!Loader::includeModule('vendor.favorites')) {
                return;
            }

            $favoritesService = new FavoritesService();
            $favoritesService->clearCacheByProduct($productId);
        } catch (\Throwable $e) {
            self::logError('onAfterIBlockElementUpdate', $e);
        }
    }

    /**
     * Логирует ошибку
     */
    private static function logError(string $method, \Throwable $e): void
    {
        $message = sprintf(
            '[vendor.favorites] %s error: %s in %s:%d',
            $method,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        // Используем встроенный механизм логирования Bitrix
        if (class_exists(\Bitrix\Main\Diag\Debug::class)) {
            \Bitrix\Main\Diag\Debug::writeToFile($message, '', 'vendor_favorites.log');
        }
    }
}





