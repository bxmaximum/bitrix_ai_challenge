<?php

declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\SlotRepository;

/**
 * Генерация расписания: каждому мастеру — слоты на ближайшие N дней (по умолчанию 14),
 * ежедневно с 10:00 до 20:00 с шагом 60 минут. Идемпотентна.
 */
final class ScheduleGenerator
{
    public const DAYS = 14;
    public const FIRST_HOUR = 10;
    public const LAST_HOUR = 20;
    public const STEP_MINUTES = 60;

    public function __construct(
        private readonly CatalogRepository $catalog,
        private readonly SlotRepository $slots,
    ) {}

    /**
     * @return int число созданных слотов
     */
    public function generate(int $days = self::DAYS): int
    {
        $rows = [];
        $today = new \DateTimeImmutable('today');

        foreach ($this->catalog->getMasterIds() as $masterId)
        {
            for ($day = 0; $day < $days; $day++)
            {
                $date = $today->modify('+' . $day . ' day');
                $start = $date->setTime(self::FIRST_HOUR, 0);
                $end = $date->setTime(self::LAST_HOUR, 0);

                for ($cursor = $start; $cursor < $end; $cursor = $cursor->modify('+' . self::STEP_MINUTES . ' minutes'))
                {
                    $rows[] = [
                        'MASTER_ID' => $masterId,
                        'STARTS_AT' => DateTime::createFromPhp(\DateTime::createFromImmutable($cursor)),
                        'ENDS_AT' => DateTime::createFromPhp(\DateTime::createFromImmutable(
                            $cursor->modify('+' . self::STEP_MINUTES . ' minutes')
                        )),
                    ];
                }
            }
        }

        return $this->slots->insertIgnore($rows);
    }
}
