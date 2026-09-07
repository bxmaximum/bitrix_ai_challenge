<?php

declare(strict_types=1);

namespace Bxmax\Booking\Dto;

use Bitrix\Main\Type\DateTime;

/**
 * Подтверждённая запись — то, что показывается на экране успеха и уходит администратору.
 */
final readonly class BookingConfirmation
{
	public function __construct(
		public int $bookingId,
		public int $slotId,
		public DateTime $startsAt,
		public DateTime $endsAt,
		public int $masterId,
		public string $masterName,
		public int $serviceId,
		public string $serviceName,
		public string $servicePrice,
		public string $name,
		public string $phone,
		public DateTime $consentAt,
	) {
	}
}
