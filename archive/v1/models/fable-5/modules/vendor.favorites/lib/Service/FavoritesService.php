<?php

declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\FileTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Vendor\Favorites\Repository\FavoritesRepository;

/**
 * Бизнес-логика «Избранного».
 *
 * Выбирает хранилище в зависимости от типа пользователя:
 * - авторизованный — БД (FavoritesRepository, ORM);
 * - гость — шифрованная cookie (CookieService).
 *
 * Списки кэшируются тегированным кэшем D7:
 * - vendor_favorites_user_{ID}      — сбрасывается при изменении избранного пользователя;
 * - vendor_favorites_iblock_{ID}    — сбрасывается при изменении/удалении элемента инфоблока.
 */
final class FavoritesService
{
    public const MODULE_ID = 'vendor.favorites';

    private const CACHE_TTL = 3600;
    private const CACHE_DIR = '/vendor_favorites';

    /** @var class-string<\Bitrix\Main\ORM\Data\DataManager>|null */
    private ?string $elementEntityClass = null;

    public function __construct(
        private readonly FavoritesRepository $repository,
        private readonly CookieService $cookieService,
    ) {
    }

    /**
     * Модуль включён в настройках?
     */
    public function isEnabled(): bool
    {
        return Option::get(self::MODULE_ID, 'enabled', 'Y') === 'Y';
    }

    /**
     * ID инфоблока каталога из настроек модуля.
     */
    public function getIblockId(): int
    {
        return (int)Option::get(self::MODULE_ID, 'iblock_id', '0');
    }

    /**
     * Добавляет товар в избранное текущего пользователя.
     */
    public function add(int $productId): Result
    {
        $result = $this->validateProduct($productId);
        if (!$result->isSuccess()) {
            return $result;
        }

        $userId = $this->getCurrentUserId();
        if ($userId > 0) {
            $result = $this->repository->add($userId, $productId);
            $this->clearUserCache($userId);

            return $result;
        }

        $ids = $this->cookieService->getProductIds();
        if (!in_array($productId, $ids, true)) {
            $ids[] = $productId;
            $this->cookieService->setProductIds($ids);
        }

        return new Result();
    }

    /**
     * Удаляет товар из избранного текущего пользователя.
     */
    public function remove(int $productId): Result
    {
        if ($productId <= 0) {
            return (new Result())->addError(new Error('Invalid product id', 'FAVORITES_INVALID_PRODUCT_ID'));
        }

        $userId = $this->getCurrentUserId();
        if ($userId > 0) {
            $result = $this->repository->remove($userId, $productId);
            $this->clearUserCache($userId);

            return $result;
        }

        $ids = array_values(array_diff($this->cookieService->getProductIds(), [$productId]));
        $this->cookieService->setProductIds($ids);

        return new Result();
    }

    /**
     * Список ID избранных товаров текущего пользователя.
     *
     * Для авторизованных результат кэшируется (тегированный кэш).
     *
     * @return int[]
     */
    public function getProductIds(): array
    {
        $userId = $this->getCurrentUserId();
        if ($userId === 0) {
            return $this->cookieService->getProductIds();
        }

        $cache = Cache::createInstance();
        $cacheId = 'user_list_' . $userId;
        $cacheDir = self::CACHE_DIR . '/user/' . $userId;

        if ($cache->initCache(self::CACHE_TTL, $cacheId, $cacheDir)) {
            /** @var int[] $ids */
            $ids = $cache->getVars()['ids'];

            return $ids;
        }

        $ids = $this->repository->getProductIds($userId);

        if ($cache->startDataCache()) {
            $taggedCache = Application::getInstance()->getTaggedCache();
            $taggedCache->startTagCache($cacheDir);
            $taggedCache->registerTag($this->getUserTag($userId));
            $taggedCache->registerTag($this->getIblockTag());
            $taggedCache->endTagCache();

            $cache->endDataCache(['ids' => $ids]);
        }

        return $ids;
    }

    /**
     * Товар в избранном текущего пользователя?
     */
    public function has(int $productId): bool
    {
        return in_array($productId, $this->getProductIds(), true);
    }

