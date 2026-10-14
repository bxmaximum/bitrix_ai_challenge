<?php

declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\SlotRepository;

/**
 * Генерация расписания: каждому активному мастеру — слоты на ближайшие 14 дней,
 * ежедневно с 10:00 до 20:00 с шагом 60 минут. Повторный запуск безопасен.
 */
class ScheduleService
{
    public const DAYS_AHEAD = 14;
    public const DAY_START_HOUR = 10;
    public const DAY_END_HOUR = 20;
    public const STEP_MINUTES = 60;

    public function __construct(
        private readonly SlotRepository $slots,
        private readonly CatalogRepository $catalog,
    )
    {
    }

    /**
     * @param int[]|null $masterIds null — все активные мастера.
     * @return Result data: ['inserted' => int]
     */
    public function generate(?array $masterIds = null, ?DateTime $today = null): Result
    {
        $result = new Result();

        if ($masterIds === null)
        {
            $masterIds = array_map(static fn ($master) => $master->id, $this->catalog->getMasters());
        }

        $rows = [];
        $dayStart = $this->startOfDay($today ?? new DateTime());
        for ($day = 0; $day < self::DAYS_AHEAD; $day++)
        {
            for ($minutes = self::DAY_START_HOUR * 60; $minutes + self::STEP_MINUTES <= self::DAY_END_HOUR * 60; $minutes += self::STEP_MINUTES)
            {
                $startsAt = (clone $dayStart)->add(sprintf('P%dDT%dM', $day, $minutes));
                $endsAt = (clone $startsAt)->add(sprintf('PT%dM', self::STEP_MINUTES));
                foreach ($masterIds as $masterId)
                {
                    $rows[] = [
                        'MASTER_ID' => (int)$masterId,
                        'STARTS_AT' => $startsAt,
                        'ENDS_AT' => $endsAt,
                    ];
                }
            }
        }

        $result->setData(['inserted' => $this->slots->insertIgnore($rows)]);

        return $result;
    }

    /**
     * Последний день, на который строится расписание (включительно).
     */
    public function getLastDay(?DateTime $today = null): DateTime
    {
        return $this->startOfDay($today ?? new DateTime())->add(sprintf('P%dD', self::DAYS_AHEAD - 1));
    }

    private function startOfDay(DateTime $date): DateTime
    {
        return (clone $date)->setTime(0, 0);
    }
}
