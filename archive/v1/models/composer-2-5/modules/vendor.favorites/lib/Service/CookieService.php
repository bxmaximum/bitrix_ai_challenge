<?php

declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\Context;
use Bitrix\Main\Web\CryptoCookie;
use Bitrix\Main\Web\Json;
use Vendor\Favorites\Config\ModuleOptions;

/**
 * Хранение избранного гостя в зашифрованных cookie.
 */
final class CookieService
{
    public const COOKIE_NAME = 'VENDOR_FAVORITES';

    public function __construct(
        private readonly ModuleOptions $options,
    ) {
    }

    /**
     * @return list<int>
     */
    public function getProductIds(): array
    {
        $raw = Context::getCurrent()->getRequest()->getCookie(self::COOKIE_NAME);

        if ($raw === null || $raw === '') {
            return [];
        }

        try {
            $decoded = Json::decode($raw);
        } catch (\Bitrix\Main\ArgumentException) {
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map('intval', $decoded),
            static fn (int $id): bool => $id > 0,
        )));
    }

    /**
     * @param list<int> $productIds
     */
    public function setProductIds(array $productIds): void
    {
        $productIds = array_values(array_unique(array_filter(
            array_map('intval', $productIds),
            static fn (int $id): bool => $id > 0,
        )));

        $cookie = new CryptoCookie(
            self::COOKIE_NAME,
            Json::encode($productIds),
            time() + $this->options->getCookieTtl(),
        );
        $cookie->setPath('/');
        $cookie->setHttpOnly(true);
        $cookie->setSpread(CryptoCookie::SPREAD_DOMAIN);

        Context::getCurrent()->getResponse()->addCookie($cookie);
    }

    public function clear(): void
    {
        $cookie = new CryptoCookie(self::COOKIE_NAME, '', time() - 3600);
        $cookie->setPath('/');
        $cookie->setHttpOnly(true);
        $cookie->setSpread(CryptoCookie::SPREAD_DOMAIN);

        Context::getCurrent()->getResponse()->addCookie($cookie);
    }

    public function add(int $productId): void
    {
        $ids = $this->getProductIds();

        if (!in_array($productId, $ids, true)) {
            $ids[] = $productId;
        }

        $this->setProductIds($ids);
    }

    public function remove(int $productId): void
    {
        $ids = array_values(array_filter(
            $this->getProductIds(),
            static fn (int $id): bool => $id !== $productId,
        ));

        $this->setProductIds($ids);
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->getProductIds(), true);
    }
}
