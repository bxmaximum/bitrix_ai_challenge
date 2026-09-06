<?php

return [
    'controllers' => [
        'value' => [
            'defaultNamespace' => '\\Bxmax\\Booking\\Infrastructure\\Controller',
            'namespaces' => [
                '\\Bxmax\\Booking\\Infrastructure\\Controller\\Api' => 'api',
            ],
        ],
        'readonly' => true,
    ],
    'services' => [
        'value' => [
            \Bxmax\Booking\Domain\Repository\SlotRepositoryInterface::class => [
                'className' => \Bxmax\Booking\Infrastructure\Repository\SlotRepository::class,
            ],
            \Bxmax\Booking\Domain\Repository\EntryRepositoryInterface::class => [
                'className' => \Bxmax\Booking\Infrastructure\Repository\EntryRepository::class,
            ],
            \Bxmax\Booking\Domain\Repository\CatalogRepositoryInterface::class => [
                'className' => \Bxmax\Booking\Infrastructure\Repository\IblockCatalogRepository::class,
            ],
        ],
        'readonly' => true,
    ],
];
