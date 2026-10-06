<?php

namespace Bxmax\Booking\Notify;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\Mail\EventManager;
use Bitrix\Main\SiteTable;

/**
 * Sends the "new booking" notification to the studio administrator through the
 * Bitrix mail event BXMAX_BOOKING_NEW (event type + message template are
 * registered by the module installer).
 *
 * The event is queued in b_event as usual and then the queue is flushed right
 * away: the deferred queue is normally drained by the mail module agent, and
 * that module is not present on this stand, so the notification would otherwise
 * sit in the queue forever.
 */
final class AdminNotifier implements MailNotifierInterface
{
	public const EVENT_NAME = 'BXMAX_BOOKING_NEW';
	public const OPTION_ADMIN_EMAIL = 'admin_email';

	public function notifyAdminAboutBooking(array $booking, array $slot, array $service, array $master): void
	{
		$result = Event::send([
			'EVENT_NAME' => self::EVENT_NAME,
			'LID' => $this->getSiteId(),
			'LANGUAGE_ID' => LANGUAGE_ID,
			'C_FIELDS' => [
				'ADMIN_EMAIL' => $this->getAdminEmail(),
				'BOOKING_ID' => (string)$booking['id'],
				'MASTER_NAME' => (string)$master['name'],
				'SERVICE_NAME' => (string)$service['name'],
				'SLOT_DATE' => (string)$slot['date'],
				'SLOT_TIME' => (string)$slot['time'],
				'CLIENT_NAME' => (string)$booking['name'],
				'CLIENT_PHONE' => (string)$booking['phone'],
				'CONSENT_AT' => (string)$booking['consentAt'],
			],
		]);

		if ($result->isSuccess())
		{
			EventManager::executeEvents();
		}
	}

	private function getSiteId(): string
	{
		return defined('SITE_ID') && SITE_ID !== '' ? SITE_ID : 's1';
	}

	/**
	 * Recipient: module option, then the site e-mail, then the default one.
	 */
	public function getAdminEmail(): string
	{
		$email = trim((string)Option::get('bxmax.booking', self::OPTION_ADMIN_EMAIL, ''));
		if ($email !== '')
		{
			return $email;
		}

		$row = SiteTable::getList([
			'select' => ['EMAIL', 'SERVER_NAME'],
			'filter' => ['=LID' => $this->getSiteId()],
			'limit' => 1,
		])->fetch();

		$email = trim((string)($row['EMAIL'] ?? ''));
		if ($email !== '')
		{
			return $email;
		}

		$serverName = (string)($row['SERVER_NAME'] ?? '');

		return $serverName !== '' ? 'admin@' . $serverName : 'admin@localhost';
	}
}
