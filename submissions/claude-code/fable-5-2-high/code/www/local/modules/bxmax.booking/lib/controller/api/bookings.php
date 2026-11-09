<?php

declare(strict_types=1);

namespace Bxmax\Booking\Controller\Api;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\DisablePrefilters;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\HttpMethod;
use Bxmax\Booking\Controller\JsonAwareController;
use Bxmax\Booking\Service\BookingService;

/**
 * bxmax:booking.api.bookings.*
 */
final class Bookings extends JsonAwareController
{
	/**
	 * Запись гостя: авторизация не нужна, CSRF остаётся (sessid или X-Bitrix-Csrf-Token).
	 * Параметры без типа: пустое или кривое значение даёт ошибку контракта
	 * (VALIDATION, CONSENT_REQUIRED), а не ошибку связывания аргументов.
	 */
	#[DisablePrefilters([ActionFilter\Authentication::class])]
	#[HttpMethod([ActionFilter\HttpMethod::METHOD_POST])]
	public function createAction(
		BookingService $bookingService,
		$slotId = '',
		$serviceId = '',
		$name = '',
		$phone = '',
		$consent = '',
	): ?array
	{
		$result = $bookingService->create(
			(int)$this->toString($slotId),
			(int)$this->toString($serviceId),
			$this->toString($name),
			$this->toString($phone),
			$this->isConsentGiven($consent)
		);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getData();
	}

	/**
	 * Согласие действительно, только если передано явное «да»: true из JSON либо
	 * Y / yes / 1 / true / on из формы. Пустое значение, 0, false, N и любой мусор — отказ.
	 */
	private function isConsentGiven(mixed $consent): bool
	{
		if (is_bool($consent))
		{
			return $consent;
		}

		return in_array(mb_strtolower($this->toString($consent)), ['y', 'yes', '1', 'true', 'on'], true);
	}
}
