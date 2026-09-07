<?php

declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Dto\SlotView;
use Bxmax\Booking\ErrorCode;
use Bxmax\Booking\Repository\MasterRepository;
use Bxmax\Booking\Repository\SlotRepository;

/**
 * Чтение расписания: неделя слотов одного мастера.
 */
final class ScheduleService
{
	public const WEEK_DAYS = 7;

	public function __construct(
		private readonly SlotRepository $slots,
		private readonly MasterRepository $masters,
	) {
	}

	/**
	 * @return Result данные: ['slots' => SlotView[]]
	 */
	public function getWeek(int $masterId, string $weekStart): Result
	{
		$result = new Result();

		if ($masterId <= 0 || !$this->masters->exists($masterId))
		{
			return $result->addError(new Error('Мастер не найден.', ErrorCode::MASTER_NOT_FOUND));
		}

		$from = self::normalizeWeekStart($weekStart);
		$to = self::addDays($from, self::WEEK_DAYS);

		$slots = $this->slots->listForMasterInRange($masterId, $from, $to);

		return $result->setData(['slots' => $this->markPastAsTaken($slots)]);
	}

	/**
	 * Понедельник недели, в которую попадает переданная дата.
	 * Некорректная или пустая дата трактуется как «текущая неделя».
	 */
	public static function normalizeWeekStart(string $raw): DateTime
	{
		$raw = trim($raw);
		$timestamp = false;

		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1)
		{
			$timestamp = strtotime($raw . ' 00:00:00');
		}

		if ($timestamp === false)
		{
			$timestamp = time();
		}

		$monday = strtotime('monday this week', $timestamp);

		return DateTime::createFromTimestamp($monday);
	}

	public static function addDays(DateTime $date, int $days): DateTime
	{
		$copy = clone $date;

		return $copy->add($days . ' days');
	}

	/**
	 * @param SlotView[] $slots
	 * @return SlotView[]
	 */
	private function markPastAsTaken(array $slots): array
	{
		$now = time();

		return array_map(
			static fn(SlotView $slot): SlotView => $slot->isFree() && $slot->startsAt->getTimestamp() <= $now
				? new SlotView($slot->id, $slot->startsAt, $slot->endsAt, SlotView::STATUS_TAKEN)
				: $slot,
			$slots
		);
	}
}
