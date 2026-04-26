<?php

declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\Context;
use Bitrix\Main\Web\CryptoCookie;
use Bitrix\Main\Web\Http\Cookie;
use Bitrix\Main\Web\Json;
use Vendor\Favorites\Config\ModuleOptions;

final class CookieService
{
    private const COOKIE_NAME = 'VENDOR_FAVORITES';

    /**
     * @return list<int>
     */
    public function getProductIds(): array
    {
        $value = (string)Context::getCurrent()->getRequest()->getCookie(self::COOKIE_NAME);
        if ($value === '') {
            return [];
        }

        try {
            $decoded = Json::decode($value);
        } catch (\Throwable) {
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        return $this->normalizeProductIds($decoded);
    }

    /**
     * @return list<int>
     */
    public function add(int $productId): array
    {
        $productIds = $this->getProductIds();
        $productIds[] = $productId;

        return $this->save($productIds);
    }

    /**
     * @return list<int>
     */
    public function remove(int $productId): array
    {
        $productIds = array_values(array_filter(
            $this->getProductIds(),
            static fn (int $id): bool => $id !== $productId,
        ));

        return $this->save($productIds);
    }

    public function clear(): void
    {
        $this->addCookie('', time() - 3600);
    }

    /**
     * @param list<int> $productIds
     */
    private function save(array $productIds): array
    {
        $productIds = $this->normalizeProductIds($productIds);
        $this->addCookie(
            Json::encode($productIds),
            time() + ModuleOptions::getCookieTtl(),
        );

        return $productIds;
    }

    private function addCookie(string $value, int $expires): void
    {
        $cookie = new CryptoCookie(self::COOKIE_NAME, $value, $expires);
        $cookie
            ->setHttpOnly(true)
            ->setSameSite(Cookie::SAME_SITE_LAX);

        Context::getCurrent()->getResponse()->addCookie($cookie);
    }

    /**
     * @param mixed[] $productIds
     * @return list<int>
     */
    private function normalizeProductIds(array $productIds): array
    {
        $normalized = [];
        foreach ($productIds as $productId) {
            $productId = (int)$productId;
            if ($productId > 0) {
                $normalized[$productId] = $productId;
            }
        }

        return array_values($normalized);
    }
}
