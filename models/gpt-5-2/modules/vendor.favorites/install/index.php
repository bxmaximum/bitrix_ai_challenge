<?php
declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

final class vendor_favorites extends CModule
{
	public const MODULE_ID = 'vendor.favorites';

	public $MODULE_ID = self::MODULE_ID;
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME = 'Избранное (Wishlist)';
	public $MODULE_DESCRIPTION = 'Функционал избранного для каталога товаров (cookie для гостей, БД для авторизованных).';
	public $PARTNER_NAME = 'Vendor';
	public $PARTNER_URI = '';

	public function __construct()
	{
		$arModuleVersion = [];
		include __DIR__ . '/version.php';

		$this->MODULE_VERSION = $arModuleVersion['VERSION'] ?? '0.1.0';
		$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'] ?? date('Y-m-d');
	}

	public function doInstall(): void
	{
		ModuleManager::registerModule($this->MODULE_ID);
		Loader::includeModule($this->MODULE_ID);

		$this->InstallDB();
		$this->InstallEvents();
		$this->InstallFiles();
	}

	public function doUninstall(): void
	{
		Loader::includeModule($this->MODULE_ID);

		$this->UnInstallFiles();
		$this->UnInstallEvents();
		$this->UnInstallDB();

		ModuleManager::unRegisterModule($this->MODULE_ID);
	}

	public function InstallDB(): void
	{
		$connection = Application::getConnection();
		$tableName = \Vendor\Favorites\Model\FavoritesTable::getTableName();

		if (!$connection->isTableExists($tableName))
		{
			\Vendor\Favorites\Model\FavoritesTable::getEntity()->createDbTable();

			// Best-effort indexes (including unique constraint to avoid duplicates).
			try
			{
				if ($connection instanceof \Bitrix\Main\DB\MysqlCommonConnection || $connection instanceof \Bitrix\Main\DB\PgsqlConnection)
				{
					$connection->createIndex($tableName, 'ux_vendor_favorites_user_product', ['USER_ID', 'PRODUCT_ID'], null, $connection::INDEX_UNIQUE);
				}
				else
				{
					$connection->createIndex($tableName, 'ix_vendor_favorites_user_product', ['USER_ID', 'PRODUCT_ID']);
				}

				$connection->createIndex($tableName, 'ix_vendor_favorites_product', ['PRODUCT_ID']);
			}
			catch (\Throwable)
			{
				// ignore
			}
		}
	}

	public function UnInstallDB(): void
	{
		$connection = Application::getConnection();
		$tableName = \Vendor\Favorites\Model\FavoritesTable::getTableName();

		if ($connection->isTableExists($tableName))
		{
			$connection->dropTable($tableName);
		}
	}

	public function InstallEvents(): void
	{
		$eventManager = EventManager::getInstance();

		$eventManager->registerEventHandler(
			'main',
			'OnAfterUserAuthorize',
			$this->MODULE_ID,
			\Vendor\Favorites\EventHandler::class,
			'onAfterUserAuthorize'
		);

		$eventManager->registerEventHandler(
			'iblock',
			'OnAfterIBlockElementDelete',
			$this->MODULE_ID,
			\Vendor\Favorites\EventHandler::class,
			'onAfterIBlockElementDelete'
		);

		$eventManager->registerEventHandler(
			'iblock',
			'OnAfterIBlockElementUpdate',
			$this->MODULE_ID,
			\Vendor\Favorites\EventHandler::class,
			'onAfterIBlockElementUpdate'
		);
	}

	public function UnInstallEvents(): void
	{
		$eventManager = EventManager::getInstance();

		$eventManager->unRegisterEventHandler(
			'main',
			'OnAfterUserAuthorize',
			$this->MODULE_ID,
			\Vendor\Favorites\EventHandler::class,
			'onAfterUserAuthorize'
		);

		$eventManager->unRegisterEventHandler(
			'iblock',
			'OnAfterIBlockElementDelete',
			$this->MODULE_ID,
			\Vendor\Favorites\EventHandler::class,
			'onAfterIBlockElementDelete'
		);

		$eventManager->unRegisterEventHandler(
			'iblock',
			'OnAfterIBlockElementUpdate',
			$this->MODULE_ID,
			\Vendor\Favorites\EventHandler::class,
			'onAfterIBlockElementUpdate'
		);
	}

	public function InstallFiles(): void
	{
		if (!isset($_SERVER['DOCUMENT_ROOT']) || $_SERVER['DOCUMENT_ROOT'] === '')
		{
			return;
		}

		$source = __DIR__ . '/components';
		$target = rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/') . '/local/components';

		if (is_dir($source))
		{
			CopyDirFiles($source, $target, true, true);
		}
	}

	public function UnInstallFiles(): void
	{
		// Intentionally do not delete copied component files on uninstall.
	}
}


