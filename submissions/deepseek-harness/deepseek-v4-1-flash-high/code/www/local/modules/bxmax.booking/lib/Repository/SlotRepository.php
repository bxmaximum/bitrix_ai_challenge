<?php

namespace Bxmax\Booking\Repository;

use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Entity\EntryTable;
use Bxmax\Booking\Entity\SlotTable;

/**
 * Reads and writes schedule slots. Knows nothing about bookings.
 */
final class SlotRepository
{
	public function findById(int $id): ?array
	{
		if ($id <= 0)
		{
			return null;
		}

		$row = SlotTable::getList([
			'select' => ['ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT', 'CLOSED'],
			'filter' => ['=ID' => $id],
			'limit' => 1,
		])->fetch();

		return $row ?: null;
	}

	/**
	 * @return array<int, array>
	 */
	public function findByMasterAndPeriod(int $masterId, DateTime $from, DateTime $to): array
	{
		if ($masterId <= 0)
		{
			return [];
		}

		$rows = [];
		$result = SlotTable::getList([
			'select' => ['ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT', 'CLOSED'],
			'filter' => [
				'=MASTER_ID' => $masterId,
				'>=STARTS_AT' => $from,
				'<STARTS_AT' => $to,
			],
			'order' => ['STARTS_AT' => 'ASC', 'ID' => 'ASC'],
		]);

		while ($row = $result->fetch())
		{
			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * Returns existing slot start moments of a master inside the period,
	 * keyed by "Y-m-d H:i" so a deploy run stays idempotent.
	 *
	 * @return array<string, int>
	 */
	public function mapExistingStarts(int $masterId, DateTime $from, DateTime $to): array
	{
		$map = [];
		foreach ($this->findByMasterAndPeriod($masterId, $from, $to) as $row)
		{
			/** @var DateTime $startsAt */
			$startsAt = $row['STARTS_AT'];
			$map[$startsAt->format('Y-m-d H:i')] = (int)$row['ID'];
		}

		return $map;
	}

	public function add(int $masterId, DateTime $startsAt, DateTime $endsAt): int
	{
		$result = SlotTable::add([
			'MASTER_ID' => $masterId,
			'STARTS_AT' => $startsAt,
			'ENDS_AT' => $endsAt,
			'CLOSED' => SlotTable::CLOSED_NO,
			'CREATED_AT' => new DateTime(),
		]);

		return (int)$result->getId();
	}

	public function setClosed(int $slotId, bool $closed): void
	{
		SlotTable::update($slotId, [
			'CLOSED' => $closed ? SlotTable::CLOSED_YES : SlotTable::CLOSED_NO,
		]);
	}

	/**
	 * @return array<int, int> slotId => entryId
	 */
	public function mapBookedSlots(array $slotIds): array
	{
		if ($slotIds === [])
		{
			return [];
		}

		$map = [];
		$result = EntryTable::getList([
			'select' => ['ID', 'SLOT_ID'],
			'filter' => ['@SLOT_ID' => $slotIds],
		]);

		while ($row = $result->fetch())
		{
			$map[(int)$row['SLOT_ID']] = (int)$row['ID'];
		}

		return $map;
	}

	/**
	 * All slots of a master from the given moment, ordered by start.
	 *
	 * @return array<int, array>
	 */
	public function findFrom(int $masterId, DateTime $from): array
	{
		$rows = [];
		$result = SlotTable::getList([
			'select' => ['ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT', 'CLOSED'],
			'filter' => [
				'=MASTER_ID' => $masterId,
				'>=STARTS_AT' => $from,
			],
			'order' => ['STARTS_AT' => 'ASC'],
		]);

		while ($row = $result->fetch())
		{
			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * Slots that nothing points to: used by the schedule extension to know
	 * which moments are still missing.
	 */
	public function countByMaster(int $masterId): int
	{
		return (int)SlotTable::getCount(['=MASTER_ID' => $masterId]);
	}
}
