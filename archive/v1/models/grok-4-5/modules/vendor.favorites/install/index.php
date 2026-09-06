<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

/**
 * Installer for vendor.favorites module.
 */
final class vendor_favorites extends CModule
{
	public $MODULE_ID = 'vendor.favorites';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	public $PARTNER_NAME;
	public $PARTNER_URI;
	public $MODULE_GROUP_RIGHTS = 'N';

	public function __construct()
	{
		$arModuleVersion = [];
		include __DIR__ . '/version.php';

		$this->MODULE_VERSION = (string)($arModuleVersion['VERSION'] ?? '1.0.0');
		$this->MODULE_VERSION_DATE = (string)($arModuleVersion['VERSION_DATE'] ?? '');
		$this->MODULE_NAME = (string)Loc::getMessage('VENDOR_FAVORITES_MODULE_NAME');
		$this->MODULE_DESCRIPTION = (string)Loc::getMessage('VENDOR_FAVORITES_MODULE_DESCRIPTION');
		$this->PARTNER_NAME = (string)Loc::getMessage('VENDOR_FAVORITES_PARTNER_NAME');
		$this->PARTNER_URI = (string)Loc::getMessage('VENDOR_FAVORITES_PARTNER_URI');
	}

	public function DoInstall(): void
	{
		global $USER, $APPLICATION;

		if (!$USER->IsAdmin())
		{
			$APPLICATION->ThrowException((string)Loc::getMessage('VENDOR_FAVORITES_ACCESS_DENIED'));

			return;
		}

		ModuleManager::registerModule($this->MODULE_ID);
		Loader::includeModule($this->MODULE_ID);

		$this->InstallDB();
		$this->InstallEvents();
		$this->InstallFiles();
	}

	public function DoUninstall(): void
	{
		global $USER, $APPLICATION;

		if (!$USER->IsAdmin())
		{
			$APPLICATION->ThrowException((string)Loc::getMessage('VENDOR_FAVORITES_ACCESS_DENIED'));

			return;
		}

		$request = Application::getInstance()->getContext()->getRequest();
		$step = (int)$request->get('step');

		if ($step < 2)
		{
			$APPLICATION->IncludeAdminFile(
				(string)Loc::getMessage('VENDOR_FAVORITES_UNINSTALL_TITLE'),
				__DIR__ . '/unstep1.php'
			);

			return;
		}

		$saveData = $request->get('savedata') === 'Y';

		$this->UnInstallFiles();
		$this->UnInstallEvents();

		if (!$saveData)
		{
			Loader::includeModule($this->MODULE_ID);
			$this->UnInstallDB();
		}

		ModuleManager::unRegisterModule($this->MODULE_ID);
	}

	public function InstallDB(): bool
	{
		Loader::includeModule('iblock');

		$tableClass = \Vendor\Favorites\Model\FavoritesTable::class;
		$connection = Application::getConnection();
		$tableName = $tableClass::getTableName();

		if (!$connection->isTableExists($tableName))
		{
			$tableClass::getEntity()->createDbTable();
		}

		$uniqueColumns = ['USER_ID', 'PRODUCT_ID'];
		if (!$connection->isIndexExists($tableName, $uniqueColumns))
		{
			$connection->createIndex(
				$tableName,
				'ux_vendor_favorites_user_product',
				$uniqueColumns,
				null,
				\Bitrix\Main\DB\Connection::INDEX_UNIQUE
			);
		}

		return true;
	}

	public function UnInstallDB(): bool
	{
		$connection = Application::getConnection();
		$tableName = \Vendor\Favorites\Model\FavoritesTable::getTableName();

		if ($connection->isTableExists($tableName))
		{
			$connection->dropTable($tableName);
		}

		\Bitrix\Main\Config\Option::delete($this->MODULE_ID);

		return true;
	}

	public function InstallEvents(): void
	{
		$eventManager = EventManager::getInstance();
		$handler = \Vendor\Favorites\EventHandler::class;

		$eventManager->registerEventHandler(
			'main',
			'OnAfterUserAuthorize',
			$this->MODULE_ID,
			$handler,
			'onAfterUserAuthorize'
		);

		$eventManager->registerEventHandler(
			'iblock',
			'OnAfterIBlockElementDelete',
			$this->MODULE_ID,
			$handler,
			'onAfterIBlockElementDelete'
		);

		$eventManager->registerEventHandler(
			'iblock',
			'OnAfterIBlockElementUpdate',
			$this->MODULE_ID,
			$handler,
			'onAfterIBlockElementUpdate'
		);
	}

	public function UnInstallEvents(): void
	{
		$eventManager = EventManager::getInstance();
		$handler = \Vendor\Favorites\EventHandler::class;

		$eventManager->unRegisterEventHandler(
			'main',
			'OnAfterUserAuthorize',
			$this->MODULE_ID,
			$handler,
			'onAfterUserAuthorize'
		);

		$eventManager->unRegisterEventHandler(
			'main',
			'OnBeforeUserLogout',
			$this->MODULE_ID,
			$handler,
			'onBeforeUserLogout'
		);

		$eventManager->unRegisterEventHandler(
			'iblock',
			'OnAfterIBlockElementDelete',
			$this->MODULE_ID,
			$handler,
			'onAfterIBlockElementDelete'
		);

		$eventManager->unRegisterEventHandler(
			'iblock',
			'OnAfterIBlockElementUpdate',
			$this->MODULE_ID,
			$handler,
			'onAfterIBlockElementUpdate'
		);
	}

	public function InstallFiles(): bool
	{
		CopyDirFiles(
			__DIR__ . '/components',
			$_SERVER['DOCUMENT_ROOT'] . '/local/components',
			true,
			true
		);

		return true;
	}

	public function UnInstallFiles(): bool
	{
		DeleteDirFilesEx('/local/components/vendor/favorites.button');

		return true;
	}
}
