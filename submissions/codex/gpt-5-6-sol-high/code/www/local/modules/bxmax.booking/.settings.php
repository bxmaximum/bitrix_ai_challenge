<?php

use Bxmax\Booking\Application\Service\BookingService;
use Bxmax\Booking\Application\Service\ScheduleService;
use Bxmax\Booking\Application\Service\SlotService;
use Bxmax\Booking\Infrastructure\Repository\BookingRepository;
use Bxmax\Booking\Infrastructure\Repository\ContentRepository;
use Bxmax\Booking\Infrastructure\Repository\SlotRepository;
use Bitrix\Main\DI\ServiceLocator;

return [
    'controllers' => [
        'value' => [
            'defaultNamespace' => '\\Bxmax\\Booking\\Infrastructure\\Controller',
        ],
        'readonly' => true,
    ],
    'services' => [
        'value' => [
            SlotRepository::class => ['className' => SlotRepository::class],
            BookingRepository::class => ['className' => BookingRepository::class],
            ContentRepository::class => ['className' => ContentRepository::class],
            SlotService::class => [
                'className' => SlotService::class,
                'constructorParams' => static fn (): array => [
                    ServiceLocator::getInstance()->get(SlotRepository::class),
                    ServiceLocator::getInstance()->get(ContentRepository::class),
                ],
            ],
            BookingService::class => [
                'className' => BookingService::class,
                'constructorParams' => static fn (): array => [
                    ServiceLocator::getInstance()->get(SlotRepository::class),
                    ServiceLocator::getInstance()->get(BookingRepository::class),
                    ServiceLocator::getInstance()->get(ContentRepository::class),
                ],
            ],
            ScheduleService::class => [
                'className' => ScheduleService::class,
                'constructorParams' => static fn (): array => [
                    ServiceLocator::getInstance()->get(SlotRepository::class),
                    ServiceLocator::getInstance()->get(ContentRepository::class),
                ],
            ],
        ],
        'readonly' => true,
    ],
];
