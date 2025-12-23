<?php

declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Vendor\Favorites\Model\FavoritesTable;
use Vendor\Favorites\Repository\FavoritesRepository;

/**
 * Основной сервис для работы с избранным
 *
 * Реализует бизнес-логику добавления/удаления/получения избранного.
 * Автоматически определяет тип пользователя (авторизован/гость)
 * и использует соответствующее хранилище.
 *
 * @package Vendor\Favorites\Service
 */
final class FavoritesService
{
    private const MODULE_ID = 'vendor.favorites';
    private const CACHE_TAG_PREFIX = 'vendor_favorites_user_';

    private CookieService $cookieService;
    private FavoritesRepository $repository;
    private ?int $currentUserId = null;

    public function __construct(
        ?CookieService $cookieService = null,
        ?FavoritesRepository $repository = null
    ) {
        $this->cookieService = $cookieService ?? new CookieService();
        $this->repository = $repository ?? new FavoritesRepository();
    }

    /**
     * Добавляет товар в избранное
     */
    public function add(int $productId): Result
    {
        $result = new Result();

        // Проверяем, включен ли модуль
        if (!$this->isModuleEnabled()) {
            return $result->addError(new Error('Модуль избранного отключен', 'MODULE_DISABLED'));
        }

        // Валидация ID товара
        if ($productId <= 0) {
            return $result->addError(new Error('Некорректный ID товара', 'INVALID_PRODUCT_ID'));
        }

        // Получаем информацию о товаре и проверяем его существование
        $productInfo = $this->getProductInfo($productId);
        if (!$productInfo) {
            return $result->addError(new Error('Товар не найден', 'PRODUCT_NOT_FOUND'));
        }

        $userId = $this->getCurrentUserId();

        if ($userId > 0) {
            // Авторизованный пользователь - сохраняем в БД
            $addResult = $this->repository->add($userId, $productId, $productInfo['IBLOCK_ID']);

            if (!$addResult->isSuccess()) {
                // Проверяем, не дубликат ли это
                if ($this->repository->isInFavorites($userId, $productId)) {
                    return $result->addError(new Error('Товар уже в избранном', 'ALREADY_IN_FAVORITES'));
                }
                return $result->addErrors($addResult->getErrors());
            }

            // Инвалидируем кэш
            $this->clearUserCache($userId);
        } else {
            // Гость - сохраняем в cookie
            if (!$this->cookieService->addProduct($productId)) {
                return $result->addError(new Error('Товар уже в избранном', 'ALREADY_IN_FAVORITES'));
            }
        }

        $result->setData(['productId' => $productId, 'added' => true]);
        return $result;
    }

    /**
     * Удаляет товар из избранного
     */
    public function remove(int $productId): Result
    {
        $result = new Result();

        if ($productId <= 0) {
            return $result->addError(new Error('Некорректный ID товара', 'INVALID_PRODUCT_ID'));
        }

        $userId = $this->getCurrentUserId();

        if ($userId > 0) {
            // Авторизованный пользователь
            if (!$this->repository->remove($userId, $productId)) {
                return $result->addError(new Error('Товар не найден в избранном', 'NOT_IN_FAVORITES'));
            }

            // Инвалидируем кэш
            $this->clearUserCache($userId);
        } else {
            // Гость
            if (!$this->cookieService->removeProduct($productId)) {
                return $result->addError(new Error('Товар не найден в избранном', 'NOT_IN_FAVORITES'));
            }
        }

        $result->setData(['productId' => $productId, 'removed' => true]);
        return $result;
    }

    /**
     * Возвращает список ID избранных товаров
     *
     * @return int[]
     */
    public function getList(): array
    {
        $userId = $this->getCurrentUserId();

        if ($userId > 0) {
            return $this->getListWithCache($userId);
        }

        return $this->cookieService->getFavorites();
    }

    /**
     * Возвращает список избранных товаров с данными из инфоблока
     *
     * @return array<int, array{ID: int, NAME: string, DETAIL_PAGE_URL: string, PREVIEW_PICTURE: ?int}>
     */
    public function getProducts(): array
    {
        $productIds = $this->getList();

        if (empty($productIds)) {
            return [];
        }

        return $this->repository->getProductsData($productIds, $this->getConfiguredIblockId());
    }

    /**
     * Проверяет, находится ли товар в избранном
     */
    public function isInFavorites(int $productId): bool
    {
        $userId = $this->getCurrentUserId();

        if ($userId > 0) {
            return $this->repository->isInFavorites($userId, $productId);
        }

        return $this->cookieService->isInFavorites($productId);
    }

