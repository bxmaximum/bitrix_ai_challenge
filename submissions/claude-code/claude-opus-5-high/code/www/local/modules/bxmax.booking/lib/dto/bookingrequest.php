<?php

declare(strict_types=1);

namespace Bxmax\Booking\Dto;

/**
 * Входные данные заявки в том виде, как их прислал посетитель.
 */
final readonly class BookingRequest
{
	public function __construct(
		public int $slotId,
		public int $serviceId,
		public string $name,
		public string $phone,
		public bool $consent,
	) {
	}

	public static function fromArray(array $data): self
	{
		return new self(
			(int)($data['slotId'] ?? 0),
			(int)($data['serviceId'] ?? 0),
			trim((string)($data['name'] ?? '')),
			trim((string)($data['phone'] ?? '')),
			self::readConsent($data['consent'] ?? null),
		);
	}

	private static function readConsent(mixed $raw): bool
	{
		if (is_bool($raw))
		{
			return $raw;
		}

		if (is_int($raw))
		{
			return $raw === 1;
		}

		if (is_string($raw))
		{
			return in_array(mb_strtolower(trim($raw)), ['1', 'y', 'yes', 'true', 'on'], true);
		}

		return false;
	}
}
