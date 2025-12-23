<?php

declare(strict_types=1);

namespace Vendor\Favorites\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Vendor\Favorites\Service\FavoritesService;

/**
 * REST API контроллер для работы с избранным
 *
 * Предоставляет методы:
 * - add: добавление товара в избранное
 * - remove: удаление товара из избранного
 * - list: получение списка ID избранных товаров
 * - getProducts: получение списка товаров с данными
 *
 * Все методы защищены CSRF-токеном.
 * Методы add и remove требуют HTTP POST.
 *
 * @package Vendor\Favorites\Controller
 */
final class Favorites extends Controller
{
    private FavoritesService $favoritesService;

    /**
     * Инициализация контроллера
     */
    protected function init(): void
    {
        parent::init();
        $this->favoritesService = new FavoritesService();
    }

    /**
     * Настройка фильтров по умолчанию
     *
     * По умолчанию:
     * - CSRF-защита включена для всех методов
     * - Аутентификация НЕ требуется (работает и для гостей)
     */
    protected function getDefaultPreFilters(): array
    {
        return [
            new ActionFilter\HttpMethod([
                ActionFilter\HttpMethod::METHOD_GET,
                ActionFilter\HttpMethod::METHOD_POST,
            ]),
            new ActionFilter\Csrf(),
        ];
    }

    /**
     * Конфигурация действий
     *
     * add и remove - только POST
     * list и getProducts - GET и POST
     */
    public function configureActions(): array
    {
        return [
            'add' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'remove' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'list' => [
                'prefilters' => [
                    new ActionFilter\Csrf(),
                ],
            ],
            'getProducts' => [
                'prefilters' => [
                    new ActionFilter\Csrf(),
                ],
            ],
            'check' => [
                'prefilters' => [
                    new ActionFilter\Csrf(),
                ],
            ],
        ];
    }

    /**
     * Добавляет товар в избранное
     *
     * @param int $productId ID товара
     * @return array|null
     *
     * @example
     * BX.ajax.runAction('vendor:favorites.Favorites.add', {
     *     data: { productId: 123 }
     * });
     */
    public function addAction(int $productId): ?array
    {
        if ($productId <= 0) {
            $this->addError(new Error('Некорректный ID товара', 'INVALID_PRODUCT_ID'));
            return null;
        }

        $result = $this->favoritesService->add($productId);

        if (!$result->isSuccess()) {
            $this->addErrors($result->getErrors());
            return null;
        }

        return [
            'success' => true,
            'productId' => $productId,
            'isInFavorites' => true,
            'count' => count($this->favoritesService->getList()),
        ];
    }

    /**
     * Удаляет товар из избранного
     *
     * @param int $productId ID товара
     * @return array|null
     *
     * @example
     * BX.ajax.runAction('vendor:favorites.Favorites.remove', {
     *     data: { productId: 123 }
     * });
     */
    public function removeAction(int $productId): ?array
    {
        if ($productId <= 0) {
            $this->addError(new Error('Некорректный ID товара', 'INVALID_PRODUCT_ID'));
            return null;
        }

        $result = $this->favoritesService->remove($productId);

        if (!$result->isSuccess()) {
            $this->addErrors($result->getErrors());
            return null;
        }

        return [
            'success' => true,
            'productId' => $productId,
            'isInFavorites' => false,
            'count' => count($this->favoritesService->getList()),
        ];
    }

    /**
     * Возвращает список ID избранных товаров
     *
     * @return array
     *
     * @example
     * BX.ajax.runAction('vendor:favorites.Favorites.list');
     */
    public function listAction(): array
    {
        $productIds = $this->favoritesService->getList();

        return [
            'productIds' => $productIds,
            'count' => count($productIds),
        ];
    }

    /**
     * Возвращает список избранных товаров с данными
     *
     * @return array
     *
     * @example
     * BX.ajax.runAction('vendor:favorites.Favorites.getProducts');
     */
    public function getProductsAction(): array
    {
        $products = $this->favoritesService->getProducts();

        return [
            'products' => $products,
            'count' => count($products),
        ];
    }

    /**
     * Проверяет, находится ли товар в избранном
     *
     * @param int $productId ID товара
     * @return array|null
     *
     * @example
     * BX.ajax.runAction('vendor:favorites.Favorites.check', {
     *     data: { productId: 123 }
     * });
     */
    public function checkAction(int $productId): ?array
    {
        if ($productId <= 0) {
            $this->addError(new Error('Некорректный ID товара', 'INVALID_PRODUCT_ID'));
            return null;
        }

        $isInFavorites = $this->favoritesService->isInFavorites($productId);

        return [
            'productId' => $productId,
            'isInFavorites' => $isInFavorites,
        ];
    }
}





