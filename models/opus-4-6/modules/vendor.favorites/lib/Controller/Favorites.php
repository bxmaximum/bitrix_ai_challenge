<?php
declare(strict_types=1);

namespace Vendor\Favorites\Controller;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Vendor\Favorites\Service\FavoritesService;

final class Favorites extends Controller
{
	protected function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\CloseSession(),
		];
	}

	public function configureActions(): array
	{
		return [
			'add' => [
				'prefilters' => [
					new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
					new ActionFilter\Csrf(),
				],
			],
			'remove' => [
				'prefilters' => [
					new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
					new ActionFilter\Csrf(),
				],
			],
			'list' => [
				'prefilters' => [
					new ActionFilter\HttpMethod([
						ActionFilter\HttpMethod::METHOD_GET,
						ActionFilter\HttpMethod::METHOD_POST,
					]),
				],
			],
			'getProducts' => [
				'prefilters' => [
					new ActionFilter\HttpMethod([
						ActionFilter\HttpMethod::METHOD_GET,
						ActionFilter\HttpMethod::METHOD_POST,
					]),
				],
			],
		];
	}

	public function addAction(int $productId): ?array
	{
		try
		{
			$service = new FavoritesService();
			$service->add($productId, $this->getUserIdOrNull());

			return [
				'success' => true,
				'ids' => $service->listIds($this->getUserIdOrNull()),
			];
		}
		catch (\Throwable $e)
		{
			$this->addError(new Error($e->getMessage(), 'FAVORITES_ADD_ERROR'));
			return null;
		}
	}

	public function removeAction(int $productId): ?array
	{
		try
		{
			$service = new FavoritesService();
			$service->remove($productId, $this->getUserIdOrNull());

			return [
				'success' => true,
				'ids' => $service->listIds($this->getUserIdOrNull()),
			];
		}
		catch (\Throwable $e)
		{
			$this->addError(new Error($e->getMessage(), 'FAVORITES_REMOVE_ERROR'));
			return null;
		}
	}

	public function listAction(): ?array
	{
		try
		{
			$service = new FavoritesService();
			$ids = $service->listIds($this->getUserIdOrNull());

			return [
				'ids' => $ids,
				'total' => count($ids),
			];
		}
		catch (\Throwable $e)
		{
			$this->addError(new Error($e->getMessage(), 'FAVORITES_LIST_ERROR'));
			return null;
		}
	}

	public function getProductsAction(): ?array
	{
		try
		{
			$service = new FavoritesService();
			$items = $service->getProducts($this->getUserIdOrNull());

			return [
				'items' => $items,
				'total' => count($items),
			];
		}
		catch (\Throwable $e)
		{
			$this->addError(new Error($e->getMessage(), 'FAVORITES_GET_PRODUCTS_ERROR'));
			return null;
		}
	}

	private function getUserIdOrNull(): ?int
	{
		$userId = (int) CurrentUser::get()->getId();
		return $userId > 0 ? $userId : null;
	}
}


