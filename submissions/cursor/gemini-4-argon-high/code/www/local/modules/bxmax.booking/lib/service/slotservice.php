<?php declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\SlotRepository;

final class SlotService
{
    public function __construct(
        private readonly SlotRepository $slotRepository,
        private readonly CatalogRepository $catalogRepository
    ) {
    }

    public function getMasterWeekSlots(int $masterId, ?string $weekStartRaw): Result
    {
        $result = new Result();

        if ($masterId <= 0 || $this->catalogRepository->findActiveMasterById($masterId) === null)
        {
            return $result->addError(new Error('Мастер не найден', 'MASTER_NOT_FOUND'));
        }

        $weekStartStr = trim((string)$weekStartRaw);
        if ($weekStartStr === '')
        {
            $mondayPhp = new \DateTimeImmutable('monday this week 00:00:00');
        }
        else
        {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStartStr))
            {
                return $result->addError(new Error('Некорректный формат даты начала недели (ожидается YYYY-MM-DD)', 'VALIDATION'));
            }

            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $weekStartStr);
            if ($parsed === false || $parsed->format('Y-m-d') !== $weekStartStr)
            {
                return $result->addError(new Error('Некорректная дата начала недели', 'VALIDATION'));
            }

            $mondayPhp = $parsed->setTime(0, 0, 0);
        }

        $weekEndPhp = $mondayPhp->modify('+7 days');

        $fromBitrix = DateTime::createFromPhp(\DateTime::createFromImmutable($mondayPhp));
        $toBitrix = DateTime::createFromPhp(\DateTime::createFromImmutable($weekEndPhp));

        $rows = $this->slotRepository->findByMasterAndRange($masterId, $fromBitrix, $toBitrix);
        $slots = [];

        foreach ($rows as $row)
        {
            /** @var DateTime $startsAt */
            $startsAt = $row['STARTS_AT'];
            /** @var DateTime $endsAt */
            $endsAt = $row['ENDS_AT'];

            $isTaken = ((string)$row['IS_CLOSED'] === 'Y') || ((int)($row['ENTRY_ID'] ?? 0) > 0);

            $slots[] = [
                'id' => (int)$row['ID'],
                'startsAt' => $startsAt->format(\DATE_ATOM),
                'endsAt' => $endsAt->format(\DATE_ATOM),
                'status' => $isTaken ? 'taken' : 'free',
            ];
        }

        return $result->setData([
            'slots' => $slots,
        ]);
    }

    public function closeSlot(int $slotId): Result
    {
        return $this->slotRepository->setClosed($slotId, true);
    }

    public function openSlot(int $slotId): Result
    {
        return $this->slotRepository->setClosed($slotId, false);
    }

    /**
     * Генерирует слоты каждому активному мастеру на ближайшие $days дней
     * (включая также понедельник текущей недели, чтобы текущая неделя в виджете была полной),
     * ежедневно с 10:00 до 20:00 с шагом 60 минут.
     */
    public function generateScheduleForAllMasters(int $days = 14): Result
    {
        $result = new Result();
        $masters = $this->catalogRepository->listActiveMasters();
        $created = 0;

        $today = new \DateTimeImmutable('today 00:00:00');
        $startDay = $today;
        $endDay = $today->modify('+' . max(1, $days) . ' days');

        foreach ($masters as $master)
        {
            $masterId = (int)$master['id'];
            $cursor = $startDay;
            while ($cursor < $endDay)
            {
                for ($hour = 10; $hour < 20; $hour++)
                {
                    $slotStartPhp = $cursor->setTime($hour, 0, 0);
                    $slotEndPhp = $cursor->setTime($hour + 1, 0, 0);

                    $startsAt = DateTime::createFromPhp(\DateTime::createFromImmutable($slotStartPhp));
                    $endsAt = DateTime::createFromPhp(\DateTime::createFromImmutable($slotEndPhp));

                    if ($this->slotRepository->ensureSlot($masterId, $startsAt, $endsAt))
                    {
                        $created++;
                    }
                }
                $cursor = $cursor->modify('+1 day');
            }
        }

        return $result->setData(['created' => $created]);
    }
}
