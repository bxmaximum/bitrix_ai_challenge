<?php
declare(strict_types=1);

namespace Vendor\Favorites\Controller;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Vendor\Favorites\Service\FavoritesService;
use Vendor\Favorites\Service\ModuleSettings;

/**
 * Exposes favorites actions for AJAX and REST-like calls.
 */
final class Favorites extends Controller
{
    /**
     * Configures filters for each controller action.
     *
     * @return array<string, array<string, array<int, object|string>>>
     */
    public function configureActions(): array
    {
        return [
            'add' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                    new ActionFilter\CloseSession(),
                ],
            ],
            'remove' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                    new ActionFilter\CloseSession(),
                ],
            ],
            'list' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_GET,
                        ActionFilter\HttpMethod::METHOD_POST,
                    ]),
                    new ActionFilter\CloseSession(),
                ],
            ],
            'getProducts' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_GET,
                        ActionFilter\HttpMethod::METHOD_POST,
                    ]),
                    new ActionFilter\CloseSession(),
                ],
            ],
        ];
    }

    /**
     * Adds product to favorites.
     *
     * @return array<string, mixed>|null
     */
    public function addAction(int $productId): ?array
    {
        if ($productId < 1) {
            $this->addError(new Error('ID товара должен быть положительным числом.', 'INVALID_PRODUCT_ID'));

            return null;
        }

        try {
            return $this->getFavoritesService()->add($productId);
        } catch (\Throwable $exception) {
            $this->addError(new Error($exception->getMessage(), 'FAVORITES_ADD_FAILED'));

            return null;
        }
    }

    /**
     * Removes product from favorites.
     *
     * @return array<string, mixed>|null
     */
    public function removeAction(int $productId): ?array
    {
        if ($productId < 1) {
            $this->addError(new Error('ID товара должен быть положительным числом.', 'INVALID_PRODUCT_ID'));

            return null;
        }

        try {
            return $this->getFavoritesService()->remove($productId);
        } catch (\Throwable $exception) {
            $this->addError(new Error($exception->getMessage(), 'FAVORITES_REMOVE_FAILED'));

            return null;
        }
    }

    /**
     * Returns favorite product ids for current user or guest.
     *
     * @return array<string, mixed>
     */
    public function listAction(): array
    {
        $service = $this->getFavoritesService();
        $productIds = $service->getFavoriteProductIds();

        return [
            'enabled' => ModuleSettings::isEnabled(),
            'productIds' => $productIds,
            'count' => count($productIds),
        ];
    }

    /**
     * Returns favorite products payload for current user or guest.
     *
     * @return array<string, mixed>
     */
    public function getProductsAction(): array
    {
        $service = $this->getFavoritesService();
        $products = $service->getFavoriteProducts();

        return [
            'enabled' => ModuleSettings::isEnabled(),
            'items' => $products,
            'count' => count($products),
        ];
    }

    /**
     * Resolves module service from service locator.
     */
    private function getFavoritesService(): FavoritesService
    {
        /** @var FavoritesService $service */
        $service = ServiceLocator::getInstance()->get(FavoritesService::class);

        return $service;
    }
}
