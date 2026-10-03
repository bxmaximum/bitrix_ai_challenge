<?php

declare(strict_types=1);

namespace Bxmax\Booking\Repository;

use Bitrix\Main\DB\DuplicateEntryException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\ORM\Query\Query;
use Bxmax\Booking\Model\EntryTable;

final class EntryRepository
{
    /**
     * @return int|null id заявки; null, если слот уже занят (сработал уникальный индекс по SLOT_ID)
     */
    public function add(int $slotId, int $serviceId, string $name, string $phone, DateTime $consentAt): ?int
    {
        try
        {
            $result = EntryTable::add([
                'SLOT_ID' => $slotId,
                'SERVICE_ID' => $serviceId,
                'NAME' => $name,
                'PHONE' => $phone,
                'CONSENT_AT' => $consentAt,
                'CREATED_AT' => new DateTime(),
            ]);
        }
        catch (DuplicateEntryException)
        {
            return null;
        }

        return $result->isSuccess() ? (int)$result->getId() : null;
    }

    public function delete(int $id): bool
    {
        return EntryTable::delete($id)->isSuccess();
    }

    /**
     * Запрос для административного списка заявок (вместе со слотом).
     */
    public function listQuery(): Query
    {
        return EntryTable::query()->setSelect([
            'ID', 'SLOT_ID', 'SERVICE_ID', 'NAME', 'PHONE', 'CONSENT_AT', 'CREATED_AT',
            'MASTER_ID' => 'SLOT.MASTER_ID',
            'STARTS_AT' => 'SLOT.STARTS_AT',
        ]);
    }
}
