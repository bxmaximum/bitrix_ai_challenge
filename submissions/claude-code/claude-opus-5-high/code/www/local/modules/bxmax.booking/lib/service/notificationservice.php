<?php

declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Mail\Event;
use Bxmax\Booking\Dto\BookingConfirmation;

/**
 * Уведомление администратора о новой заявке.
 * Отправка идёт через почтовое событие Битрикса, не через mail().
 */
final class NotificationService
{
	public const EVENT_TYPE = 'BXMAX_BOOKING_NEW';

	public function notifyAdmin(BookingConfirmation $confirmation): void
	{
		Event::send([
			'EVENT_NAME' => self::EVENT_TYPE,
			'LID' => $this->getSiteId(),
			'C_FIELDS' => [
				'BOOKING_ID' => (string)$confirmation->bookingId,
				'EMAIL_TO' => $this->getAdminEmail(),
				'DATE_TIME' => $confirmation->startsAt->format('d.m.Y H:i')
					. '–' . $confirmation->endsAt->format('H:i'),
				'MASTER' => $confirmation->masterName,
				'SERVICE' => $confirmation->serviceName,
				'PRICE' => $confirmation->servicePrice,
				'CLIENT_NAME' => $confirmation->name,
				'CLIENT_PHONE' => $confirmation->phone,
				'CONSENT_AT' => $confirmation->consentAt->format('d.m.Y H:i:s'),
				'ADMIN_LINK' => $this->getAdminLink(),
			],
		]);
	}

	private function getSiteId(): string
	{
		$site = Context::getCurrent()?->getSite();

		return is_string($site) && $site !== '' ? $site : 's1';
	}

	private function getAdminEmail(): string
	{
		$email = (string)Option::get('bxmax.booking', 'admin_email', '');
		if ($email !== '')
		{
			return $email;
		}

		return (string)Option::get('main', 'email_from', 'admin@' . (Context::getCurrent()?->getServer()?->getHttpHost() ?: 'localhost'));
	}

	private function getAdminLink(): string
	{
		$request = Context::getCurrent()?->getRequest();
		$host = $request?->getHttpHost() ?: '';
		$scheme = $request !== null && $request->isHttps() ? 'https' : 'http';

		return $host === ''
			? '/bitrix/admin/bxmax_booking_entry_list.php?lang=ru'
			: $scheme . '://' . $host . '/bitrix/admin/bxmax_booking_entry_list.php?lang=ru';
	}
}
