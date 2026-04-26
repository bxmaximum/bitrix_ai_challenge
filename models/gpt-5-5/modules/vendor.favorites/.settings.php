<?php

declare(strict_types=1);

return [
    'controllers' => [
        'value' => [
            'defaultNamespace' => '\\Vendor\\Favorites\\Controller',
            'restIntegration' => ['enabled' => true],
        ],
        'readonly' => true,
    ],
    'services' => [
        'value' => [
            \Vendor\Favorites\Repository\FavoritesRepository::class => [
                'className' => \Vendor\Favorites\Repository\FavoritesRepository::class,
            ],
            \Vendor\Favorites\Service\CookieService::class => [
                'className' => \Vendor\Favorites\Service\CookieService::class,
            ],
            \Vendor\Favorites\Service\FavoritesService::class => [
                'className' => \Vendor\Favorites\Service\FavoritesService::class,
            ],
        ],
        'readonly' => true,
    ],
];
