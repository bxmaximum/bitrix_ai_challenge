<?php

declare(strict_types=1);

use Bitrix\Main\DI\ServiceLocator;
use Vendor\Favorites\Config\ModuleOptions;
use Vendor\Favorites\Repository\FavoritesRepository;
use Vendor\Favorites\Service\CookieService;
use Vendor\Favorites\Service\FavoritesService;
use Vendor\Favorites\Service\ProductService;

return [
    'controllers' => [
        'value' => [
            'defaultNamespace' => '\\Vendor\\Favorites\\Controller',
            'restIntegration' => [
                'enabled' => true,
            ],
        ],
        'readonly' => true,
    ],
    'services' => [
        'value' => [
            ModuleOptions::class => [
                'className' => ModuleOptions::class,
            ],
            FavoritesRepository::class => [
                'className' => FavoritesRepository::class,
            ],
            CookieService::class => [
                'className' => CookieService::class,
                'constructorParams' => static function (): array {
                    return [
                        ServiceLocator::getInstance()->get(ModuleOptions::class),
                    ];
                },
            ],
            ProductService::class => [
                'className' => ProductService::class,
                'constructorParams' => static function (): array {
                    $locator = ServiceLocator::getInstance();

                    return [
                        $locator->get(ModuleOptions::class),
                        $locator->get(FavoritesRepository::class),
                    ];
                },
            ],
            FavoritesService::class => [
                'className' => FavoritesService::class,
                'constructorParams' => static function (): array {
                    $locator = ServiceLocator::getInstance();

                    return [
                        $locator->get(ModuleOptions::class),
                        $locator->get(FavoritesRepository::class),
                        $locator->get(CookieService::class),
                        $locator->get(ProductService::class),
                    ];
                },
            ],
        ],
        'readonly' => true,
    ],
];
