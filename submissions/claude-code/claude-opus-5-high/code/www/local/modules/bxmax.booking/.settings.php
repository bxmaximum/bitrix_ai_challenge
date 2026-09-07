<?php

use Bitrix\Main\DI\ServiceLocator;
use Bxmax\Booking\Console;
use Bxmax\Booking\Repository;
use Bxmax\Booking\Service;

$resolve = static fn(string ...$classes): callable => static function () use ($classes): array {
	$locator = ServiceLocator::getInstance();

	return array_map(static fn(string $class): object => $locator->get($class), $classes);
};

return [
	'controllers' => [
		'value' => [
			'defaultNamespace' => '\\Bxmax\\Booking\\Controller',
			'namespaces' => [
				'\\Bxmax\\Booking\\Controller' => 'api',
			],
			'restIntegration' => [
				'enabled' => false,
			],
		],
		'readonly' => true,
	],
	'services' => [
		'value' => [
			Repository\SlotRepository::class => [
				'className' => Repository\SlotRepository::class,
			],
			Repository\EntryRepository::class => [
				'className' => Repository\EntryRepository::class,
			],
			Repository\MasterRepository::class => [
				'className' => Repository\MasterRepository::class,
			],
			Repository\ServiceRepository::class => [
				'className' => Repository\ServiceRepository::class,
			],
			Service\NotificationService::class => [
				'className' => Service\NotificationService::class,
			],
			Service\ScheduleService::class => [
				'className' => Service\ScheduleService::class,
				'constructorParams' => $resolve(
					Repository\SlotRepository::class,
					Repository\MasterRepository::class
				),
			],
			Service\ScheduleGenerator::class => [
				'className' => Service\ScheduleGenerator::class,
				'constructorParams' => $resolve(
					Repository\SlotRepository::class,
					Repository\MasterRepository::class
				),
			],
			Service\BookingService::class => [
				'className' => Service\BookingService::class,
				'constructorParams' => $resolve(
					Repository\SlotRepository::class,
					Repository\EntryRepository::class,
					Repository\ServiceRepository::class,
					Repository\MasterRepository::class,
					Service\NotificationService::class
				),
			],
			Service\AdminService::class => [
				'className' => Service\AdminService::class,
				'constructorParams' => $resolve(
					Repository\SlotRepository::class,
					Repository\EntryRepository::class,
					Repository\MasterRepository::class,
					Repository\ServiceRepository::class
				),
			],
		],
		'readonly' => true,
	],
	'console' => [
		'value' => [
			'commands' => [
				Console\GenerateScheduleCommand::class,
			],
		],
		'readonly' => true,
	],
];
