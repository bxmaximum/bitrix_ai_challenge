<?php

declare(strict_types=1);

namespace Bxmax\Booking\Application\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Infrastructure\Repository\ContentRepository;
use Bxmax\Booking\Infrastructure\Repository\SlotRepository;

final class SlotService
{
    public function __construct(
        private readonly SlotRepository $slots,
        private readonly ContentRepository $content,
    ) {}

    public function list(int $masterId, string $weekStart): Result
    {
        $result = new Result();
        if (!$this->content->masterExists($masterId))
        {
            return $result->addError(new Error('Мастер не найден.', 'MASTER_NOT_FOUND'));
        }

        $fromPhp = \DateTimeImmutable::createFromFormat('!Y-m-d', $weekStart);
        if (!$fromPhp)
        {
            $fromPhp = new \DateTimeImmutable('monday this week');
        }
        $toPhp = $fromPhp->modify('+7 days');
        $rows = $this->slots->listForPeriod(
            $masterId,
            DateTime::createFromPhp(\DateTime::createFromImmutable($fromPhp)),
            DateTime::createFromPhp(\DateTime::createFromImmutable($toPhp)),
        );
        $booked = $this->slots->findBookedSlotIds(array_column($rows, 'ID'));

        $items = array_map(static function (array $row) use ($booked): array {
            $id = (int)$row['ID'];
            return [
                'id' => $id,
                'startsAt' => $row['STARTS_AT']->format(DATE_ATOM),
                'endsAt' => $row['ENDS_AT']->format(DATE_ATOM),
                'status' => ($row['IS_CLOSED'] === 'Y' || isset($booked[$id])) ? 'taken' : 'free',
            ];
        }, $rows);

        return $result->setData(['slots' => $items]);
    }
}
