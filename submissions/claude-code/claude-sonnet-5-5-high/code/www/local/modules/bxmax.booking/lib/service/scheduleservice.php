<?php

declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\SlotRepository;

/**
 * Недельная сетка слотов мастера для лендинга. В ответе нет данных заявок — только id, время и статус.
 */
final class ScheduleService
{
    public const STATUS_FREE = 'free';
    public const STATUS_TAKEN = 'taken';

    public function __construct(
        private readonly CatalogRepository $catalog,
        private readonly SlotRepository $slots,
    ) {}

    public function getWeek(int $masterId, string $weekStart): Result
    {
        $result = new Result();

        if ($masterId <= 0 || $this->catalog->findMaster($masterId) === null)
        {
            return $result->addError(new Error('Мастер не найден', 'MASTER_NOT_FOUND'));
        }

        $monday = $this->normalizeWeekStart($weekStart);
        $from = DateTime::createFromPhp(\DateTime::createFromImmutable($monday));
        $to = DateTime::createFromPhp(\DateTime::createFromImmutable($monday->modify('+7 days')));
        $now = new DateTime();

        $items = [];
        foreach ($this->slots->findByMasterBetween($masterId, $from, $to) as $row)
        {
            $items[] = [
                'id' => (int)$row['ID'],
                'startsAt' => $row['STARTS_AT']->format('c'),
                'endsAt' => $row['ENDS_AT']->format('c'),
                'status' => $this->isAvailable($row, $now) ? self::STATUS_FREE : self::STATUS_TAKEN,
            ];
        }

        $result->setData(['slots' => $items]);

        return $result;
    }

    /**
     * Свободен = не закрыт администратором, не занят заявкой и ещё не начался.
     */
    private function isAvailable(array $slot, DateTime $now): bool
    {
        return $slot['IS_CLOSED'] !== 'Y'
            && $slot['ENTRY_ID'] === null
            && $slot['STARTS_AT']->getTimestamp() > $now->getTimestamp();
    }

    private function normalizeWeekStart(string $weekStart): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', trim($weekStart));
        if ($date === false || $date->format('Y-m-d') !== trim($weekStart))
        {
            $date = new \DateTimeImmutable('today');
        }

        // всегда приводим к понедельнику недели
        $dayOfWeek = (int)$date->format('N');

        return $date->modify('-' . ($dayOfWeek - 1) . ' days');
    }
}
