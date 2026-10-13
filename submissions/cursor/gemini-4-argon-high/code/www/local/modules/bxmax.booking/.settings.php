<?php declare(strict_types=1);

return [
    'controllers' => [
        'value' => [
            'defaultNamespace' => '\\Bxmax\\Booking\\Controller',
            'namespaces' => [
                '\\Bxmax\\Booking\\Controller' => 'api',
            ],
            'restIntegration' => [
                'enabled' => true,
            ],
        ],
        'readonly' => true,
    ],
    'services' => [
        'value' => [],
        'readonly' => true,
    ],
];
