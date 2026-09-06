<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Vendor\Favorites\Service\FavoritesService;

/**
 * Кнопка «В избранное» с AJAX и анимацией.
 */
final class VendorFavoritesButtonComponent extends CBitrixComponent
{
    private const ALLOWED_SIZES = ['small', 'medium', 'large'];

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function onPrepareComponentParams($params): array
    {
        $params['PRODUCT_ID'] = max(0, (int) ($params['PRODUCT_ID'] ?? 0));
        $params['SHOW_COUNTER'] = (($params['SHOW_COUNTER'] ?? 'N') === 'Y') ? 'Y' : 'N';

        $size = strtolower((string) ($params['BUTTON_SIZE'] ?? 'medium'));
        $params['BUTTON_SIZE'] = in_array($size, self::ALLOWED_SIZES, true) ? $size : 'medium';

        return $params;
    }

    public function executeComponent(): void
    {
        $this->setFrameMode(true);

        if ($this->arParams['PRODUCT_ID'] <= 0) {
            return;
        }

        if (!Loader::includeModule('vendor.favorites')) {
            ShowError(Loc::getMessage('VENDOR_FAVORITES_BUTTON_MODULE_ERROR'));

            return;
        }

        /** @var FavoritesService $service */
        $service = ServiceLocator::getInstance()->get(FavoritesService::class);

        $productId = (int) $this->arParams['PRODUCT_ID'];

        $this->arResult = [
            'PRODUCT_ID' => $productId,
            'IS_FAVORITE' => $service->isFavorite($productId),
            'ENABLED' => $service->isEnabled(),
            'SHOW_COUNTER' => $this->arParams['SHOW_COUNTER'] === 'Y',
            'FAVORITE_COUNT' => $this->arParams['SHOW_COUNTER'] === 'Y'
                ? $service->getFavoriteCount($productId)
                : 0,
            'BUTTON_SIZE' => $this->arParams['BUTTON_SIZE'],
            'SIGNED_PARAMETERS' => $this->getSignedParameters(),
        ];

        $this->includeComponentTemplate();
    }
}
