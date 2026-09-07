<?php

declare(strict_types=1);

namespace Bxmax\Booking\Dto;

use Bitrix\Main\Type\DateTime;

/**
 * Слот в том виде, в котором он отдаётся наружу: без каких-либо данных заявки.
 */
final readonly class SlotView
{
	public const STATUS_FREE = 'free';
	public const STATUS_TAKEN = 'taken';

	public function __construct(
		public int $id,
		public DateTime $startsAt,
		public DateTime $endsAt,
		public string $status,
	) {
	}

	public function isFree(): bool
	{
		return $this->status === self::STATUS_FREE;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'startsAt' => self::toIso($this->startsAt),
			'endsAt' => self::toIso($this->endsAt),
			'status' => $this->status,
		];
	}

	private static function toIso(DateTime $value): string
	{
		return $value->format(DATE_ATOM);
	}
}
