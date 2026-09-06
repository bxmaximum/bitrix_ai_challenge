<?php

declare(strict_types=1);

namespace Vendor\Favorites\Controller;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Vendor\Favorites\Service\FavoritesService;

Loc::loadMessages(__FILE__);

/**
 * AJAX / REST controller for favorites operations.
 *
 * Actions:
 * - favorites.add
 * - favorites.remove
 * - favorites.list
 * - favorites.getProducts
 * - favorites.status (UI helper)
 */
final class Favorites extends Controller
{
	private readonly FavoritesService $favoritesService;

	public function __construct(?Request $request = null)
	{
		parent::__construct($request);

		/** @var FavoritesService $service */
		$service = ServiceLocator::getInstance()->get(FavoritesService::class);
		$this->favoritesService = $service;
	}

	public function configureActions(): array
	{
		$csrfAndClose = [
			new ActionFilter\Csrf(),
			new ActionFilter\CloseSession(),
		];

		return [
			'add' => [
				'prefilters' => array_merge(
					[
						new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
					],
					$csrfAndClose
				),
			],
			'remove' => [
				'prefilters' => array_merge(
					[
						new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
					],
					$csrfAndClose
				),
			],
			'list' => [
				'prefilters' => array_merge(
					[
						new ActionFilter\HttpMethod([
							ActionFilter\HttpMethod::METHOD_GET,
							ActionFilter\HttpMethod::METHOD_POST,
						]),
					],
					$csrfAndClose
				),
			],
			'getProducts' => [
				'prefilters' => array_merge(
					[
						new ActionFilter\HttpMethod([
							ActionFilter\HttpMethod::METHOD_GET,
							ActionFilter\HttpMethod::METHOD_POST,
						]),
					],
					$csrfAndClose
				),
			],
			'status' => [
				'prefilters' => array_merge(
					[
						new ActionFilter\HttpMethod([
							ActionFilter\HttpMethod::METHOD_GET,
							ActionFilter\HttpMethod::METHOD_POST,
						]),
					],
					$csrfAndClose
				),
			],
		];
	}

	/**
	 * POST favorites.add
	 *
	 * @return array<string, mixed>|null
	 */
	public function addAction(int $productId): ?array
	{
		if ($productId <= 0)
		{
			$this->addError(new Error(
				(string)Loc::getMessage('VENDOR_FAVORITES_CTRL_INVALID_PRODUCT'),
				'INVALID_PRODUCT_ID'
			));

			return null;
		}

		$result = $this->favoritesService->add($productId);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getData();
	}

	/**
	 * POST favorites.remove
	 *
	 * @return array<string, mixed>|null
	 */
	public function removeAction(int $productId): ?array
	{
		if ($productId <= 0)
		{
			$this->addError(new Error(
				(string)Loc::getMessage('VENDOR_FAVORITES_CTRL_INVALID_PRODUCT'),
				'INVALID_PRODUCT_ID'
			));

			return null;
		}

		$result = $this->favoritesService->remove($productId);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getData();
	}

	/**
	 * GET favorites.list
	 *
	 * @return array{items: list<int>, count: int}
	 */
	public function listAction(): array
	{
		$enabledResult = $this->favoritesService->ensureEnabled();
		if (!$enabledResult->isSuccess())
		{
			$this->addErrors($enabledResult->getErrors());

			return ['items' => [], 'count' => 0];
		}

		$items = $this->favoritesService->getProductIds();

		return [
			'items' => $items,
			'count' => count($items),
		];
	}

	/**
	 * GET favorites.getProducts
	 *
	 * @return array{items: list<array<string, mixed>>, count: int}
	 */
	public function getProductsAction(): array
	{
		$enabledResult = $this->favoritesService->ensureEnabled();
		if (!$enabledResult->isSuccess())
		{
			$this->addErrors($enabledResult->getErrors());

			return ['items' => [], 'count' => 0];
		}

		$items = $this->favoritesService->getProducts();

		return [
			'items' => $items,
			'count' => count($items),
		];
	}

	/**
	 * Helper for component hydration.
	 *
	 * @return array{inFavorites: bool, count: int, productId: int}|null
	 */
	public function statusAction(int $productId): ?array
	{
		if ($productId <= 0)
		{
			$this->addError(new Error(
				(string)Loc::getMessage('VENDOR_FAVORITES_CTRL_INVALID_PRODUCT'),
				'INVALID_PRODUCT_ID'
			));

			return null;
		}

		$enabledResult = $this->favoritesService->ensureEnabled();
		if (!$enabledResult->isSuccess())
		{
			$this->addErrors($enabledResult->getErrors());

			return null;
		}

		return [
			'productId' => $productId,
			'inFavorites' => $this->favoritesService->isInFavorites($productId),
			'count' => $this->favoritesService->getFavoritesCount(),
		];
	}
}
