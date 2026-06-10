<?php

declare(strict_types=1);

namespace Vendor\Favorites\Controller;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Request;
use Vendor\Favorites\Service\FavoritesService;

/**
 * REST-контроллер избранного.
 *
 * Доступен и гостям, и авторизованным пользователям
 * (хранилище выбирает FavoritesService).
 *
 * AJAX-эндпоинты:
 * - POST vendor:favorites.Favorites.add         { productId: int }
 * - POST vendor:favorites.Favorites.remove      { productId: int }
 * - GET  vendor:favorites.Favorites.list
 * - GET  vendor:favorites.Favorites.getProducts
 */
final class Favorites extends Controller
{
    private readonly FavoritesService $service;

    public function __construct(?Request $request = null)
    {
        parent::__construct($request);

        /** @var FavoritesService $service */
        $service = ServiceLocator::getInstance()->get('vendor.favorites.favoritesService');
        $this->service = $service;
    }

    /**
     * API доступен гостям, поэтому Authentication из дефолтных
     * префильтров исключён: хранилище выбирает FavoritesService.
     *
     * @inheritDoc
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
     * @inheritDoc
     */
    public function configureActions(): array
    {
        return [
            'add' => [
                '+prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                ],
            ],
            'remove' => [
                '+prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                ],
            ],
            'list' => [
                '+prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
                ],
                '-prefilters' => [
                    ActionFilter\Csrf::class,
                ],
            ],
            'getProducts' => [
                '+prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
                ],
                '-prefilters' => [
                    ActionFilter\Csrf::class,
                ],
            ],
            'status' => [
                '-prefilters' => [
                    ActionFilter\Csrf::class,
                ],
            ],
        ];
    }

    /**
     * Добавляет товар в избранное.
     *
     * @return array{added: bool, count: int}|null
     */
    public function addAction(int $productId): ?array
    {
        if (!$this->checkEnabled()) {
            return null;
        }

        $result = $this->service->add($productId);
        if (!$result->isSuccess()) {
            $this->addErrors($result->getErrors());

            return null;
        }

        return [
            'added' => true,
            'count' => $this->service->getProductCounter($productId),
        ];
    }

    /**
     * Удаляет товар из избранного.
     *
     * @return array{removed: bool, count: int}|null
     */
    public function removeAction(int $productId): ?array
    {
        if (!$this->checkEnabled()) {
            return null;
        }

        $result = $this->service->remove($productId);
        if (!$result->isSuccess()) {
            $this->addErrors($result->getErrors());

            return null;
        }

        return [
            'removed' => true,
            'count' => $this->service->getProductCounter($productId),
        ];
    }

    /**
     * Состояние товара для текущего пользователя.
     *
     * Используется компонентом для клиентской гидрации состояния кнопки:
     * страница может отдаваться из композитного/компонентного кэша,
     * поэтому персональное состояние запрашивается AJAX-ом.
     *
     * @return array{inFavorites: bool, count: int}|null
     */
    public function statusAction(int $productId): ?array
    {
        if (!$this->checkEnabled()) {
            return null;
        }

        if ($productId <= 0) {
            $this->addError(new Error('Invalid product id', 'FAVORITES_INVALID_PRODUCT_ID'));

            return null;
        }

        return [
            'inFavorites' => $this->service->has($productId),
            'count' => $this->service->getProductCounter($productId),
        ];
    }

    /**
     * Список ID избранных товаров текущего пользователя.
     *
     * @return array{ids: int[]}|null
     */
    public function listAction(): ?array
    {
        if (!$this->checkEnabled()) {
            return null;
        }

        return ['ids' => $this->service->getProductIds()];
    }

    /**
     * Список избранных товаров с данными элементов инфоблока.
     *
     * @return array{products: array}|null
     */
    public function getProductsAction(): ?array
    {
        if (!$this->checkEnabled()) {
            return null;
        }

        return ['products' => $this->service->getProducts()];
    }

    private function checkEnabled(): bool
    {
        if ($this->service->isEnabled()) {
            return true;
        }

        $this->addError(new Error('Favorites module is disabled', 'FAVORITES_DISABLED'));

        return false;
    }
}