    /**
     * Сколько пользователей добавили товар в избранное.
     *
     * База: записи в БД. Текущий гость хранится в cookie и в БД не попадает,
     * поэтому его собственное добавление учитывается поверх счётчика.
     */
    public function getProductCounter(int $productId): int
    {
        $count = $this->repository->countByProduct($productId);

        if ($this->getCurrentUserId() === 0 && in_array($productId, $this->cookieService->getProductIds(), true)) {
            $count++;
        }

        return $count;
    }

    /**
     * Список избранных товаров с данными элементов инфоблока.
     *
     * Данные читаются через скомпилированную ORM-сущность инфоблока
     * (\Bitrix\Iblock\Elements\Element{ApiCode}Table) и кэшируются
     * с тегом инфоблока.
     *
     * @return array<int, array{ID: int, NAME: string, CODE: ?string, PREVIEW_TEXT: ?string, PICTURE_SRC: ?string}>
     */
    public function getProducts(): array
    {
        $ids = $this->getProductIds();
        if ($ids === [] || !Loader::includeModule('iblock')) {
            return [];
        }

        $cache = Cache::createInstance();
        $cacheId = 'products_' . md5(implode(',', $ids));
        $cacheDir = self::CACHE_DIR . '/products';

        if ($cache->initCache(self::CACHE_TTL, $cacheId, $cacheDir)) {
            /** @var array $products */
            $products = $cache->getVars()['products'];

            return $products;
        }

        $products = $this->loadProducts($ids);

        if ($cache->startDataCache()) {
            $taggedCache = Application::getInstance()->getTaggedCache();
            $taggedCache->startTagCache($cacheDir);
            $taggedCache->registerTag($this->getIblockTag());
            $taggedCache->endTagCache();

            $cache->endDataCache(['products' => $products]);
        }

        return $products;
    }

    /**
     * Миграция избранного гостя из cookie в БД при авторизации.
     * Дубликаты не создаются, cookie очищается.
     */
    public function migrateGuestFavorites(int $userId): Result
    {
        $result = new Result();
        if ($userId <= 0) {
            return $result;
        }

        $cookieIds = $this->cookieService->getProductIds();
        if ($cookieIds === []) {
            return $result;
        }

        $iblockId = $this->getIblockId();
        if ($iblockId > 0) {
            $cookieIds = $this->filterExistingProducts($cookieIds);
        }

        $result = $this->repository->addMany($userId, $cookieIds);
        $this->cookieService->clear();
        $this->clearUserCache($userId);

        return $result;
    }

    /**
     * Удаляет товар из избранного всех пользователей и сбрасывает их кэш.
     * Вызывается при удалении элемента инфоблока.
     */
    public function removeProductEverywhere(int $productId): void
    {
        $userIds = $this->repository->removeProductForAllUsers($productId);
        foreach ($userIds as $userId) {
            $this->clearUserCache($userId);
        }

        $this->clearIblockCache();
    }

    /**
     * Сбрасывает кэш, привязанный к инфоблоку каталога
     * (вызывается при изменении/удалении элемента).
     */
    public function clearIblockCache(): void
    {
        Application::getInstance()->getTaggedCache()->clearByTag($this->getIblockTag());
    }

    /**
     * Валидация товара: положительный ID + существование в инфоблоке каталога.
     */
    private function validateProduct(int $productId): Result
    {
        $result = new Result();

        if ($productId <= 0) {
            return $result->addError(new Error('Invalid product id', 'FAVORITES_INVALID_PRODUCT_ID'));
        }

        $iblockId = $this->getIblockId();
        if ($iblockId <= 0) {
            return $result->addError(new Error('Catalog iblock is not configured', 'FAVORITES_IBLOCK_NOT_CONFIGURED'));
        }

        if ($this->filterExistingProducts([$productId]) === []) {
            return $result->addError(new Error('Product not found', 'FAVORITES_PRODUCT_NOT_FOUND'));
        }

        return $result;
    }

    /**
     * Оставляет только ID существующих активных элементов инфоблока каталога.
     *
     * @param int[] $ids
     * @return int[]
     */
    private function filterExistingProducts(array $ids): array
    {
        if ($ids === [] || !Loader::includeModule('iblock')) {
            return [];
        }

        $rows = $this->getElementEntityClass()::getList([
            'select' => ['ID'],
            'filter' => $this->prepareElementFilter(['@ID' => $ids, '=ACTIVE' => 'Y']),
        ])->fetchAll();

        return array_map(static fn (array $row): int => (int)$row['ID'], $rows);
    }

