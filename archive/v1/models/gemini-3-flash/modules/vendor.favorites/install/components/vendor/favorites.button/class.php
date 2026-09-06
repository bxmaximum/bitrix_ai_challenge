<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Vendor\Favorites\Service\FavoritesService;
use Bitrix\Main\Application;

class FavoritesButtonComponent extends CBitrixComponent
{
    private FavoritesService $service;

    public function onPrepareComponentParams($arParams): array
    {
        $arParams['PRODUCT_ID'] = (int)$arParams['PRODUCT_ID'];
        $arParams['SHOW_COUNTER'] = ($arParams['SHOW_COUNTER'] === 'Y');
        $arParams['BUTTON_SIZE'] = in_array($arParams['BUTTON_SIZE'], ['small', 'medium', 'large']) 
            ? $arParams['BUTTON_SIZE'] 
            : 'medium';

        return $arParams;
    }

    public function executeComponent(): void
    {
        try {
            if (!$this->checkModules()) {
                return;
            }

            $this->service = new FavoritesService();
            
            if ($this->startCache()) {
                $this->arResult['IS_FAVORITE'] = in_array(
                    $this->arParams['PRODUCT_ID'], 
                    $this->service->getFavorites(), 
                    true
                );

                if (defined('BX_COMP_MANAGED_CACHE')) {
                    $taggedCache = Application::getInstance()->getTaggedCache();
                    $taggedCache->startTagCache($this->getCachePath());
                    $taggedCache->registerTag('vendor_favorites_user_' . \Bitrix\Main\Engine\CurrentUser::get()->getId());
                    $taggedCache->registerTag('vendor_favorites_product_' . $this->arParams['PRODUCT_ID']);
                    $taggedCache->registerTag('vendor_favorites_global');
                    $taggedCache->endTagCache();
                }

                $this->includeComponentTemplate();
            }
        } catch (\Exception $e) {
            ShowError($e->getMessage());
        }
    }

    private function checkModules(): bool
    {
        if (!Loader::includeModule('vendor.favorites')) {
            ShowError('Module vendor.favorites is not installed');
            return false;
        }
        return true;
    }

    private function startCache(): bool
    {
        $cacheId = serialize([
            $this->arParams['PRODUCT_ID'],
            \Bitrix\Main\Engine\CurrentUser::get()->getId(),
            // cookie guest state is not cached globally, but we use user id in cache id
        ]);

        return !($this->arParams['CACHE_TYPE'] === 'N' || $this->startResultCache(false, $cacheId));
    }
}

