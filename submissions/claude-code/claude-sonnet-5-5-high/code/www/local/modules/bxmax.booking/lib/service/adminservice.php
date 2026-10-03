<?php

declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bxmax\Booking\Repository\EntryRepository;
use Bxmax\Booking\Repository\SlotRepository;

/**
 * Операции администратора над заявками и слотами (используются страницами админки).
 */
final class AdminService
{
    public function __construct(
        private readonly SlotRepository $slots,
        private readonly EntryRepository $entries,
    ) {}

    /**
     * Удаление заявки освобождает слот: занятость слота вычисляется по наличию заявки.
     */
    public function deleteEntry(int $entryId): Result
    {
        $result = new Result();
        if (!$this->entries->delete($entryId))
        {
            $result->addError(new Error('Не удалось удалить заявку', 'ENTRY_DELETE_FAILED'));
        }

        return $result;
    }

    public function setSlotClosed(int $slotId, bool $closed): Result
    {
        $result = new Result();
        if (!$this->slots->setClosed($slotId, $closed))
        {
            $result->addError(new Error('Слот не найден', 'SLOT_NOT_FOUND'));
        }

        return $result;
    }
}
