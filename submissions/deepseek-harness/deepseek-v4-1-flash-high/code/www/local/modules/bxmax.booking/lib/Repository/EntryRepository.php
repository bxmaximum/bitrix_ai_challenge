<?php

namespace Bxmax\Booking\Repository;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Entity\EntryTable;

/**
 * Bookings storage. The single source of truth for "is this slot occupied".
 */
final class EntryRepository
{
	public function findById(int $id): ?array
	{
		$row = EntryTable::getList([
			'select' => ['ID', 'SLOT_ID', 'MASTER_ID', 'SERVICE_ID', 'NAME', 'PHONE', 'CONSENT_AT', 'CREATED_AT'],
			'filter' => ['=ID' => $id],
			'limit' => 1,
		])->fetch();

		return $row ?: null;
	}

	public function findBySlotId(int $slotId): ?array
	{
		$row = EntryTable::getList([
			'select' => ['ID', 'SLOT_ID', 'MASTER_ID', 'SERVICE_ID', 'NAME', 'PHONE', 'CONSENT_AT', 'CREATED_AT'],
			'filter' => ['=SLOT_ID' => $slotId],
			'limit' => 1,
		])->fetch();

		return $row ?: null;
	}

	/**
	 * Inserts a booking. The unique index on SLOT_ID is what actually keeps
	 * "one slot - one booking": a concurrent insert fails here, not earlier.
	 *
	 * @throws EntryAlreadyExistsException
	 */
	public function add(array $fields): int
	{
		$fields['CREATED_AT'] = new DateTime();

		try
		{
			$result = EntryTable::add($fields);
		}
		catch (SqlQueryException $exception)
		{
			// The row is not inserted because the slot is already occupied
			// (duplicate key on the unique index) - or because of a real DB error.
			if ($this->findBySlotId((int)$fields['SLOT_ID']) !== null)
			{
				throw new EntryAlreadyExistsException($exception->getMessage(), 0, $exception);
			}

			throw $exception;
		}

		return (int)$result->getId();
	}

	public function delete(int $id): void
	{
		EntryTable::delete($id);
	}

	public function countAll(): int
	{
		return (int)EntryTable::getCount();
	}
}
