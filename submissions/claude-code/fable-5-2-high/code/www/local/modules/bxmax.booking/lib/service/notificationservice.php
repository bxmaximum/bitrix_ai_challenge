<?php

declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Type\DateTime;

/**
 * Уведомления администратора студии через почтовые события Битрикса.
 */
class NotificationService
{
	public const EVENT_NEW_ENTRY = 'BXMAX_BOOKING_NEW';

	private const MODULE_ID = 'bxmax.booking';

	public function notifyNewEntry(
		int $entryId,
		DateTime $startsAt,
		string $masterName,
		string $serviceName,
		string $clientName,
		string $clientPhone,
		DateTime $consentAt,
	): void
	{
		$siteId = $this->getSiteId();
		$emailTo = $this->getAdminEmail();
		if ($siteId === null || $emailTo === '')
		{
			return;
		}

		try
		{
			// Очередь b_event: письмо уйдёт после хита или по cron и не задержит ответ посетителю.
			Event::send([
				'EVENT_NAME' => self::EVENT_NEW_ENTRY,
				'LID' => $siteId,
				'LANGUAGE_ID' => 'ru',
				'DUPLICATE' => 'N',
				'C_FIELDS' => [
					'EMAIL_TO' => $emailTo,
					'ENTRY_ID' => (string)$entryId,
					'MASTER_NAME' => $masterName,
					'SERVICE_NAME' => $serviceName,
					'SLOT_DATE' => mb_strtolower(FormatDate('j F Y, l', $startsAt->getTimestamp())),
					'SLOT_TIME' => $startsAt->format('H:i'),
					'NAME' => $clientName,
					'PHONE' => $clientPhone,
					'CONSENT_AT' => $consentAt->format('d.m.Y H:i:s'),
					'ADMIN_URL' => UrlManager::getInstance()->getHostUrl() . '/bitrix/admin/bxmax_booking_entries.php?lang=ru',
				],
			]);
		}
		catch (\Throwable $exception)
		{
			// Запись уже сохранена; сбой почты фиксируем в журнале, клиенту показываем успех.
			Application::getInstance()->getExceptionHandler()->writeToLog($exception);
		}
	}

	private function getAdminEmail(): string
	{
		$email = trim(Option::get(self::MODULE_ID, 'admin_email'));
		if ($email === '')
		{
			$email = trim(Option::get('main', 'email_from'));
		}

		return check_email($email) ? $email : '';
	}

	private function getSiteId(): ?string
	{
		$siteId = Context::getCurrent()?->getSite();
		if ($siteId)
		{
			return $siteId;
		}

		$site = SiteTable::getList([
			'select' => ['LID'],
			'filter' => ['=ACTIVE' => 'Y'],
			'order' => ['DEF' => 'DESC', 'SORT' => 'ASC'],
			'limit' => 1,
			'cache' => ['ttl' => 3600],
		])->fetch();

		return $site ? (string)$site['LID'] : null;
	}
}
