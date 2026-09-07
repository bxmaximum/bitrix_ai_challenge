<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Mail\Internal\EventMessageSiteTable;
use Bitrix\Main\Mail\Internal\EventMessageTable;
use Bitrix\Main\Mail\Internal\EventTypeTable;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

if (class_exists('bxmax_booking'))
{
	return;
}

/**
 * Установка модуля онлайн-записи.
 *
 * Модуль регистрирует только своё: таблицы, почтовое событие, страницы админки,
 * компонент публичной части. Удаление снимает всё зарегистрированное.
 */
final class bxmax_booking extends CModule
{
	public const EVENT_TYPE = 'BXMAX_BOOKING_NEW';

	public $MODULE_ID = 'bxmax.booking';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	public $MODULE_GROUP_RIGHTS = 'Y';
	public $PARTNER_NAME;
	public $PARTNER_URI;

	public function __construct()
	{
		$arModuleVersion = [];
		include __DIR__ . '/version.php';

		$this->MODULE_VERSION = $arModuleVersion['VERSION'];
		$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		$this->MODULE_NAME = Loc::getMessage('BXMAX_BOOKING_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('BXMAX_BOOKING_MODULE_DESC');
		$this->PARTNER_NAME = Loc::getMessage('BXMAX_BOOKING_PARTNER_NAME');
		$this->PARTNER_URI = '';
	}

	/**
	 * Уровни доступа к модулю, которые видит админка групп пользователей.
	 */
	public static function GetModuleRightList(): array
	{
		return [
			'reference_id' => ['D', 'R', 'W'],
			'reference' => [
				'[D] ' . Loc::getMessage('BXMAX_BOOKING_RIGHT_D'),
				'[R] ' . Loc::getMessage('BXMAX_BOOKING_RIGHT_R'),
				'[W] ' . Loc::getMessage('BXMAX_BOOKING_RIGHT_W'),
			],
		];
	}

	public function GetPath($notDocumentRoot = false): string
	{
		$path = str_replace('\\', '/', dirname(__DIR__));

		return $notDocumentRoot
			? str_replace(Application::getDocumentRoot(), '', $path)
			: $path;
	}

	public function DoInstall(): void
	{
		global $APPLICATION;

		try
		{
			$this->InstallDB();
			$this->InstallEvents();
			$this->InstallFiles();
			ModuleManager::registerModule($this->MODULE_ID);
		}
		catch (Throwable $exception)
		{
			$APPLICATION->ThrowException($exception->getMessage());

			return;
		}

		$APPLICATION->IncludeAdminFile(
			Loc::getMessage('BXMAX_BOOKING_INSTALL_TITLE'),
			$this->GetPath() . '/install/step.php'
		);
	}

	public function DoUninstall(): void
	{
		global $APPLICATION;

		$request = Application::getInstance()->getContext()->getRequest();
		$saveData = $request->get('savedata') === 'Y';

		ModuleManager::unRegisterModule($this->MODULE_ID);
		$this->UnInstallFiles();
		$this->UnInstallEvents();

		if (!$saveData)
		{
			$this->UnInstallDB();
		}

		Option::delete($this->MODULE_ID);

		$APPLICATION->IncludeAdminFile(
			Loc::getMessage('BXMAX_BOOKING_UNINSTALL_TITLE'),
			$this->GetPath() . '/install/unstep.php'
		);
	}

	public function InstallDB(): void
	{
		global $DB;

		$errors = $DB->RunSQLBatch($this->GetPath() . '/install/db/' . mb_strtolower($DB->type) . '/install.sql');
		if (is_array($errors) && $errors !== [])
		{
			throw new RuntimeException(implode('; ', $errors));
		}
	}

	public function UnInstallDB(): void
	{
		global $DB;

		$DB->RunSQLBatch($this->GetPath() . '/install/db/' . mb_strtolower($DB->type) . '/uninstall.sql');
	}

	/**
	 * Почтовое событие и шаблон письма администратору.
	 */
	public function InstallEvents(): void
	{
		$existing = EventTypeTable::getList([
			'select' => ['ID'],
			'filter' => ['=EVENT_NAME' => self::EVENT_TYPE],
			'limit' => 1,
		])->fetch();

		if (!$existing)
		{
			EventTypeTable::add([
				'LID' => 'ru',
				'EVENT_NAME' => self::EVENT_TYPE,
				'NAME' => Loc::getMessage('BXMAX_BOOKING_EVENT_NAME'),
				'DESCRIPTION' => Loc::getMessage('BXMAX_BOOKING_EVENT_DESC'),
				'SORT' => 150,
			]);
		}

		$message = EventMessageTable::getList([
			'select' => ['ID'],
			'filter' => ['=EVENT_NAME' => self::EVENT_TYPE],
			'limit' => 1,
		])->fetch();

		if (!$message)
		{
			$addResult = EventMessageTable::add([
				'EVENT_NAME' => self::EVENT_TYPE,
				'LID' => 's1',
				'ACTIVE' => 'Y',
				'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO' => '#EMAIL_TO#',
				'BODY_TYPE' => 'text',
				'SUBJECT' => Loc::getMessage('BXMAX_BOOKING_EVENT_SUBJECT'),
				'MESSAGE' => Loc::getMessage('BXMAX_BOOKING_EVENT_MESSAGE'),
			]);

			foreach (\Bitrix\Main\SiteTable::getList(['select' => ['LID']])->fetchAll() as $site)
			{
				EventMessageSiteTable::add([
					'EVENT_MESSAGE_ID' => (int)$addResult->getId(),
					'SITE_ID' => $site['LID'],
				]);
			}
		}
	}

	public function UnInstallEvents(): void
	{
		$messages = EventMessageTable::getList([
			'select' => ['ID'],
			'filter' => ['=EVENT_NAME' => self::EVENT_TYPE],
		])->fetchAll();

		foreach ($messages as $message)
		{
			$sites = EventMessageSiteTable::getList([
				'select' => ['EVENT_MESSAGE_ID', 'SITE_ID'],
				'filter' => ['=EVENT_MESSAGE_ID' => (int)$message['ID']],
			])->fetchAll();

			foreach ($sites as $site)
			{
				EventMessageSiteTable::delete([
					'EVENT_MESSAGE_ID' => (int)$site['EVENT_MESSAGE_ID'],
					'SITE_ID' => $site['SITE_ID'],
				]);
			}

			EventMessageTable::delete((int)$message['ID']);
		}

		$types = EventTypeTable::getList([
			'select' => ['ID'],
			'filter' => ['=EVENT_NAME' => self::EVENT_TYPE],
		])->fetchAll();

		foreach ($types as $type)
		{
			EventTypeTable::delete((int)$type['ID']);
		}
	}

	public function InstallFiles(): void
	{
		CopyDirFiles(
			$this->GetPath() . '/install/admin',
			Application::getDocumentRoot() . '/bitrix/admin',
			true,
			true
		);

		CopyDirFiles(
			$this->GetPath() . '/install/components',
			Application::getDocumentRoot() . '/local/components',
			true,
			true
		);
	}

	public function UnInstallFiles(): void
	{
		DeleteDirFilesEx('/bitrix/admin/bxmax_booking_entry_list.php');
		DeleteDirFilesEx('/bitrix/admin/bxmax_booking_slot_list.php');
		DeleteDirFilesEx('/local/components/bxmax/booking.form');

		$vendorDir = Application::getDocumentRoot() . '/local/components/bxmax';
		if (is_dir($vendorDir) && (scandir($vendorDir) === ['.', '..']))
		{
			rmdir($vendorDir);
		}
	}
}
