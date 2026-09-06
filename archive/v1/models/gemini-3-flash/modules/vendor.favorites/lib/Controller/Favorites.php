<?php

declare(strict_types=1);

namespace Vendor\Favorites\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Vendor\Favorites\Service\FavoritesService;

class Favorites extends Controller
{
    private FavoritesService $service;

    protected function init()
    {
        parent::init();
        $this->service = new FavoritesService();
    }

    protected function getDefaultPreFilters(): array
    {
        return [
            new ActionFilter\Csrf(),
            new ActionFilter\HttpMethod([
                ActionFilter\HttpMethod::METHOD_GET,
                ActionFilter\HttpMethod::METHOD_POST,
            ]),
        ];
    }

    public function addAction(int $productId): ?array
    {
        if ($productId <= 0) {
            $this->addError(new Error('Invalid product ID', 'INVALID_PRODUCT_ID'));
            return null;
        }

        if (!$this->service->add($productId)) {
            $this->addError(new Error('Failed to add product to favorites', 'ADD_FAILED'));
            return null;
        }

        return ['success' => true];
    }

    public function removeAction(int $productId): ?array
    {
        if ($productId <= 0) {
            $this->addError(new Error('Invalid product ID', 'INVALID_PRODUCT_ID'));
            return null;
        }

        if (!$this->service->remove($productId)) {
            $this->addError(new Error('Failed to remove product from favorites', 'REMOVE_FAILED'));
            return null;
        }

        return ['success' => true];
    }

    public function listAction(): array
    {
        return [
            'ids' => $this->service->getFavorites()
        ];
    }

    public function getProductsAction(): ?array
    {
        $ids = $this->service->getFavorites();
        if (empty($ids)) {
            return ['products' => []];
        }

        if (!Loader::includeModule('iblock')) {
            $this->addError(new Error('Iblock module not installed', 'IBLOCK_NOT_INSTALLED'));
            return null;
        }

        $products = [];
        $res = \Bitrix\Iblock\ElementTable::getList([
            'select' => ['ID', 'NAME', 'DETAIL_PAGE_URL' => 'IBLOCK.DETAIL_PAGE_URL'],
            'filter' => ['=ID' => $ids, '=ACTIVE' => 'Y'],
        ]);

        while ($item = $res->fetch()) {
            // Note: Detail page URL template processing might be needed here in a real app
            $products[] = [
                'id' => (int)$item['ID'],
                'name' => $item['NAME'],
            ];
        }

        return ['products' => $products];
    }
}

