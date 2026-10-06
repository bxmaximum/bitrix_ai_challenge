<?php

namespace Bxmax\Booking\Service;

use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Repository\MasterRepository;
use Bxmax\Booking\Repository\SlotRepository;

/**
 * Builds the working schedule of the studio.
 *
 * Every master works daily from 10:00 to 20:00, one slot per hour, and the
 * schedule is kept for the next 14 days. Generation is idempotent: an existing
 * (master, hour) pair is never created twice and never overwritten, so a second
 * deploy run cannot unbook or reopen anything.
 */
final class ScheduleService
{
	public const HORIZON_DAYS = 14;
	public const WORK_DAY_FIRST_HOUR = 10;
	public const WORK_DAY_LAST_HOUR = 20;
	public const SLOT_LENGTH_MINUTES = 60;

	public function __construct(
		private readonly SlotRepository $slotRepository,
		private readonly MasterRepository $masterRepository,
	)
	{
	}

	/**
	 * Generates the whole horizon for every master.
	 *
	 * @param DateTime|null $from first day of the horizon (today by default)
	 * @return int number of created slots
	 */
	public function generateHorizon(?DateTime $from = null): int
	{
		$from ??= new DateTime();
		$fromDayTs = $this->toDayTimestamp($from);

		$created = 0;
		foreach ($this->masterRepository->getActiveIds() as $masterId)
		{
			$created += $this->generateForMaster($masterId, $fromDayTs);
		}

		return $created;
	}

	/**
	 * @return int number of created slots
	 */
	public function generateForMaster(int $masterId, int $fromDayTs): int
	{
		$firstMoment = DateTime::createFromTimestamp($fromDayTs);
		$lastMoment = DateTime::createFromTimestamp($fromDayTs + self::HORIZON_DAYS * 86400);

		$existing = $this->slotRepository->mapExistingStarts($masterId, $firstMoment, $lastMoment);

		$created = 0;
		for ($day = 0; $day < self::HORIZON_DAYS; $day++)
		{
			[$year, $month, $dayOfMonth] = $this->splitDay($fromDayTs + $day * 86400);

			for ($hour = self::WORK_DAY_FIRST_HOUR; $hour < self::WORK_DAY_LAST_HOUR; $hour++)
			{
				$key = sprintf('%04d-%02d-%02d %02d:00', $year, $month, $dayOfMonth, $hour);
				if (isset($existing[$key]))
				{
					continue;
				}

				$startsAtTs = mktime($hour, 0, 0, $month, $dayOfMonth, $year);
				$startsAt = DateTime::createFromTimestamp($startsAtTs);
				$endsAt = DateTime::createFromTimestamp($startsAtTs + self::SLOT_LENGTH_MINUTES * 60);

				$this->slotRepository->add($masterId, $startsAt, $endsAt);
				$created++;
			}
		}

		return $created;
	}

	/**
	 * Agent handler: moves the 14-day horizon forward every night.
	 */
	public static function extendHorizonAgent(): string
	{
		$service = new self(new SlotRepository(), new MasterRepository());
		$service->generateHorizon(new DateTime());

		return '\Bxmax\Booking\Service\ScheduleService::extendHorizonAgent();';
	}

	private function toDayTimestamp(DateTime $moment): int
	{
		return mktime(0, 0, 0, (int)$moment->format('n'), (int)$moment->format('j'), (int)$moment->format('Y'));
	}

	/**
	 * @return array{0:int, 1:int, 2:int}
	 */
	private function splitDay(int $timestamp): array
	{
		return [
			(int)date('Y', $timestamp),
			(int)date('n', $timestamp),
			(int)date('j', $timestamp),
		];
	}
}
