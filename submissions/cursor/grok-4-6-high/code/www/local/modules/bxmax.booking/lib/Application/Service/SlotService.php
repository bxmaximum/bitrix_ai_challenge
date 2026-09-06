<?php

declare(strict_types=1);

namespace Bxmax\Booking\Application\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bxmax\Booking\Domain\Repository\CatalogRepositoryInterface;
use Bxmax\Booking\Domain\Repository\SlotRepositoryInterface;

final class SlotService
{
    public function __construct(
        private readonly SlotRepositoryInterface $slots,
        private readonly CatalogRepositoryInterface $catalog,
    ) {
    }

    public function listForWeek(int $masterId, string $weekStart): Result
    {
        $result = new Result();

        if (!$this->catalog->masterExists($masterId)) {
            $result->addError(new Error('Master not found', 'MASTER_NOT_FOUND'));

            return $result;
        }

        $monday = $this->parseMonday($weekStart);
        $sundayExclusive = $monday->modify('+7 days');

        $result->setData([
            'slots' => $this->slots->listForMasterWeek($masterId, $monday, $sundayExclusive),
        ]);

        return $result;
    }

    private function parseMonday(string $weekStart): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $weekStart, new \DateTimeZone(date_default_timezone_get()));
        if ($date === false) {
            $date = new \DateTimeImmutable('monday this week');
        }

        $date = $date->setTime(0, 0, 0);
        $day = (int)$date->format('N');
        if ($day !== 1) {
            $date = $date->modify('-' . ($day - 1) . ' days');
        }

        return $date;
    }
}
