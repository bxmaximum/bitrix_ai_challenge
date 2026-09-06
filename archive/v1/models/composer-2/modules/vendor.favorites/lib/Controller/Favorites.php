<?php

declare(strict_types=1);

namespace Vendor\Favorites\Controller;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Vendor\Favorites\Config\ModuleConfig;
use Vendor\Favorites\Service\FavoritesService;

/**
 * AJAX/REST API избранного (BX.ajax.runAction).
 */
final class Favorites extends Controller
{
    private ?FavoritesService $service = null;

    protected function init(): void
    {
        parent::init();
        Loader::includeModule(ModuleConfig::MODULE_ID);
        $this->service = new FavoritesService();
    }

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
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_GET,
                        ActionFilter\HttpMethod::METHOD_POST,
                    ]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'getProducts' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_GET,
                        ActionFilter\HttpMethod::METHOD_POST,
                    ]),
                    new ActionFilter\Csrf(),
                ],
            ],
        ];
    }

    public function addAction(int $productId = 0): ?array
    {
        if (!$this->checkEnabled()) {
            return null;
        }

        if ($productId <= 0) {
            $productId = (int) ($this->getRequest()->getPost('productId') ?? $this->getRequest()->get('productId'));
        }

        if ($productId <= 0) {
            $this->addError(new Error('Некорректный ID товара', 'INVALID_PRODUCT_ID'));

            return null;
        }

        $user = $this->getCurrentUser() ?? CurrentUser::get();
        $ids = $this->service->add($user, $productId);
        if ($ids === null) {
            $this->addError(new Error('Не удалось добавить товар или товар недоступен', 'ADD_FAILED'));

            return null;
        }

        return [
            'success' => true,
            'ids' => $ids,
        ];
    }

    public function removeAction(int $productId = 0): ?array
    {
        if (!$this->checkEnabled()) {
            return null;
        }

        if ($productId <= 0) {
            $productId = (int) ($this->getRequest()->getPost('productId') ?? $this->getRequest()->get('productId'));
        }

        if ($productId <= 0) {
            $this->addError(new Error('Некорректный ID товара', 'INVALID_PRODUCT_ID'));

            return null;
        }

        $user = $this->getCurrentUser() ?? CurrentUser::get();
        $ids = $this->service->remove($user, $productId);
        if ($ids === null) {
            $this->addError(new Error('Не удалось удалить товар', 'REMOVE_FAILED'));

            return null;
        }

        return [
            'success' => true,
            'ids' => $ids,
        ];
    }

    public function listAction(): ?array
    {
        if (!$this->checkEnabled()) {
            return null;
        }

        $user = $this->getCurrentUser() ?? CurrentUser::get();

        return [
            'ids' => $this->service->getFavoriteProductIds($user),
        ];
    }

    public function getProductsAction(): ?array
    {
        if (!$this->checkEnabled()) {
            return null;
        }

        $user = $this->getCurrentUser() ?? CurrentUser::get();

        return [
            'products' => $this->service->getProductsData($user),
        ];
    }

    private function checkEnabled(): bool
    {
        if (!ModuleConfig::isEnabled()) {
            $this->addError(new Error('Модуль избранного отключён', 'MODULE_DISABLED'));

            return false;
        }

        return true;
    }
}
