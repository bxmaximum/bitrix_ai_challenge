<?php

declare(strict_types=1);

namespace Vendor\Favorites\Controller;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Vendor\Favorites\Service\FavoritesService;

/**
 * REST API избранного: favorites.add, favorites.remove, favorites.list, favorites.getProducts.
 *
 * Вызов через BX.ajax.runAction('vendor:favorites.favorites.add', { data: { productId: 1 } }).
 */
final class Favorites extends Controller
{
    /**
     * @return array<string, mixed>
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
            // BX.ajax.runAction по умолчанию отправляет POST.
            'list' => [
                '-prefilters' => [
                    ActionFilter\Csrf::class,
                ],
            ],
            'getProducts' => [
                '-prefilters' => [
                    ActionFilter\Csrf::class,
                ],
            ],
            'getCounts' => [
                '-prefilters' => [
                    ActionFilter\Csrf::class,
                ],
            ],
        ];
    }

    /**
     * POST favorites.add — добавить товар в избранное.
     */
    public function addAction(int $productId, FavoritesService $favoritesService): ?array
    {
        $result = $favoritesService->add($productId);

        if (!$result->isSuccess()) {
            $this->addErrors($result->getErrors());

            return null;
        }

        return [
            'success' => true,
            'productId' => $productId,
            'isFavorite' => true,
            'favoriteCount' => $favoritesService->getFavoriteCountAfterMutation($productId, true),
        ];
    }

    /**
     * POST favorites.remove — удалить товар из избранного.
     */
    public function removeAction(int $productId, FavoritesService $favoritesService): ?array
    {
        $result = $favoritesService->remove($productId);

        if (!$result->isSuccess()) {
            $this->addErrors($result->getErrors());

            return null;
        }

        return [
            'success' => true,
            'productId' => $productId,
            'isFavorite' => false,
            'favoriteCount' => $favoritesService->getFavoriteCountAfterMutation($productId, false),
        ];
    }

    /**
     * GET favorites.list — список ID избранных товаров.
     *
     * @return array{ids: list<int>}
     */
    public function listAction(FavoritesService $favoritesService): array
    {
        if (!$favoritesService->isEnabled()) {
            return ['ids' => []];
        }

        return ['ids' => $favoritesService->getList()];
    }

    /**
     * Счётчики добавлений для списка товаров (для синхронизации UI).
     *
     * @param list<int> $productIds
     *
     * @return array{counts: array<int, int>}
     */
    public function getCountsAction(array $productIds, FavoritesService $favoritesService): array
    {
        if (!$favoritesService->isEnabled()) {
            return ['counts' => []];
        }

        return ['counts' => $favoritesService->getFavoriteCounts($productIds)];
    }

    /**
     * GET favorites.getProducts — список товаров с данными.
     *
     * @return array{products: list<array<string, mixed>>}
     */
    public function getProductsAction(FavoritesService $favoritesService): array
    {
        if (!$favoritesService->isEnabled()) {
            return ['products' => []];
        }

        return ['products' => $favoritesService->getProducts()];
    }
}
