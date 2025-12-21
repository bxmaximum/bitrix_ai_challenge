<?php

declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\Context;
use Bitrix\Main\Web\CryptoCookie;
use Bitrix\Main\Config\Option;

class CookieService
{
    private const COOKIE_NAME = 'V_FAVORITES';

    /**
     * @return int[]
     */
    public function getFavoriteIds(): array
    {
        $request = Context::getCurrent()->getRequest();
        $cookieValue = $request->getCookie(self::COOKIE_NAME);

        if (!$cookieValue) {
            return [];
        }

        $ids = json_decode($cookieValue, true);

        return is_array($ids) ? array_map('intval', $ids) : [];
    }

    /**
     * @param int[] $ids
     */
    public function setFavoriteIds(array $ids): void
    {
        $ids = array_unique(array_map('intval', $ids));
        $value = json_encode(array_values($ids));

        $cookieLifeTime = (int)Option::get('vendor.favorites', 'cookie_lifetime', 2592000); // 30 days default

        $cookie = new CryptoCookie(
            self::COOKIE_NAME,
            $value,
            time() + $cookieLifeTime
        );
        $cookie->setHttpOnly(true);
        $cookie->setPath('/');

        $response = Context::getCurrent()->getResponse();
        $response->addCookie($cookie);
    }

    public function clear(): void
    {
        $cookie = new CryptoCookie(self::COOKIE_NAME, '', time() - 3600);
        $cookie->setPath('/');
        Context::getCurrent()->getResponse()->addCookie($cookie);
    }
}

