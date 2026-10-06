<?php

namespace Bxmax\Booking\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Repository\MasterRepository;
use Bxmax\Booking\Repository\SlotRepository;
use Bxmax\Booking\Value\ErrorCode;
use Bxmax\Booking\Value\SlotStatus;

/**
 * Read model of the public booking widget: a week of slots of one master.
 */
final class SlotService
{
	public const DAYS_IN_WEEK = 7;

	public function __construct(
		private readonly SlotRepository $slotRepository,
		private readonly MasterRepository $masterRepository,
	)
	{
	}

	/**
	 * @return Result data: ['slots' => list of ['id','startsAt','endsAt','status']]
	 */
	public function getWeek(int $masterId, string $weekStart): Result
	{
		$result = new Result();

		if ($masterId <= 0 || !$this->masterRepository->isExists($masterId))
		{
			return $result->addError(new Error('Master not found', ErrorCode::MASTER_NOT_FOUND));
		}

		$weekStartTs = $this->parseDate($weekStart);
		if ($weekStartTs === null)
		{
			return $result->addError(new Error('weekStart must be a date in YYYY-MM-DD format', ErrorCode::VALIDATION));
		}

		$from = DateTime::createFromTimestamp($weekStartTs);
		$to = DateTime::createFromTimestamp($weekStartTs + self::DAYS_IN_WEEK * 86400);

		$slots = $this->slotRepository->findByMasterAndPeriod($masterId, $from, $to);
		$booked = $this->slotRepository->mapBookedSlots(array_map(
			static fn(array $slot): int => (int)$slot['ID'],
			$slots
		));

		$items = [];
		foreach ($slots as $slot)
		{
			$slotId = (int)$slot['ID'];
			$isClosed = ($slot['CLOSED'] ?? 'N') === 'Y';
			$isBooked = isset($booked[$slotId]);

			/** @var DateTime $startsAt */
			$startsAt = $slot['STARTS_AT'];
			/** @var DateTime $endsAt */
			$endsAt = $slot['ENDS_AT'];

			$items[] = [
				'id' => $slotId,
				'startsAt' => date('c', $startsAt->getTimestamp()),
				'endsAt' => date('c', $endsAt->getTimestamp()),
				'status' => ($isClosed || $isBooked) ? SlotStatus::TAKEN : SlotStatus::FREE,
			];
		}

		return $result->setData(['slots' => $items]);
	}

	private function parseDate(string $value): ?int
	{
		if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', trim($value), $matches))
		{
			return null;
		}

		$year = (int)$matches[1];
		$month = (int)$matches[2];
		$day = (int)$matches[3];

		if (!checkdate($month, $day, $year))
		{
			return null;
		}

		return mktime(0, 0, 0, $month, $day, $year);
	}
}
