<?php

declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\Context;
use Bitrix\Main\Web\CryptoCookie;
use Bitrix\Main\Web\Json;
use Vendor\Favorites\Config\ModuleConfig;

/**
 * Хранение списка ID товаров гостя в зашифрованных cookie.
 */
final class CookieService
{
    private const COOKIE_NAME = 'VENDOR_FAVORITES_IDS';

    /**
     * @return list<int>
     */
    public function getProductIds(): array
    {
        $request = Context::getCurrent()->getRequest();
        $raw = $request->getCookie(self::COOKIE_NAME);
        if ($raw === null || $raw === '') {
            return [];
        }

        try {
            $decoded = Json::decode($raw);
        } catch (\Throwable) {
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        $ids = [];
        foreach ($decoded as $v) {
            $id = (int) $v;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * @param list<int> $productIds
     */
    public function setProductIds(array $productIds): void
    {
        $clean = [];
        foreach ($productIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $clean[$id] = $id;
            }
        }
        $list = array_values($clean);
        if ($list === []) {
            $this->clear();

            return;
        }

        $expires = time() + ModuleConfig::getGuestCookieTtl();
        $value = Json::encode($list);

        $cookie = new CryptoCookie(self::COOKIE_NAME, $value, $expires);
        Context::getCurrent()->getResponse()->addCookie($cookie);
    }

    public function clear(): void
    {
        $expires = time() - 3600;
        $cookie = new CryptoCookie(self::COOKIE_NAME, '', $expires);
        Context::getCurrent()->getResponse()->addCookie($cookie);
    }
}
