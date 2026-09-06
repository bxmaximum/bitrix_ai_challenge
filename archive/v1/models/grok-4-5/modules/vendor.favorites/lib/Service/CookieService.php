<?php

declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Web\Cookie;
use Bitrix\Main\Web\CryptoCookie;
use Bitrix\Main\Web\Http\Cookie as HttpCookie;
use Bitrix\Main\Web\Json;
use Vendor\Favorites\Config\ModuleOptions;

/**
 * Guest favorites storage via encrypted CryptoCookie.
 *
 * Keeps an in-request memory snapshot so reads after write return the new value
 * (browser Request cookies are not updated until the next HTTP request).
 */
final class CookieService
{
	public const COOKIE_NAME = 'VENDOR_FAVORITES';

	/** @var list<int>|null */
	private ?array $runtimeIds = null;

	private bool $runtimeLoaded = false;

	public function __construct(
		private readonly ModuleOptions $options,
	) {
	}

	/**
	 * @return list<int>
	 */
	public function getProductIds(): array
	{
		if ($this->runtimeLoaded)
		{
			return $this->runtimeIds ?? [];
		}

		$this->runtimeIds = $this->readFromRequest();
		$this->runtimeLoaded = true;

		return $this->runtimeIds;
	}

	/**
	 * @param list<int> $productIds
	 */
	public function setProductIds(array $productIds): void
	{
		$ids = $this->normalizeIds($productIds);
		$this->runtimeIds = $ids;
		$this->runtimeLoaded = true;

		$value = Json::encode(array_values($ids));
		$expires = time() + $this->options->getCookieTtl();

		$this->addCookie($value, $expires);
	}

	public function clear(): void
	{
		$this->runtimeIds = [];
		$this->runtimeLoaded = true;
		$this->addCookie('', time() - 3600);
	}

	/**
	 * @return list<int>
	 */
	private function readFromRequest(): array
	{
		$request = Context::getCurrent()?->getRequest();
		if ($request === null)
		{
			return [];
		}

		$raw = (string)$request->getCookie(self::COOKIE_NAME);
		if ($raw === '')
		{
			return [];
		}

		try
		{
			$decoded = Json::decode($raw);
		}
		catch (\Throwable)
		{
			return [];
		}

		if (!is_array($decoded))
		{
			return [];
		}

		return $this->normalizeIds($decoded);
	}

	/**
	 * @param list<mixed> $ids
	 * @return list<int>
	 */
	private function normalizeIds(array $ids): array
	{
		$result = [];
		foreach ($ids as $id)
		{
			$id = (int)$id;
			if ($id > 0)
			{
				$result[$id] = $id;
			}
		}

		return array_values($result);
	}

	private function addCookie(string $value, int $expires): void
	{
		$response = Context::getCurrent()?->getResponse();
		if ($response === null)
		{
			return;
		}

		$cookie = new CryptoCookie(self::COOKIE_NAME, $value, $expires);
		$cookie
			->setPath('/')
			->setHttpOnly(true)
			->setSecure($this->isHttps())
			->setSameSite(HttpCookie::SAME_SITE_LAX)
			->setSpread(Cookie::SPREAD_DOMAIN | Cookie::SPREAD_SITES);

		$response->addCookie($cookie);
	}

	private function isHttps(): bool
	{
		$request = Context::getCurrent()?->getRequest();
		if ($request !== null && method_exists($request, 'isHttps'))
		{
			return (bool)$request->isHttps();
		}

		return (string)(Application::getInstance()->getContext()->getServer()->get('HTTPS') ?? '') !== '';
	}
}
