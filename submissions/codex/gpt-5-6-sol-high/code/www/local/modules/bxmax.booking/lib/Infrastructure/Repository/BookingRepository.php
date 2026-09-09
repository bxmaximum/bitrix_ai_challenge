<?php

declare(strict_types=1);

namespace Bxmax\Booking\Infrastructure\Repository;

use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Model\BookingEntryTable;

final class BookingRepository
{
    public function create(
        int $slotId,
        int $serviceId,
        string $name,
        string $phone,
        DateTime $consentAt,
    ): AddResult {
        return BookingEntryTable::add([
            'SLOT_ID' => $slotId,
            'SERVICE_ID' => $serviceId,
            'NAME' => $name,
            'PHONE' => $phone,
            'CONSENT_AT' => $consentAt,
        ]);
    }

    public function existsForSlot(int $slotId): bool
    {
        return (bool)BookingEntryTable::query()
            ->setSelect(['ID'])
            ->where('SLOT_ID', $slotId)
            ->setLimit(1)
            ->fetch();
    }

    public function delete(int $id): void
    {
        BookingEntryTable::delete($id);
    }
}
