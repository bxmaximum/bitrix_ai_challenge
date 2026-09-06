<?php

declare(strict_types=1);

namespace Bxmax\Booking\Application\Service;

use Bitrix\Main\Type\DateTime as BxDateTime;
use Bxmax\Booking\Domain\Repository\CatalogRepositoryInterface;
use Bxmax\Booking\Domain\Repository\SlotRepositoryInterface;

final class ScheduleGenerator
{
    public function __construct(
        private readonly SlotRepositoryInterface $slots,
        private readonly CatalogRepositoryInterface $catalog,
    ) {
    }

    public function generateForExistingMasters(): int
    {
        $created = 0;
        foreach ($this->catalog->getMasters() as $master) {
            $created += $this->generateForMaster((int)$master['id']);
        }

        return $created;
    }

    public function generateForMaster(int $masterId): int
    {
        if ($masterId <= 0 || $this->slots->existsForMaster($masterId)) {
            return 0;
        }

        $tz = new \DateTimeZone(date_default_timezone_get());
        $day = new \DateTimeImmutable('today', $tz);
        $rows = [];

        for ($d = 0; $d < 14; $d++) {
            $current = $day->modify('+' . $d . ' days');
            for ($hour = 10; $hour < 20; $hour++) {
                $start = $current->setTime($hour, 0, 0);
                $end = $start->modify('+1 hour');
                $rows[] = [
                    'MASTER_ID' => $masterId,
                    'STARTS_AT' => BxDateTime::createFromPhp(\DateTime::createFromInterface($start)),
                    'ENDS_AT' => BxDateTime::createFromPhp(\DateTime::createFromInterface($end)),
                    'IS_CLOSED' => 'N',
                ];
            }
        }

        $this->slots->addMany($rows);

        return count($rows);
    }
}
