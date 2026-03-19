<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Vendor\Favorites\Service\FavoritesService;

final class VendorFavoritesButton extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['PRODUCT_ID'] = (int) ($arParams['PRODUCT_ID'] ?? 0);
        $arParams['SHOW_COUNTER'] = ($arParams['SHOW_COUNTER'] ?? 'N') === 'Y' ? 'Y' : 'N';
        $size = (string) ($arParams['BUTTON_SIZE'] ?? 'medium');
        $arParams['BUTTON_SIZE'] = in_array($size, ['small', 'medium', 'large'], true) ? $size : 'medium';
        $arParams['SYNC_STATE_ON_CLIENT'] = ($arParams['SYNC_STATE_ON_CLIENT'] ?? 'Y') === 'N' ? 'N' : 'Y';

        return $arParams;
    }

    public function executeComponent(): void
    {
        if (!Loader::includeModule('vendor.favorites')) {
            return;
        }

        if (!\Vendor\Favorites\Config\ModuleConfig::isEnabled()) {
            return;
        }

        if ($this->arParams['PRODUCT_ID'] <= 0) {
            return;
        }

        $this->arResult['PRODUCT_ID'] = $this->arParams['PRODUCT_ID'];
        $this->arResult['SYNC_STATE_ON_CLIENT'] = $this->arParams['SYNC_STATE_ON_CLIENT'];

        if ($this->arParams['SYNC_STATE_ON_CLIENT'] === 'Y') {
            $this->arResult['IN_FAVORITES'] = false;
            $this->arResult['FAVORITES_COUNT'] = 0;
        } else {
            $service = new FavoritesService();
            $user = CurrentUser::get();
            $this->arResult['IN_FAVORITES'] = $service->isFavorite($user, $this->arParams['PRODUCT_ID']);
            $this->arResult['FAVORITES_COUNT'] = $service->getFavoritesCount($user);
        }
        $this->arResult['BUTTON_SIZE'] = $this->arParams['BUTTON_SIZE'];
        $this->arResult['SHOW_COUNTER'] = $this->arParams['SHOW_COUNTER'];
        $this->arResult['AJAX_ACTION_BASE'] = 'vendor:favorites.Controller.Favorites';

        $this->includeComponentTemplate();
    }
}
