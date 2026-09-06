<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Vendor\Favorites\Service\FavoritesService;

Loc::loadMessages(__FILE__);

final class VendorFavoritesButtonComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams): array
    {
        $size = (string)($arParams['BUTTON_SIZE'] ?? 'medium');
        if (!in_array($size, ['small', 'medium', 'large'], true)) {
            $size = 'medium';
        }

        return [
            'PRODUCT_ID' => max(0, (int)($arParams['PRODUCT_ID'] ?? 0)),
            'SHOW_COUNTER' => (($arParams['SHOW_COUNTER'] ?? 'N') === 'Y') ? 'Y' : 'N',
            'BUTTON_SIZE' => $size,
        ];
    }

    public function executeComponent(): void
    {
        if (!Loader::includeModule('vendor.favorites')) {
            ShowError((string)Loc::getMessage('VENDOR_FAVORITES_BUTTON_MODULE_NOT_INSTALLED'));
            return;
        }

        if ((int)$this->arParams['PRODUCT_ID'] <= 0) {
            ShowError((string)Loc::getMessage('VENDOR_FAVORITES_BUTTON_INVALID_PRODUCT'));
            return;
        }

        $service = FavoritesService::create();
        $productId = (int)$this->arParams['PRODUCT_ID'];
        $userId = $this->getUserId();

        $isFavorite = $service->isFavorite($productId, $userId);

        $this->arResult = [
            'PRODUCT_ID' => $productId,
            'IS_FAVORITE' => $isFavorite,
            'COUNTER' => $this->arParams['SHOW_COUNTER'] === 'Y' ? $service->countForDisplay($productId, $userId) : null,
            'SHOW_COUNTER' => $this->arParams['SHOW_COUNTER'] === 'Y',
            'BUTTON_SIZE' => $this->arParams['BUTTON_SIZE'],
        ];

        $this->includeComponentTemplate();
    }

    private function getUserId(): ?int
    {
        $userId = (int)CurrentUser::get()->getId();

        return $userId > 0 ? $userId : null;
    }
}
