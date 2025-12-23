<?php

declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Web\Cookie;

/**
 * Сервис для работы с cookie
 *
 * Обеспечивает хранение избранного для гостей.
 * Использует JSON-кодирование для хранения списка ID.
 *
 * @package Vendor\Favorites\Service
 */
final class CookieService
{
    private const COOKIE_NAME = 'VENDOR_FAVORITES';
    private const MODULE_ID = 'vendor.favorites';
    private const DEFAULT_COOKIE_LIFETIME_DAYS = 30;

    /**
     * Получает список ID избранных товаров из cookie
     *
     * @return int[]
     */
    public function getFavorites(): array
    {
        $request = Context::getCurrent()->getRequest();
        
        // Пробуем получить cookie (Bitrix автоматически добавляет префикс)
        $cookieValue = $request->getCookie(self::COOKIE_NAME);
        
        // Если не нашли, пробуем через $_COOKIE напрямую с разными вариантами имени
        if (empty($cookieValue)) {
            $cookiePrefix = Option::get('main', 'cookie_name', 'BITRIX_SM');
            $fullName = $cookiePrefix . '_' . self::COOKIE_NAME;
            
            $cookieValue = $_COOKIE[$fullName] ?? $_COOKIE[self::COOKIE_NAME] ?? null;
        }

        if (empty($cookieValue)) {
            return [];
        }

        // Декодируем base64 если закодировано
        $decoded = base64_decode($cookieValue, true);
        if ($decoded !== false) {
            $cookieValue = $decoded;
        }

        $data = json_decode($cookieValue, true);

        if (!is_array($data)) {
            return [];
        }

        return array_map('intval', array_filter($data, 'is_numeric'));
    }

    /**
     * Сохраняет список ID избранных товаров в cookie
     *
     * @param int[] $productIds
     */
    public function saveFavorites(array $productIds): void
    {
        $productIds = array_unique(array_map('intval', array_filter($productIds)));
        $cookieValue = base64_encode(json_encode(array_values($productIds)));

        $lifetime = $this->getCookieLifetime();
        $expires = time() + ($lifetime * 86400); // дни в секунды

        $cookie = new Cookie(self::COOKIE_NAME, $cookieValue, $expires);
        $cookie->setPath('/');
        $cookie->setHttpOnly(false); // Разрешаем доступ из JS для отладки
        $cookie->setSecure(false);

        Context::getCurrent()->getResponse()->addCookie($cookie);
        
        // Также устанавливаем напрямую для немедленного доступа
        $cookiePrefix = Option::get('main', 'cookie_name', 'BITRIX_SM');
        $fullName = $cookiePrefix . '_' . self::COOKIE_NAME;
        $_COOKIE[$fullName] = $cookieValue;
    }

    /**
     * Добавляет товар в избранное (cookie)
     */
    public function addProduct(int $productId): bool
    {
        $favorites = $this->getFavorites();

        if (in_array($productId, $favorites, true)) {
            return false; // уже есть
        }

        $favorites[] = $productId;
        $this->saveFavorites($favorites);

        return true;
    }

    /**
     * Удаляет товар из избранного (cookie)
     */
    public function removeProduct(int $productId): bool
    {
        $favorites = $this->getFavorites();

        $key = array_search($productId, $favorites, true);
        if ($key === false) {
            return false; // не было в избранном
        }

        unset($favorites[$key]);
        $this->saveFavorites($favorites);

        return true;
    }

    /**
     * Проверяет, находится ли товар в избранном (cookie)
     */
    public function isInFavorites(int $productId): bool
    {
        return in_array($productId, $this->getFavorites(), true);
    }

    /**
     * Очищает cookie с избранным
     */
    public function clear(): void
    {
        $cookie = new Cookie(self::COOKIE_NAME, '', time() - 3600);
        $cookie->setPath('/');

        Context::getCurrent()->getResponse()->addCookie($cookie);
        
        // Также очищаем из $_COOKIE
        $cookiePrefix = Option::get('main', 'cookie_name', 'BITRIX_SM');
        $fullName = $cookiePrefix . '_' . self::COOKIE_NAME;
        unset($_COOKIE[$fullName], $_COOKIE[self::COOKIE_NAME]);
    }

    /**
     * Получает время жизни cookie в днях из настроек модуля
     */
    private function getCookieLifetime(): int
    {
        return (int)Option::get(
            self::MODULE_ID,
            'cookie_lifetime',
            (string)self::DEFAULT_COOKIE_LIFETIME_DAYS
        );
    }
}
