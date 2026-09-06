<?php
declare(strict_types=1);

use Vendor\Favorites\Repository\FavoritesRepository;
use Vendor\Favorites\Service\CookieService;
use Vendor\Favorites\Service\FavoritesService;
use Vendor\Favorites\Service\ProductService;

return [
    'controllers' => [
        'value' => [
            'defaultNamespace' => '\\Vendor\\Favorites\\Controller',
        ],
        'readonly' => true,
    ],
    'services' => [
        'value' => [
            FavoritesRepository::class => [
                'className' => FavoritesRepository::class,
            ],
            CookieService::class => [
                'className' => CookieService::class,
            ],
            ProductService::class => [
                'className' => ProductService::class,
            ],
            FavoritesService::class => [
                'className' => FavoritesService::class,
                'constructorParams' => static function (): array {
                    return [
                        new FavoritesRepository(),
                        new CookieService(),
                        new ProductService(),
                    ];
                },
            ],
        ],
        'readonly' => true,
    ],
];
