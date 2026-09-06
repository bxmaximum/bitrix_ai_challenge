<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Vendor\Favorites\Service\FavoritesService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Компонент кнопки "Избранное"
 *
 * Отображает кнопку добавления/удаления товара из избранного
 * с анимацией и AJAX-взаимодействием.
 *
 * Параметры:
 * - PRODUCT_ID: ID товара (обязательный)
 * - SHOW_COUNTER: показывать счётчик добавлений (Y/N)
 * - BUTTON_SIZE: размер кнопки (small/medium/large)
 *
 * @package Vendor\Favorites\Components
 */
class VendorFavoritesButton extends CBitrixComponent
{
    private ?FavoritesService $favoritesService = null;

    /**
     * Выполняет компонент
     */
    public function executeComponent(): void
    {
        if (!$this->checkModules()) {
            return;
        }

        $this->initParams();

        if (!$this->validateParams()) {
            return;
        }

        $this->prepareResult();
        $this->includeComponentTemplate();
    }

    /**
     * Проверяет наличие необходимых модулей
     */
    private function checkModules(): bool
    {
        if (!Loader::includeModule('vendor.favorites')) {
            ShowError(Loc::getMessage('VENDOR_FAVORITES_BUTTON_MODULE_NOT_INSTALLED') ?: 'Модуль vendor.favorites не установлен');
            return false;
        }

        return true;
    }

    /**
     * Инициализирует параметры компонента
     */
    private function initParams(): void
    {
        $this->arParams['PRODUCT_ID'] = (int)($this->arParams['PRODUCT_ID'] ?? 0);
        $this->arParams['SHOW_COUNTER'] = ($this->arParams['SHOW_COUNTER'] ?? 'N') === 'Y';
        $this->arParams['BUTTON_SIZE'] = $this->arParams['BUTTON_SIZE'] ?? 'medium';

        // Валидация размера кнопки
        $allowedSizes = ['small', 'medium', 'large'];
        if (!in_array($this->arParams['BUTTON_SIZE'], $allowedSizes, true)) {
            $this->arParams['BUTTON_SIZE'] = 'medium';
        }
    }

    /**
     * Валидирует параметры компонента
     */
    private function validateParams(): bool
    {
        if ($this->arParams['PRODUCT_ID'] <= 0) {
            ShowError(Loc::getMessage('VENDOR_FAVORITES_BUTTON_INVALID_PRODUCT_ID') ?: 'Некорректный ID товара');
            return false;
        }

        return true;
    }

    /**
     * Подготавливает результат для шаблона
     */
    private function prepareResult(): void
    {
        $this->favoritesService = new FavoritesService();
        $productId = $this->arParams['PRODUCT_ID'];

        // Проверяем, в избранном ли товар
        $this->arResult['IS_IN_FAVORITES'] = $this->favoritesService->isInFavorites($productId);

        // Получаем счётчик, если нужно
        $this->arResult['FAVORITES_COUNT'] = 0;
        if ($this->arParams['SHOW_COUNTER']) {
            $this->arResult['FAVORITES_COUNT'] = $this->favoritesService->getProductFavoritesCount($productId);
        }

        // Данные для JavaScript
        $this->arResult['JS_PARAMS'] = [
            'productId' => $productId,
            'isInFavorites' => $this->arResult['IS_IN_FAVORITES'],
            'showCounter' => $this->arParams['SHOW_COUNTER'],
            'buttonSize' => $this->arParams['BUTTON_SIZE'],
            'sessid' => bitrix_sessid(),
        ];

        // CSS классы
        $this->arResult['CSS_CLASSES'] = $this->buildCssClasses();
    }

    /**
     * Формирует CSS классы для кнопки
     */
    private function buildCssClasses(): string
    {
        $classes = ['vendor-favorites-btn'];
        $classes[] = 'vendor-favorites-btn--' . $this->arParams['BUTTON_SIZE'];

        if ($this->arResult['IS_IN_FAVORITES']) {
            $classes[] = 'vendor-favorites-btn--active';
        }

        return implode(' ', $classes);
    }
}





