<?php

declare(strict_types=1);

namespace Bxmax\Booking\Controller;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\Authentication;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\HttpMethod;
use Bitrix\Main\Engine\ActionFilter\FilterType;
use Bitrix\Main\Engine\ActionFilter\HttpMethod as HttpMethodFilter;
use Bitrix\Main\Engine\Controller;
use Bxmax\Booking\Dto\BookingRequest;
use Bxmax\Booking\Service\BookingService;

/**
 * Создание заявки на запись.
 * Действие: bxmax:booking.api.bookings.create
 */
final class Bookings extends Controller
{
	/**
	 * @return array{bookingId: int}|null
	 */
	#[Authentication(type: FilterType::DisablePrefilter)]
	#[HttpMethod([HttpMethodFilter::METHOD_POST])]
	public function createAction(
		BookingService $booking,
		int $slotId = 0,
		int $serviceId = 0,
		string $name = '',
		string $phone = '',
		$consent = null
	): ?array {
		$result = $booking->create(BookingRequest::fromArray([
			'slotId' => $slotId,
			'serviceId' => $serviceId,
			'name' => $name,
			'phone' => $phone,
			'consent' => $consent,
		]));

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return ['bookingId' => $result->getData()['confirmation']->bookingId];
	}
}