    /**
     * Мигрирует избранное из cookie в БД при авторизации
     */
    public function migrateFromCookies(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $cookieFavorites = $this->cookieService->getFavorites();

        if (empty($cookieFavorites)) {
            return 0;
        }

        $migratedCount = 0;
        $iblockId = $this->getConfiguredIblockId();

        foreach ($cookieFavorites as $productId) {
            // Проверяем существование товара
            $productInfo = $this->getProductInfo($productId);
            if (!$productInfo) {
                continue;
            }

            // Проверяем, нет ли уже такой записи
            if ($this->repository->isInFavorites($userId, $productId)) {
                continue;
            }

            // Добавляем в БД
            $addResult = $this->repository->add(
                $userId,
                $productId,
                $productInfo['IBLOCK_ID'] ?: $iblockId
            );

            if ($addResult->isSuccess()) {
                $migratedCount++;
            }
        }

        // Очищаем cookie после миграции
        $this->cookieService->clear();

        // Инвалидируем кэш пользователя
        $this->clearUserCache($userId);

        return $migratedCount;
    }

    /**
     * Возвращает количество добавлений товара в избранное
     */
    public function getProductFavoritesCount(int $productId): int
    {
        return FavoritesTable::getProductFavoritesCount($productId);
    }

    /**
     * Получает список избранного с кэшированием
     *
     * @return int[]
     */
    private function getListWithCache(int $userId): array
    {
        $cache = Application::getInstance()->getCache();
        $cacheId = 'favorites_list_' . $userId;
        $cachePath = '/vendor.favorites/user/';
        $cacheTtl = 3600;

        if ($cache->initCache($cacheTtl, $cacheId, $cachePath)) {
            $data = $cache->getVars();
            return $data['productIds'] ?? [];
        }

        $productIds = $this->repository->getListByUser($userId);

        if ($cache->startDataCache()) {
            $taggedCache = Application::getInstance()->getTaggedCache();
            $taggedCache->startTagCache($cachePath);
            $taggedCache->registerTag(self::CACHE_TAG_PREFIX . $userId);
            $taggedCache->endTagCache();

            $cache->endDataCache(['productIds' => $productIds]);
        }

        return $productIds;
    }

    /**
     * Очищает кэш избранного пользователя
     */
    public function clearUserCache(int $userId): void
    {
        $taggedCache = Application::getInstance()->getTaggedCache();
        $taggedCache->clearByTag(self::CACHE_TAG_PREFIX . $userId);
    }

    /**
     * Очищает кэш для всех пользователей, у которых товар в избранном
     */
    public function clearCacheByProduct(int $productId): void
    {
        // Получаем всех пользователей, у которых этот товар в избранном
        $userIds = $this->repository->getUserIdsByProduct($productId);

        $taggedCache = Application::getInstance()->getTaggedCache();
        foreach ($userIds as $userId) {
            $taggedCache->clearByTag(self::CACHE_TAG_PREFIX . $userId);
        }
    }

    /**
     * Получает информацию о товаре из инфоблока
     *
     * @return array{ID: int, IBLOCK_ID: int, NAME: string}|null
     */
    private function getProductInfo(int $productId): ?array
    {
        if (!Loader::includeModule('iblock')) {
            return null;
        }

        $iblockId = $this->getConfiguredIblockId();

        // Используем Bitrix\Iblock\Elements API для работы с инфоблоками
        $element = \Bitrix\Iblock\ElementTable::getRow([
            'filter' => [
                '=ID' => $productId,
                '=ACTIVE' => 'Y',
            ],
            'select' => ['ID', 'IBLOCK_ID', 'NAME'],
        ]);

        if (!$element) {
            return null;
        }

        // Если задан конкретный инфоблок, проверяем соответствие
        if ($iblockId > 0 && (int)$element['IBLOCK_ID'] !== $iblockId) {
            return null;
        }

        return [
            'ID' => (int)$element['ID'],
            'IBLOCK_ID' => (int)$element['IBLOCK_ID'],
            'NAME' => $element['NAME'],
        ];
    }

    /**
     * Получает ID текущего пользователя
     */
    private function getCurrentUserId(): int
    {
        if ($this->currentUserId !== null) {
            return $this->currentUserId;
        }

        global $USER;
        return $USER instanceof \CUser ? (int)$USER->GetID() : 0;
    }

    /**
     * Устанавливает ID пользователя (для тестирования)
     */
    public function setCurrentUserId(int $userId): void
    {
        $this->currentUserId = $userId;
    }

    /**
     * Проверяет, включен ли модуль
     */
    private function isModuleEnabled(): bool
    {
        return Option::get(self::MODULE_ID, 'enabled', 'Y') === 'Y';
    }

    /**
     * Получает ID инфоблока каталога из настроек
     */
    private function getConfiguredIblockId(): int
    {
        return (int)Option::get(self::MODULE_ID, 'iblock_id', '0');
    }
}





