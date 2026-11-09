<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SiteTable;

Loc::loadMessages(__FILE__);

class bxmax_booking extends CModule
{
	public const MAIL_EVENT = 'BXMAX_BOOKING_NEW';

	public $MODULE_ID = 'bxmax.booking';
	public $MODULE_GROUP_RIGHTS = 'Y';

	public function __construct()
	{
		$arModuleVersion = [];
		include __DIR__ . '/version.php';

		$this->MODULE_VERSION = $arModuleVersion['VERSION'];
		$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		$this->MODULE_NAME = Loc::getMessage('BXMAX_BOOKING_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('BXMAX_BOOKING_MODULE_DESCRIPTION');
		$this->PARTNER_NAME = 'BXMax';
		$this->PARTNER_URI = 'https://bxmax.ru';
	}

	public function GetModuleRightList(): array
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

	public function DoInstall(): void
	{
		global $APPLICATION, $USER;

		if (!$USER->IsAdmin())
		{
			return;
		}

		$this->install();

		$APPLICATION->IncludeAdminFile(
			Loc::getMessage('BXMAX_BOOKING_INSTALL_TITLE'),
			__DIR__ . '/step.php'
		);
	}

	public function DoUninstall(): void
	{
		global $APPLICATION, $USER;

		if (!$USER->IsAdmin())
		{
			return;
		}

		$request = Context::getCurrent()->getRequest();
		if ((int)$request->get('step') < 2)
		{
			// Шаг 1: спросить, сохранять ли таблицы с расписанием и заявками.
			$APPLICATION->IncludeAdminFile(
				Loc::getMessage('BXMAX_BOOKING_UNINSTALL_TITLE'),
				__DIR__ . '/unstep1.php'
			);

			return;
		}

		$this->uninstall($request->get('savedata') === 'Y');

		$APPLICATION->IncludeAdminFile(
			Loc::getMessage('BXMAX_BOOKING_UNINSTALL_TITLE'),
			__DIR__ . '/unstep2.php'
		);
	}

	/**
	 * Полная установка без интерфейса: для админки, миграций и CLI.
	 */
	public function install(): bool
	{
		if (!$this->InstallDB())
		{
			return false;
		}

		$this->InstallEvents();
		$this->InstallFiles();

		return true;
	}

	/**
	 * Полное удаление без интерфейса. $saveData = true оставляет таблицы расписания и заявок.
	 */
	public function uninstall(bool $saveData = false): bool
	{
		if (!$this->UnInstallDB(['savedata' => $saveData]))
		{
			return false;
		}

		$this->UnInstallEvents();
		$this->UnInstallFiles();

		return true;
	}

	public function InstallDB(): bool
	{
		global $APPLICATION;

		// Таблицы и агент: install/migrations/{tables,agents}.php
		$result = $this->installMigrations();
		if (!$result->isSuccess())
		{
			$APPLICATION->ThrowException(implode('<br>', $result->getErrorMessages()));

			return false;
		}

		ModuleManager::registerModule($this->MODULE_ID);

		return true;
	}

	/**
	 * @param array{savedata?: bool} $params savedata = true оставляет таблицы расписания и заявок.
	 */
	public function UnInstallDB(array $params = []): bool
	{
		global $APPLICATION;

		$dropTables = empty($params['savedata']);
		$result = $this->uninstallMigrations($dropTables);
		if (!$result->isSuccess())
		{
			$APPLICATION->ThrowException(implode('<br>', $result->getErrorMessages()));

			return false;
		}

		Option::delete($this->MODULE_ID);
		// Снимает также агентов и групповые права модуля.
		ModuleManager::unRegisterModule($this->MODULE_ID);

		return true;
	}

	/**
	 * Тип почтового события и шаблон письма администратору.
	 * D7 API записи почтовых типов нет, поэтому CEventType / CEventMessage.
	 */
	public function InstallEvents(): bool
	{
		$typeExists = CEventType::GetList(['TYPE_ID' => self::MAIL_EVENT, 'LID' => 'ru'])->Fetch();
		if (!$typeExists)
		{
			(new CEventType())->Add([
				'LID' => 'ru',
				'EVENT_NAME' => self::MAIL_EVENT,
				'NAME' => Loc::getMessage('BXMAX_BOOKING_MAIL_TYPE_NAME'),
				'DESCRIPTION' => Loc::getMessage('BXMAX_BOOKING_MAIL_TYPE_DESCRIPTION'),
			]);
		}

		$messageExists = CEventMessage::GetList('id', 'asc', ['TYPE_ID' => self::MAIL_EVENT])->Fetch();
		if (!$messageExists)
		{
			$siteIds = array_column(SiteTable::getList(['select' => ['LID']])->fetchAll(), 'LID');
			if ($siteIds)
			{
				(new CEventMessage())->Add([
					'ACTIVE' => 'Y',
					'EVENT_NAME' => self::MAIL_EVENT,
					'LID' => $siteIds,
					'LANGUAGE_ID' => 'ru',
					'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
					'EMAIL_TO' => '#EMAIL_TO#',
					'SUBJECT' => Loc::getMessage('BXMAX_BOOKING_MAIL_SUBJECT'),
					'BODY_TYPE' => 'html',
					'MESSAGE' => Loc::getMessage('BXMAX_BOOKING_MAIL_BODY'),
				]);
			}
		}

		return true;
	}

	public function UnInstallEvents(): bool
	{
		$messages = CEventMessage::GetList('id', 'asc', ['TYPE_ID' => self::MAIL_EVENT]);
		while ($message = $messages->Fetch())
		{
			CEventMessage::Delete((int)$message['ID']);
		}

		CEventType::Delete(self::MAIL_EVENT);

		return true;
	}

	public function InstallFiles(): bool
	{
		$root = $_SERVER['DOCUMENT_ROOT'];

		CopyDirFiles(__DIR__ . '/admin', $root . '/bitrix/admin', true, false);
		CopyDirFiles(__DIR__ . '/components', $root . '/local/components', true, true);

		return true;
	}

	public function UnInstallFiles(): bool
	{
		$root = $_SERVER['DOCUMENT_ROOT'];

		DeleteDirFiles(__DIR__ . '/admin', $root . '/bitrix/admin');
		DeleteDirFilesEx('/local/components/bxmax/booking.form');

		// Каталог вендора убираем, только если в нём не осталось чужих компонентов.
		$vendorDir = new Directory($root . '/local/components/bxmax');
		if ($vendorDir->isExists() && !$vendorDir->getChildren())
		{
			$vendorDir->delete();
		}

		return true;
	}
}
