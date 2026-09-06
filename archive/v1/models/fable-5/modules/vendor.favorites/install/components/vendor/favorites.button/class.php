<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Vendor\Favorites\Service\FavoritesService;

/**
 * Компонент vendor:favorites.button — кнопка «Избранное» для страницы товара.
 *
 * Параметры:
 * - PRODUCT_ID   int    — ID товара (обязательный);
 * - SHOW_COUNTER Y|N    — показывать счётчик добавлений;
 * - BUTTON_SIZE  small|medium|large — размер кнопки.
 *
 * Компонент «тонкий»: всю бизнес-логику выполняет FavoritesService.
 * Состояние «в избранном» зависит от пользователя, поэтому HTML не кэшируется —
 * данные сервиса уже закрыты тегированным кэшем D7.
 */
final class VendorFavoritesButtonComponent extends CBitrixComponent
{
    private const SIZES = ['small', 'medium', 'large'];

    /**
     * @param array $arParams
     */
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['PRODUCT_ID'] = (int)($arParams['PRODUCT_ID'] ?? 0);
        $arParams['SHOW_COUNTER'] = ($arParams['SHOW_COUNTER'] ?? 'N') === 'Y' ? 'Y' : 'N';

        $size = (string)($arParams['BUTTON_SIZE'] ?? 'medium');
        $arParams['BUTTON_SIZE'] = in_array($size, self::SIZES, true) ? $size : 'medium';

        return $arParams;
    }

    public function executeComponent(): void
    {
        if (!Loader::includeModule('vendor.favorites')) {
            ShowError('Module vendor.favorites is not installed');

            return;
        }

        if ($this->arParams['PRODUCT_ID'] <= 0) {
            return;
        }

        /** @var FavoritesService $service */
        $service = ServiceLocator::getInstance()->get('vendor.favorites.favoritesService');

        if (!$service->isEnabled()) {
            return;
        }

        $this->arResult['PRODUCT_ID'] = $this->arParams['PRODUCT_ID'];
        $this->arResult['IS_FAVORITE'] = $service->has($this->arParams['PRODUCT_ID']);
        $this->arResult['SHOW_COUNTER'] = $this->arParams['SHOW_COUNTER'] === 'Y';
        $this->arResult['COUNTER'] = $this->arResult['SHOW_COUNTER']
            ? $service->getProductCounter($this->arParams['PRODUCT_ID'])
            : 0;
        $this->arResult['BUTTON_SIZE'] = $this->arParams['BUTTON_SIZE'];

        $this->includeComponentTemplate();
    }
}
