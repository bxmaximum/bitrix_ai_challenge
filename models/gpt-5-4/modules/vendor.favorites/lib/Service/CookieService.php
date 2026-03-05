<?php
declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\Web\CryptoCookie;
use Bitrix\Main\Web\Json;

/**
 * Manages encrypted guest favorites stored in cookies.
 */
final class CookieService
{
    private const COOKIE_NAME = 'vendor_favorites';
    private ?array $runtimeProductIds = null;

    /**
     * Returns favorite product ids from encrypted cookie.
     *
     * @return int[]
     */
    public function getProductIds(): array
    {
        if ($this->runtimeProductIds !== null) {
            return $this->runtimeProductIds;
        }

        try {
            $value = (string) Context::getCurrent()->getRequest()->getCookie(self::COOKIE_NAME);
            if ($value === '') {
                $this->runtimeProductIds = [];

                return $this->runtimeProductIds;
            }

            $decoded = Json::decode($value);
            if (!is_array($decoded)) {
                $this->runtimeProductIds = [];

                return $this->runtimeProductIds;
            }

            $normalized = array_map(
                static fn(mixed $id): int => (int) $id,
                $decoded
            );

            $normalized = array_values(array_unique(array_filter(
                $normalized,
                static fn(int $id): bool => $id > 0
            )));

            $this->runtimeProductIds = $normalized;

            return $this->runtimeProductIds;
        } catch (ArgumentException) {
            $this->runtimeProductIds = [];

            return $this->runtimeProductIds;
        }
    }

    /**
     * Checks whether product exists in guest favorites.
     */
    public function hasProduct(int $productId): bool
    {
        return in_array($productId, $this->getProductIds(), true);
    }

    /**
     * Adds product to encrypted guest cookie.
     */
    public function addProduct(int $productId): void
    {
        $productIds = $this->getProductIds();
        $productIds[] = $productId;

        $this->setProductIds($productIds);
    }

    /**
     * Removes product from encrypted guest cookie.
     */
    public function removeProduct(int $productId): void
    {
        $productIds = array_filter(
            $this->getProductIds(),
            static fn(int $id): bool => $id !== $productId
        );

        $this->setProductIds(array_values($productIds));
    }

    /**
     * Clears guest favorites cookie.
     */
    public function clear(): void
    {
        $this->runtimeProductIds = [];

        $cookie = $this->buildCookie('[]');
        $cookie->setExpires(time() - 3600);

        Context::getCurrent()->getResponse()->addCookie($cookie);
    }

    /**
     * Persists normalized product ids in encrypted cookie.
     *
     * @param int[] $productIds
     */
    private function setProductIds(array $productIds): void
    {
        $normalized = array_values(array_unique(array_filter(
            array_map(static fn(mixed $id): int => (int) $id, $productIds),
            static fn(int $id): bool => $id > 0
        )));

        $this->runtimeProductIds = $normalized;

        Context::getCurrent()->getResponse()->addCookie(
            $this->buildCookie(Json::encode($normalized))
        );
    }

    /**
     * Creates CryptoCookie with secure defaults.
     */
    private function buildCookie(string $value): CryptoCookie
    {
        $request = Context::getCurrent()->getRequest();
        $expires = time() + (86400 * ModuleSettings::getCookieLifetimeDays());

        return (new CryptoCookie(self::COOKIE_NAME, $value, $expires))
            ->setHttpOnly(true)
            ->setSecure($request->isHttps())
            ->setPath('/');
    }
}
