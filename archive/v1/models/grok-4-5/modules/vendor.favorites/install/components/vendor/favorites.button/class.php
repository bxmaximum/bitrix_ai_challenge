<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Vendor\Favorites\Service\FavoritesService;

Loc::loadMessages(__FILE__);

/**
 * Public favorites toggle button for a catalog product page.
 */
final class VendorFavoritesButtonComponent extends CBitrixComponent
{
	private const ALLOWED_SIZES = ['small', 'medium', 'large'];

	public function onPrepareComponentParams($arParams): array
	{
		$arParams['PRODUCT_ID'] = max(0, (int)($arParams['PRODUCT_ID'] ?? 0));
		$arParams['SHOW_COUNTER'] = (($arParams['SHOW_COUNTER'] ?? 'N') === 'Y') ? 'Y' : 'N';

		$size = strtolower((string)($arParams['BUTTON_SIZE'] ?? 'medium'));
		$arParams['BUTTON_SIZE'] = in_array($size, self::ALLOWED_SIZES, true) ? $size : 'medium';

		return $arParams;
	}

	public function executeComponent(): void
	{
		if ($this->arParams['PRODUCT_ID'] <= 0)
		{
			ShowError((string)Loc::getMessage('VENDOR_FAVORITES_BUTTON_NO_PRODUCT'));

			return;
		}

		if (!Loader::includeModule('vendor.favorites'))
		{
			ShowError((string)Loc::getMessage('VENDOR_FAVORITES_BUTTON_MODULE_ERROR'));

			return;
		}

		$locator = ServiceLocator::getInstance();
		if (!$locator->has(FavoritesService::class))
		{
			ShowError((string)Loc::getMessage('VENDOR_FAVORITES_BUTTON_MODULE_ERROR'));

			return;
		}

		/** @var FavoritesService $service */
		$service = $locator->get(FavoritesService::class);

		$enabledResult = $service->ensureEnabled();
		if (!$enabledResult->isSuccess())
		{
			return;
		}

		$productId = $this->arParams['PRODUCT_ID'];
		$inFavorites = $service->isInFavorites($productId);
		$count = $this->arParams['SHOW_COUNTER'] === 'Y'
			? $service->getFavoritesCount()
			: null;

		$this->arResult = [
			'PRODUCT_ID' => $productId,
			'IN_FAVORITES' => $inFavorites,
			'SHOW_COUNTER' => $this->arParams['SHOW_COUNTER'] === 'Y',
			'COUNT' => $count,
			'BUTTON_SIZE' => $this->arParams['BUTTON_SIZE'],
			'BUTTON_ID' => 'vendor-favorites-btn-' . $productId . '-' . $this->randString(4),
		];

		$this->includeComponentTemplate();
	}
}
