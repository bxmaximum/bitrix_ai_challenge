<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bxmax\Booking\Install\MailEvents;

/**
 * Installer of the bxmax.booking module.
 *
 * Everything the module brings to the stand is registered and removed here:
 * database tables, admin pages, the public component, the mail event and the
 * schedule agent - so a fresh copy of the site can be brought to a working
 * state by code only.
 */
class bxmax_booking extends CModule
{
	public $MODULE_ID = 'bxmax.booking';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME = 'Онлайн-запись «Лак&Точка»';
	public $MODULE_DESCRIPTION = 'Расписание мастеров, онлайн-запись и уведомление администратора студии.';
	public $MODULE_GROUP_RIGHTS = 'Y';
	public $PARTNER_NAME = 'BXMAX';
	public $PARTNER_URI = 'https://example.com';

	private const ADMIN_FILES = [
		'bxmax_booking_entries.php',
		'bxmax_booking_slots.php',
	];

	public function __construct()
	{
		$arModuleVersion = [];
		include __DIR__ . '/version.php';

		$this->MODULE_VERSION = $arModuleVersion['VERSION'] ?? '1.0.0';
		$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'] ?? '';
	}

	public function DoInstall(): bool
	{
		global $DB;

		$DB->RunSQLBatch(__DIR__ . '/db/' . strtolower($DB->type) . '/install.sql');

		CopyDirFiles(__DIR__ . '/components', $_SERVER['DOCUMENT_ROOT'] . '/local/components', true, true);
		CopyDirFiles(__DIR__ . '/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin', true, true);

		RegisterModule($this->MODULE_ID);
		Loader::includeModule($this->MODULE_ID);

		MailEvents::install($this->getSiteId());
		$this->installRights();
		$this->installAgent();

		return true;
	}

	public function DoUninstall(): bool
	{
		global $DB;

		Loader::includeModule($this->MODULE_ID);

		$this->uninstallAgent();
		MailEvents::uninstall();

		foreach (self::ADMIN_FILES as $fileName)
		{
			$path = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/' . $fileName;
			if (is_file($path))
			{
				unlink($path);
			}
		}

		DeleteDirFilesEx('/local/components/bxmax/booking.slots');

		Option::delete($this->MODULE_ID);

		// Развёртывание площадки помечает её как уже настроенную: после ручного
		// удаления модуля оно не должно молча вернуть его на следующем запросе.
		if (defined('BXMAX_DEPLOY_REVISION') && defined('BXMAX_DEPLOY_OPTION'))
		{
			Option::set($this->MODULE_ID, BXMAX_DEPLOY_OPTION, (string)BXMAX_DEPLOY_REVISION);
		}

		UnRegisterModule($this->MODULE_ID);

		$DB->RunSQLBatch(__DIR__ . '/db/' . strtolower($DB->type) . '/uninstall.sql');

		return true;
	}

	/**
	 * Rights of the module pages: D - no access, R - read only, W - full access.
	 */
	public function GetModuleRightList(): array
	{
		return [
			'reference_id' => ['D', 'R', 'W'],
			'reference' => [
				'[D] Доступ закрыт',
				'[R] Просмотр заявок и слотов',
				'[W] Управление заявками и слотами',
			],
		];
	}

	public function GetModuleRightListDefault(): string
	{
		return 'D';
	}

	/**
	 * The module also works when it is deployed from the console or from a
	 * fresh copy of the stand where no request context exists yet.
	 */
	private function getSiteId(): string
	{
		return defined('SITE_ID') && SITE_ID !== '' ? SITE_ID : MailEvents::DEFAULT_SITE_ID;
	}

	private function installRights(): void
	{
		global $APPLICATION;

		if (!is_object($APPLICATION) || !method_exists($APPLICATION, 'SetGroupRight'))
		{
			return;
		}

		// The default installation keeps the module pages available to the
		// administrator group only - which is exactly the default group right.
		$APPLICATION->SetGroupRight($this->MODULE_ID, 1, 'W');
	}

	private function installAgent(): void
	{
		$agentName = '\\Bxmax\\Booking\\Service\\ScheduleService::extendHorizonAgent();';

		$exists = CAgent::GetList([], ['MODULE_ID' => $this->MODULE_ID, 'NAME' => $agentName])->Fetch();
		if ($exists)
		{
			return;
		}

		CAgent::AddAgent(
			$agentName,
			$this->MODULE_ID,
			'N',
			86400,
			'',
			'Y',
			ConvertTimeStamp(mktime(3, 0, 0, (int)date('n'), (int)date('j') + 1, (int)date('Y')), 'FULL')
		);
	}

	private function uninstallAgent(): void
	{
		CAgent::RemoveModuleAgents($this->MODULE_ID);
	}
}
