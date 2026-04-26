<?php

declare(strict_types=1);

namespace Vendor\Favorites\Controller;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Vendor\Favorites\Service\FavoritesService;

final class Favorites extends Controller
{
    public function configureActions(): array
    {
        return [
            'add' => [
                '+prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                ],
                '-prefilters' => [ActionFilter\Authentication::class],
            ],
            'remove' => [
                '+prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                ],
                '-prefilters' => [ActionFilter\Authentication::class],
            ],
            'list' => [
                '+prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
                    new ActionFilter\CloseSession(),
                ],
                '-prefilters' => [
                    ActionFilter\Authentication::class,
                    ActionFilter\Csrf::class,
                ],
            ],
            'getProducts' => [
                '+prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
                    new ActionFilter\CloseSession(),
                ],
                '-prefilters' => [
                    ActionFilter\Authentication::class,
                    ActionFilter\Csrf::class,
                ],
            ],
        ];
    }

    /**
     * Endpoint: vendor:favorites.favorites.add
     *
     * @return array<string, mixed>|null
     */
    public function addAction(int $productId): ?array
    {
        $result = FavoritesService::create()->add($productId, $this->getUserId());
        if (!$result->isSuccess()) {
            $this->addErrors($result->getErrors());
            return null;
        }

        return $result->getData();
    }

    /**
     * Endpoint: vendor:favorites.favorites.remove
     *
     * @return array<string, mixed>|null
     */
    public function removeAction(int $productId): ?array
    {
        $result = FavoritesService::create()->remove($productId, $this->getUserId());
        if (!$result->isSuccess()) {
            $this->addErrors($result->getErrors());
            return null;
        }

        return $result->getData();
    }

    /**
     * Endpoint: vendor:favorites.favorites.list
     *
     * @return array{items: list<int>}
     */
    public function listAction(): array
    {
        return [
            'items' => FavoritesService::create()->getProductIds($this->getUserId()),
        ];
    }

    /**
     * Endpoint: vendor:favorites.favorites.getProducts
     *
     * @return array{items: list<array<string, mixed>>}
     */
    public function getProductsAction(): array
    {
        return [
            'items' => FavoritesService::create()->getProducts($this->getUserId()),
        ];
    }

    private function getUserId(): ?int
    {
        $userId = (int)$this->getCurrentUser()?->getId();

        return $userId > 0 ? $userId : null;
    }
}
