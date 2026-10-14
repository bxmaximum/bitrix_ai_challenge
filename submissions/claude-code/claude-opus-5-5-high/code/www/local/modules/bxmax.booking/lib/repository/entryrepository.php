<?php

declare(strict_types=1);

namespace Bxmax\Booking\Repository;

use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Model\EntryTable;

/**
 * Доступ к таблице заявок bxmax_booking_entry.
 */
class EntryRepository
{
    /**
     * Добавляет заявку. При занятом слоте БД бросает DuplicateEntryException
     * (уникальный индекс по SLOT_ID) — его обрабатывает сервис.
     *
     * @throws \Bitrix\Main\DB\DuplicateEntryException
     * @throws SystemException
     */
    public function add(int $slotId, int $serviceId, string $name, string $phone, DateTime $consentAt): int
    {
        $result = EntryTable::add([
            'SLOT_ID' => $slotId,
            'SERVICE_ID' => $serviceId,
            'NAME' => $name,
            'PHONE' => $phone,
            'CONSENT_AT' => $consentAt,
            'CREATED_AT' => new DateTime(),
        ]);

        if (!$result->isSuccess())
        {
            throw new SystemException(implode('; ', $result->getErrorMessages()));
        }

        return (int)$result->getId();
    }

    public function delete(int $id): bool
    {
        return EntryTable::delete($id)->isSuccess();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findWithSlot(int $id): ?array
    {
        $row = EntryTable::query()
            ->setSelect([
                'ID', 'SLOT_ID', 'SERVICE_ID', 'NAME', 'PHONE', 'CONSENT_AT', 'CREATED_AT',
                'MASTER_ID' => 'SLOT.MASTER_ID',
                'STARTS_AT' => 'SLOT.STARTS_AT',
                'ENDS_AT' => 'SLOT.ENDS_AT',
            ])
            ->where('ID', $id)
            ->fetch();

        return $row ?: null;
    }

    /**
     * Запрос для административного списка заявок.
     */
    public function createListQuery(): Query
    {
        return EntryTable::query()
            ->setSelect([
                'ID', 'SLOT_ID', 'SERVICE_ID', 'NAME', 'PHONE', 'CONSENT_AT', 'CREATED_AT',
                'MASTER_ID' => 'SLOT.MASTER_ID',
                'STARTS_AT' => 'SLOT.STARTS_AT',
            ]);
    }
}
