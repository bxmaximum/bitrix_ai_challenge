<?php
declare(strict_types=1);

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Vendor\Favorites\Service\FavoritesService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

final class VendorFavoritesButtonComponent extends CBitrixComponent
{
	public function onPrepareComponentParams($params): array
	{
		$params['PRODUCT_ID'] = (int) ($params['PRODUCT_ID'] ?? 0);
		$params['SHOW_COUNTER'] = (($params['SHOW_COUNTER'] ?? 'N') === 'Y') ? 'Y' : 'N';

		$size = (string) ($params['BUTTON_SIZE'] ?? 'medium');
		$params['BUTTON_SIZE'] = in_array($size, ['small', 'medium', 'large'], true) ? $size : 'medium';

		return $params;
	}

	public function executeComponent(): void
	{
		$this->arResult = [
			'ENABLED' => false,
			'PRODUCT_ID' => (int) $this->arParams['PRODUCT_ID'],
			'IS_FAVORITE' => false,
			'COUNTER' => 0,
			'SHOW_COUNTER' => $this->arParams['SHOW_COUNTER'] === 'Y',
			'BUTTON_SIZE' => (string) $this->arParams['BUTTON_SIZE'],
			'ACTION_ADD' => 'vendor:favorites.favorites.add',
			'ACTION_REMOVE' => 'vendor:favorites.favorites.remove',
		];

		if ($this->arResult['PRODUCT_ID'] <= 0)
		{
			return;
		}

		if (!Loader::includeModule('vendor.favorites'))
		{
			return;
		}

		$service = new FavoritesService();
		if (!$service->isEnabled())
		{
			return;
		}

		$userId = (int) CurrentUser::get()->getId();
		$userId = $userId > 0 ? $userId : null;

		$ids = $service->listIds($userId);
		$this->arResult['ENABLED'] = true;
		$this->arResult['IS_FAVORITE'] = in_array($this->arResult['PRODUCT_ID'], $ids, true);
		$this->arResult['COUNTER'] = count($ids);

		$this->includeComponentTemplate();
	}
}


