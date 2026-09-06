<?php

declare(strict_types=1);

namespace Vendor\Favorites;

use Vendor\Favorites\Service\FavoritesService;
use Vendor\Favorites\Repository\FavoritesRepository;
use Bitrix\Main\Application;

class EventHandler
{
    public static function onAfterUserAuthorize(array $userFields): void
    {
        $userId = (int)$userFields['user_fields']['ID'];
        if ($userId > 0) {
            $service = new FavoritesService();
            $service->migrateFromCookie($userId);
        }
    }

    public static function onAfterIBlockElementDelete(array $fields): void
    {
        $productId = (int)$fields['ID'];
        if ($productId > 0) {
            $repository = new FavoritesRepository();
            $repository->removeByProductId($productId);
            
            // Invalidate all cache if needed, or we could just rely on tagged cache if we had a global tag
            // For now, simpler to clear all if a product is deleted
            if (defined('BX_COMP_MANAGED_CACHE')) {
                Application::getInstance()->getTaggedCache()->clearByTag('vendor_favorites_global');
            }
        }
    }

    public static function onAfterIBlockElementUpdate(array $fields): void
    {
        // Requirement: Invalidate cache when element is updated
        if (defined('BX_COMP_MANAGED_CACHE')) {
            Application::getInstance()->getTaggedCache()->clearByTag('vendor_favorites_product_' . $fields['ID']);
            Application::getInstance()->getTaggedCache()->clearByTag('vendor_favorites_global');
        }
    }
}

