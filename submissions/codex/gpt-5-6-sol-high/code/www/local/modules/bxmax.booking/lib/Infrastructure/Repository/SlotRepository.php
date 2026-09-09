<?php

declare(strict_types=1);

namespace Bxmax\Booking\Infrastructure\Repository;

use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Model\BookingEntryTable;
use Bxmax\Booking\Model\SlotTable;

final class SlotRepository
{
    public function find(int $id): ?array
    {
        $row = SlotTable::query()
            ->setSelect(['ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT', 'IS_CLOSED'])
            ->where('ID', $id)
            ->fetch();

        return $row ?: null;
    }

    public function listForPeriod(int $masterId, DateTime $from, DateTime $to): array
    {
        return SlotTable::query()
            ->setSelect(['ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT', 'IS_CLOSED'])
            ->where('MASTER_ID', $masterId)
            ->where('STARTS_AT', '>=', $from)
            ->where('STARTS_AT', '<', $to)
            ->setOrder(['STARTS_AT' => 'ASC'])
            ->fetchAll();
    }

    public function findBookedSlotIds(array $slotIds): array
    {
        if ($slotIds === [])
        {
            return [];
        }

        $rows = BookingEntryTable::query()
            ->setSelect(['SLOT_ID'])
            ->whereIn('SLOT_ID', $slotIds)
            ->fetchAll();

        return array_fill_keys(array_map(static fn (array $row): int => (int)$row['SLOT_ID'], $rows), true);
    }

    public function add(int $masterId, DateTime $startsAt, DateTime $endsAt): void
    {
        SlotTable::add([
            'MASTER_ID' => $masterId,
            'STARTS_AT' => $startsAt,
            'ENDS_AT' => $endsAt,
            'IS_CLOSED' => 'N',
        ]);
    }

    public function setClosed(int $id, bool $closed): void
    {
        SlotTable::update($id, ['IS_CLOSED' => $closed ? 'Y' : 'N']);
    }
}