    /**
     * Загружает данные товаров из инфоблока в исходном порядке $ids.
     *
     * @param int[] $ids
     */
    private function loadProducts(array $ids): array
    {
        $rows = $this->getElementEntityClass()::getList([
            'select' => ['ID', 'NAME', 'CODE', 'PREVIEW_TEXT', 'PREVIEW_PICTURE'],
            'filter' => $this->prepareElementFilter(['@ID' => $ids, '=ACTIVE' => 'Y']),
        ])->fetchAll();

        $pictures = $this->loadPictureSources(array_filter(array_map(
            static fn (array $row): int => (int)($row['PREVIEW_PICTURE'] ?? 0),
            $rows,
        )));

        $indexed = [];
        foreach ($rows as $row) {
            $pictureId = (int)($row['PREVIEW_PICTURE'] ?? 0);
            $indexed[(int)$row['ID']] = [
                'ID' => (int)$row['ID'],
                'NAME' => (string)$row['NAME'],
                'CODE' => $row['CODE'] !== null ? (string)$row['CODE'] : null,
                'PREVIEW_TEXT' => $row['PREVIEW_TEXT'] !== null ? (string)$row['PREVIEW_TEXT'] : null,
                'PICTURE_SRC' => $pictures[$pictureId] ?? null,
            ];
        }

        $products = [];
        foreach ($ids as $id) {
            if (isset($indexed[$id])) {
                $products[] = $indexed[$id];
            }
        }

        return $products;
    }

    /**
     * Класс ORM-сущности элементов каталога.
     *
     * Если у инфоблока задан API_CODE — используется скомпилированная
     * сущность \Bitrix\Iblock\Elements\Element{ApiCode}Table,
     * иначе — базовая \Bitrix\Iblock\ElementTable.
     *
     * @return class-string<\Bitrix\Main\ORM\Data\DataManager>
     */
    private function getElementEntityClass(): string
    {
        if ($this->elementEntityClass !== null) {
            return $this->elementEntityClass;
        }

        $iblock = \Bitrix\Iblock\Iblock::wakeUp($this->getIblockId());

        $this->elementEntityClass = $iblock->fillApiCode()
            ? $iblock->getEntityDataClass()
            : \Bitrix\Iblock\ElementTable::class;

        return $this->elementEntityClass;
    }

    /**
     * Для базовой ElementTable нужен явный фильтр по IBLOCK_ID.
     */
    private function prepareElementFilter(array $filter): array
    {
        if ($this->getElementEntityClass() === \Bitrix\Iblock\ElementTable::class) {
            $filter['=IBLOCK_ID'] = $this->getIblockId();
        }

        return $filter;
    }

    /**
     * Пути к файлам картинок по их ID (без старого ядра CFile).
     *
     * @param int[] $fileIds
     * @return array<int, string>
     */
    private function loadPictureSources(array $fileIds): array
    {
        if ($fileIds === []) {
            return [];
        }

        $uploadDir = Option::get('main', 'upload_dir', 'upload');

        $rows = FileTable::getList([
            'select' => ['ID', 'SUBDIR', 'FILE_NAME'],
            'filter' => ['@ID' => $fileIds],
        ])->fetchAll();

        $sources = [];
        foreach ($rows as $row) {
            $sources[(int)$row['ID']] = '/' . $uploadDir . '/' . $row['SUBDIR'] . '/' . $row['FILE_NAME'];
        }

        return $sources;
    }

    private function getCurrentUserId(): int
    {
        return (int)CurrentUser::get()->getId();
    }

    private function getUserTag(int $userId): string
    {
        return 'vendor_favorites_user_' . $userId;
    }

    private function getIblockTag(): string
    {
        return 'vendor_favorites_iblock_' . $this->getIblockId();
    }

    private function clearUserCache(int $userId): void
    {
        Application::getInstance()->getTaggedCache()->clearByTag($this->getUserTag($userId));
    }
}
