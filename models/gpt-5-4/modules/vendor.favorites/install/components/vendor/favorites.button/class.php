<?php
declare(strict_types=1);

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Vendor\Favorites\Service\FavoritesService;
use Vendor\Favorites\Service\ModuleSettings;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Renders interactive favorites button for product detail pages.
 */
final class VendorFavoritesButtonComponent extends CBitrixComponent
{
    /**
     * Normalizes incoming component parameters.
     *
     * @param array<string, mixed> $arParams
     * @return array<string, mixed>
     */
    public function onPrepareComponentParams($arParams): array
    {
        $allowedSizes = ['small', 'medium', 'large'];
        $buttonSize = (string) ($arParams['BUTTON_SIZE'] ?? 'medium');

        $arParams['PRODUCT_ID'] = max(0, (int) ($arParams['PRODUCT_ID'] ?? 0));
        $arParams['SHOW_COUNTER'] = ($arParams['SHOW_COUNTER'] ?? 'N') === 'Y' ? 'Y' : 'N';
        $arParams['BUTTON_SIZE'] = in_array($buttonSize, $allowedSizes, true) ? $buttonSize : 'medium';

        return $arParams;
    }

    /**
     * Builds component result and renders the template.
     */
    public function executeComponent(): void
    {
        if (!Loader::includeModule('vendor.favorites')) {
            ShowError('Модуль vendor.favorites не установлен.');

            return;
        }

        $service = $this->getFavoritesService();
        $productId = (int) $this->arParams['PRODUCT_ID'];
        $state = $productId > 0 ? $service->buildState($productId) : [
            'isFavorite' => false,
            'totalCount' => 0,
        ];

        $this->arResult = [
            'PRODUCT_ID' => $productId,
            'IS_ENABLED' => ModuleSettings::isEnabled(),
            'IS_FAVORITE' => (bool) ($state['isFavorite'] ?? false),
            'COUNT' => (int) ($state['totalCount'] ?? 0),
            'SHOW_COUNTER' => $this->arParams['SHOW_COUNTER'] === 'Y',
            'BUTTON_SIZE' => (string) $this->arParams['BUTTON_SIZE'],
            'ACTION_ADD' => 'vendor:favorites.favorites.add',
            'ACTION_REMOVE' => 'vendor:favorites.favorites.remove',
            'ACTION_LIST' => 'vendor:favorites.favorites.list',
            'SESSID' => bitrix_sessid(),
        ];

        $this->includeComponentTemplate();
    }

    /**
     * Resolves favorites service from module container.
     */
    private function getFavoritesService(): FavoritesService
    {
        /** @var FavoritesService $service */
        $service = ServiceLocator::getInstance()->get(FavoritesService::class);

        return $service;
    }
}
