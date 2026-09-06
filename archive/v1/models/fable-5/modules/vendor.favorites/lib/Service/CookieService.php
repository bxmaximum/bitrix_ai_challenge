<?php

declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\CryptoCookie;
use Bitrix\Main\Web\Json;

/**
 * Хранение избранного гостя в шифрованной cookie (CryptoCookie).
 *
 * Значение — JSON-массив ID товаров. Шифрование/дешифрование выполняет
 * ядро (CookiesCrypter) на основе crypto_key из .settings.php.
 */
final class CookieService
{
    private const COOKIE_NAME = 'VENDOR_FAVORITES';

    /** TTL cookie по умолчанию, дней */
    private const DEFAULT_TTL_DAYS = 30;

    /**
     * Значение, записанное в response в рамках текущего запроса.
     * Cookie из request на этот момент уже устарела, поэтому
     * при чтении это значение имеет приоритет.
     *
     * @var int[]|null
     */
    private ?array $pendingIds = null;

    /**
     * Возвращает список ID товаров из cookie гостя.
     *
     * @return int[]
     */
    public function getProductIds(): array
    {
        if ($this->pendingIds !== null) {
            return $this->pendingIds;
        }

        $raw = Application::getInstance()->getContext()->getRequest()
            ->getCookie(self::COOKIE_NAME);

        if (!is_string($raw) || $raw === '') {
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

        $ids = [];
        foreach ($decoded as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * Сохраняет список ID товаров в шифрованную cookie.
     *
     * @param int[] $productIds
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
            time() + $this->getTtlSeconds(),
        );
        $cookie->setHttpOnly(true);

        Application::getInstance()->getContext()->getResponse()->addCookie($cookie);

        $this->pendingIds = $productIds;
    }

    /**
     * Очищает cookie избранного (после миграции в БД).
     */
    public function clear(): void
    {
        $cookie = new CryptoCookie(self::COOKIE_NAME, '', time() - 3600);
        $cookie->setHttpOnly(true);

        Application::getInstance()->getContext()->getResponse()->addCookie($cookie);

        $this->pendingIds = [];
    }

    private function getTtlSeconds(): int
    {
        $days = (int)Option::get('vendor.favorites', 'cookie_ttl_days', (string)self::DEFAULT_TTL_DAYS);
        if ($days <= 0) {
            $days = self::DEFAULT_TTL_DAYS;
        }

        return $days * 86400;
    }
}
