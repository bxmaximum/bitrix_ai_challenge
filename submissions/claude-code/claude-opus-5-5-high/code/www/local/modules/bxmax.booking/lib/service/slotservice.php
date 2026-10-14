<?php

declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\ErrorCode;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\SlotRepository;

/**
 * Чтение расписания для витрины и управление доступностью слотов из админки.
 */
class SlotService
{
    public const STATUS_FREE = 'free';
    public const STATUS_TAKEN = 'taken';

    public function __construct(
        private readonly SlotRepository $slots,
        private readonly CatalogRepository $catalog,
    )
    {
    }

    /**
     * Слоты мастера на неделю, начиная с понедельника $weekStart (YYYY-MM-DD).
     * В ответе нет данных заявок: только id, время и статус.
     *
     * @return Result data: ['slots' => list<array{id:int, startsAt:string, endsAt:string, status:string}>]
     */
    public function getWeek(int $masterId, string $weekStart, ?DateTime $now = null): Result
    {
        $result = new Result();

        if ($this->catalog->getMaster($masterId) === null)
        {
            return $result->addError(new Error(
                Loc::getMessage('BXMAX_BOOKING_ERROR_MASTER_NOT_FOUND'),
                ErrorCode::MASTER_NOT_FOUND
            ));
        }

        $from = $this->parseMonday($weekStart);
        if ($from === null)
        {
            return $result->addError(new Error(
                Loc::getMessage('BXMAX_BOOKING_ERROR_WEEK_START'),
                ErrorCode::VALIDATION,
                ['field' => 'weekStart']
            ));
        }
        $to = (clone $from)->add('P7D');
        $now ??= new DateTime();

        $slots = [];
        foreach ($this->slots->findByMasterAndPeriod($masterId, $from, $to) as $slot)
        {
            $slots[] = [
                'id' => $slot['ID'],
                'startsAt' => $slot['STARTS_AT']->format(DATE_ATOM),
                'endsAt' => $slot['ENDS_AT']->format(DATE_ATOM),
                'status' => $this->isBookable($slot, $now) ? self::STATUS_FREE : self::STATUS_TAKEN,
            ];
        }

        return $result->setData(['slots' => $slots]);
    }

    /**
     * Слот доступен для записи: не закрыт администратором, не занят и ещё не начался.
     *
     * @param array{IS_CLOSED:bool, STARTS_AT:DateTime, ENTRY_ID?:?int} $slot
     */
    public function isBookable(array $slot, ?DateTime $now = null): bool
    {
        $now ??= new DateTime();

        return !$slot['IS_CLOSED']
            && empty($slot['ENTRY_ID'])
            && $slot['STARTS_AT']->getTimestamp() > $now->getTimestamp();
    }

    /**
     * @param int[] $ids
     */
    public function close(array $ids): Result
    {
        $this->slots->setClosed($ids, true);

        return new Result();
    }

    /**
     * @param int[] $ids
     */
    public function open(array $ids): Result
    {
        $this->slots->setClosed($ids, false);

        return new Result();
    }

    /**
     * Понедельник текущей недели в формате YYYY-MM-DD.
     */
    public function getCurrentWeekStart(?DateTime $now = null): string
    {
        $date = (clone ($now ?? new DateTime()))->setTime(0, 0);
        $shift = (int)$date->format('N') - 1;
        if ($shift > 0)
        {
            $date->add(sprintf('-P%dD', $shift));
        }

        return $date->format('Y-m-d');
    }

    private function parseMonday(string $value): ?DateTime
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1]))
        {
            return null;
        }

        $date = DateTime::createFromPhp(new \DateTime($value . ' 00:00:00'));
        if ((int)$date->format('N') !== 1)
        {
            return null;
        }

        return $date;
    }
}
