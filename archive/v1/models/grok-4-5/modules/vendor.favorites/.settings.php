<?php

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
			\Vendor\Favorites\Config\ModuleOptions::class => [
				'constructor' => static function (): \Vendor\Favorites\Config\ModuleOptions {
					return new \Vendor\Favorites\Config\ModuleOptions();
				},
			],
			\Vendor\Favorites\Repository\FavoritesRepository::class => [
				'constructor' => static function (): \Vendor\Favorites\Repository\FavoritesRepository {
					return new \Vendor\Favorites\Repository\FavoritesRepository();
				},
			],
			\Vendor\Favorites\Service\CookieService::class => [
				'constructor' => static function (): \Vendor\Favorites\Service\CookieService {
					$locator = \Bitrix\Main\DI\ServiceLocator::getInstance();

					return new \Vendor\Favorites\Service\CookieService(
						$locator->get(\Vendor\Favorites\Config\ModuleOptions::class)
					);
				},
			],
			\Vendor\Favorites\Service\ProductService::class => [
				'constructor' => static function (): \Vendor\Favorites\Service\ProductService {
					$locator = \Bitrix\Main\DI\ServiceLocator::getInstance();

					return new \Vendor\Favorites\Service\ProductService(
						$locator->get(\Vendor\Favorites\Config\ModuleOptions::class)
					);
				},
			],
			\Vendor\Favorites\Service\FavoritesService::class => [
				'constructor' => static function (): \Vendor\Favorites\Service\FavoritesService {
					$locator = \Bitrix\Main\DI\ServiceLocator::getInstance();

					return new \Vendor\Favorites\Service\FavoritesService(
						$locator->get(\Vendor\Favorites\Config\ModuleOptions::class),
						$locator->get(\Vendor\Favorites\Repository\FavoritesRepository::class),
						$locator->get(\Vendor\Favorites\Service\CookieService::class),
						$locator->get(\Vendor\Favorites\Service\ProductService::class),
					);
				},
			],
		],
		'readonly' => true,
	],
];
