<?php

declare(strict_types=1);

namespace Bxmax\Booking\Application\Service;

use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Infrastructure\Repository\ContentRepository;
use Bxmax\Booking\Infrastructure\Repository\SlotRepository;

final class ScheduleService
{
    public function __construct(
        private readonly SlotRepository $slots,
        private readonly ContentRepository $content,
    ) {}

    public function provision(int $days = 14): void
    {
        $today = new \DateTimeImmutable('today');
        foreach ($this->content->getMasters() as $master)
        {
            $masterId = (int)$master['ID'];
            for ($day = 0; $day < $days; $day++)
            {
                $date = $today->modify('+' . $day . ' days');
                for ($hour = 10; $hour < 20; $hour++)
                {
                    $start = $date->setTime($hour, 0);
                    $end = $start->modify('+60 minutes');
                    $this->slots->add(
                        $masterId,
                        DateTime::createFromPhp(\DateTime::createFromImmutable($start)),
                        DateTime::createFromPhp(\DateTime::createFromImmutable($end)),
                    );
                }
            }
        }
    }
}
