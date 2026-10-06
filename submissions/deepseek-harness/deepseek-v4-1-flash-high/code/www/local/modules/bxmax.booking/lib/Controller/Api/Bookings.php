<?php

namespace Bxmax\Booking\Controller\Api;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\AutoWire\Parameter;
use Bitrix\Main\Engine\Controller;
use Bxmax\Booking\Service\BookingService;

/**
 * bxmax:booking.api.bookings.create
 *
 * Public endpoint: creates a booking for a free slot.
 */
final class Bookings extends Controller
{
	public function configureActions(): array
	{
		return [
			'create' => [
				'-prefilters' => [
					Authentication::class,
				],
			],
		];
	}

	public function getAutoWiredParameters(): array
	{
		return [
			new Parameter(
				BookingService::class,
				static fn(string $className) => ServiceLocator::getInstance()->get($className)
			),
		];
	}

	/**
	 * @return array{bookingId?: int}
	 */
	public function createAction(
		BookingService $bookingService,
		int $slotId = 0,
		int $serviceId = 0,
		string $name = '',
		string $phone = '',
		$consent = null,
	): array
	{
		$result = $bookingService->book($slotId, $serviceId, $name, $phone, $consent);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return [];
		}

		return $result->getData();
	}
}
