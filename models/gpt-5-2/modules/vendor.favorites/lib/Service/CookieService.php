<?php
declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\Context;
use Bitrix\Main\Web\CryptoCookie;
use Bitrix\Main\Web\Cookie;

final class CookieService
{
	private const COOKIE_NAME = 'VENDOR_FAVORITES';

	/**
	 * @return int[]
	 */
	public function getIds(): array
	{
		$request = Context::getCurrent()->getRequest();
		// HttpRequest::prepareCookie() strips the BITRIX_SM_ prefix, so we use the original name.
		$value = (string) $request->getCookie(self::COOKIE_NAME);
		if ($value === '')
		{
			return [];
		}

		try
		{
			$data = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
		}
		catch (\Throwable)
		{
			return [];
		}

		if (!is_array($data))
		{
			return [];
		}

		$ids = [];
		foreach ($data as $id)
		{
			$id = (int) $id;
			if ($id > 0)
			{
				$ids[] = $id;
			}
		}

		return array_values(array_unique($ids));
	}

	/**
	 * @param int[] $ids
	 */
	public function setIds(array $ids, int $ttlSeconds): void
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id) => $id > 0)));

		$value = json_encode($ids, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

		$expires = time() + max(60, $ttlSeconds);
		$cookie = new CryptoCookie(self::COOKIE_NAME, $value, $expires, true);
		$cookie->setPath('/');
		$cookie->setSpread(Cookie::SPREAD_DOMAIN | Cookie::SPREAD_SITES);

		Context::getCurrent()->getResponse()->addCookie($cookie);
	}

	public function clear(): void
	{
		$cookie = new CryptoCookie(self::COOKIE_NAME, '', time() - 3600, true);
		$cookie->setPath('/');
		$cookie->setSpread(Cookie::SPREAD_DOMAIN | Cookie::SPREAD_SITES);

		Context::getCurrent()->getResponse()->addCookie($cookie);
	}
}


