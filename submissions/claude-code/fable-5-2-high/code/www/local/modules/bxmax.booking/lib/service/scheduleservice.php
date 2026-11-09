<?php

declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\DB\DuplicateEntryException;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\SlotRepository;

/**
 * Генератор расписания: каждому активному мастеру — слоты на ближайшие дни.
 */
class ScheduleService
{
	public const HORIZON_DAYS = 14;
	public const DAY_START_HOUR = 10;
	public const DAY_END_HOUR = 20;
	public const SLOT_MINUTES = 60;

	public function __construct(
		private readonly SlotRepository $slots,
		private readonly CatalogRepository $catalog,
	)
	{
	}

	/**
	 * Идемпотентно дописывает недостающие слоты начиная с сегодняшнего дня.
	 *
	 * @return int Сколько слотов создано.
	 */
	public function generate(int $days = self::HORIZON_DAYS): int
	{
		$days = max(1, $days);
		$today = new \DateTimeImmutable('today');
		$from = $this->toBitrix($today);
		$to = $this->toBitrix($today->modify('+' . $days . ' days'));

		$created = 0;
		foreach ($this->catalog->getMasters() as $master)
		{
			$existing = $this->slots->getExistingStarts($master['id'], $from, $to);

			$rows = [];
			for ($day = 0; $day < $days; $day++)
			{
				$date = $today->modify('+' . $day . ' days');
				$start = $date->setTime(self::DAY_START_HOUR, 0);
				$dayEnd = $date->setTime(self::DAY_END_HOUR, 0);

				while ($start < $dayEnd)
				{
					$end = $start->modify('+' . self::SLOT_MINUTES . ' minutes');
					if ($end > $dayEnd)
					{
						break;
					}

					if (!isset($existing[$start->getTimestamp()]))
					{
						$rows[] = [
							'MASTER_ID' => $master['id'],
							'STARTS_AT' => $this->toBitrix($start),
							'ENDS_AT' => $this->toBitrix($end),
						];
					}

					$start = $end;
				}
			}

			$created += $this->insert($rows);
		}

		return $created;
	}

	/**
	 * @param list<array{MASTER_ID: int, STARTS_AT: DateTime, ENDS_AT: DateTime}> $rows
	 */
	private function insert(array $rows): int
	{
		if (!$rows)
		{
			return 0;
		}

		try
		{
			return $this->slots->addMany($rows)->isSuccess() ? count($rows) : 0;
		}
		catch (DuplicateEntryException)
		{
			// Параллельный запуск (агент и развёртывание одновременно) уже создал часть слотов:
			// уникальный индекс не дал задвоить, оставшиеся добавляем по одному.
			$created = 0;
			foreach ($rows as $row)
			{
				try
				{
					$created += $this->slots->addMany([$row])->isSuccess() ? 1 : 0;
				}
				catch (DuplicateEntryException)
				{
				}
			}

			return $created;
		}
	}

	private function toBitrix(\DateTimeImmutable $date): DateTime
	{
		return DateTime::createFromPhp(\DateTime::createFromImmutable($date));
	}
}
