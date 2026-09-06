<?php

return [
    'controllers' => [
        'value' => [
            'defaultNamespace' => '\\Vendor\\Favorites\\Controller',
        ],
        'readonly' => true,
    ],
    'services' => [
        'value' => [
            'vendor.favorites.cookieService' => [
                'className' => \Vendor\Favorites\Service\CookieService::class,
            ],
            'vendor.favorites.favoritesRepository' => [
                'className' => \Vendor\Favorites\Repository\FavoritesRepository::class,
            ],
            'vendor.favorites.favoritesService' => [
                'constructor' => static function (): \Vendor\Favorites\Service\FavoritesService {
                    $locator = \Bitrix\Main\DI\ServiceLocator::getInstance();

                    return new \Vendor\Favorites\Service\FavoritesService(
                        $locator->get('vendor.favorites.favoritesRepository'),
                        $locator->get('vendor.favorites.cookieService'),
                    );
                },
            ],
        ],
        'readonly' => true,
    ],
];
