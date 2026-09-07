<?php

declare(strict_types=1);

namespace Bxmax\Booking\Repository;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Model\EntryTable;

/**
 * Доступ к заявкам.
 */
final class EntryRepository
{
	public function existsForSlot(int $slotId): bool
	{
		return (bool)EntryTable::query()
			->setSelect(['ID'])
			->where('SLOT_ID', $slotId)
			->setLimit(1)
			->fetch();
	}

	/**
	 * Пытается занять слот.
	 *
	 * Единственность записи на слот обеспечивает уникальный индекс UX_BXMAX_BOOKING_ENTRY_SLOT:
	 * при гонке успешной окажется ровно одна вставка, остальные получат SlotTakenException.
	 *
	 * @throws SlotTakenException
	 */
	public function create(
		int $slotId,
		int $serviceId,
		string $name,
		string $phone,
		DateTime $consentAt
	): int {
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
		catch (SqlQueryException $exception)
		{
			if ($this->existsForSlot($slotId))
			{
				throw new SlotTakenException('Slot is already booked.', 0, $exception);
			}

			throw $exception;
		}

		if (!$result->isSuccess())
		{
			throw new \Bitrix\Main\SystemException(implode('; ', $result->getErrorMessages()));
		}

		return (int)$result->getId();
	}

	public function delete(int $id): \Bitrix\Main\ORM\Data\DeleteResult
	{
		return EntryTable::delete($id);
	}

	public function findById(int $id): ?array
	{
		$row = EntryTable::query()
			->setSelect(['ID', 'SLOT_ID', 'SERVICE_ID', 'NAME', 'PHONE', 'CONSENT_AT', 'CREATED_AT'])
			->where('ID', $id)
			->fetch();

		return $row ?: null;
	}
}
