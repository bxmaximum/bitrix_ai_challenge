<?php

declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Repository\MasterRepository;
use Bxmax\Booking\Repository\SlotRepository;

/**
 * Генерация расписания: каждому мастеру слоты на ближайшие N дней,
 * ежедневно с 10:00 до 20:00 с шагом 60 минут.
 */
final class ScheduleGenerator
{
	public const DAYS = 14;
	public const HOUR_FROM = 10;
	public const HOUR_TO = 20;
	public const STEP_MINUTES = 60;

	public function __construct(
		private readonly SlotRepository $slots,
		private readonly MasterRepository $masters,
	) {
	}

	/**
	 * @return array{masters:int, created:int, planned:int}
	 */
	public function generate(int $days = self::DAYS, ?DateTime $startDay = null): array
	{
		$masters = $this->masters->getAll();
		$start = $startDay ?? new DateTime();
		$startDate = strtotime($start->format('Y-m-d') . ' 00:00:00');

		$rows = [];
		foreach (array_keys($masters) as $masterId)
		{
			for ($day = 0; $day < $days; $day++)
			{
				$dayStart = strtotime('+' . $day . ' day', $startDate);
				for ($minutes = self::HOUR_FROM * 60; $minutes < self::HOUR_TO * 60; $minutes += self::STEP_MINUTES)
				{
					$from = $dayStart + $minutes * 60;
					$to = $from + self::STEP_MINUTES * 60;
					$rows[] = [
						'MASTER_ID' => $masterId,
						'STARTS_AT' => date('Y-m-d H:i:s', $from),
						'ENDS_AT' => date('Y-m-d H:i:s', $to),
					];
				}
			}
		}

		$created = 0;
		foreach (array_chunk($rows, 500) as $chunk)
		{
			$created += $this->slots->insertIgnoreBatch($chunk);
		}

		return [
			'masters' => count($masters),
			'created' => $created,
			'planned' => count($rows),
		];
	}
}
