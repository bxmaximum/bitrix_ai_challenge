<?php

declare(strict_types=1);

namespace Bxmax\Booking\Repository;

use Bitrix\Main\DB\DuplicateEntryException;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Model\EntryTable;

class EntryRepository
{
	/**
	 * Вставляет заявку. Занятость слота решает уникальный индекс по SLOT_ID:
	 * из двух одновременных вставок база принимает ровно одну.
	 *
	 * @return Result Успех: getId() — ID заявки. Неуспех: ошибки ORM.
	 * @throws SlotOccupiedException Слот уже занят другой заявкой.
	 */
	public function add(int $slotId, int $serviceId, string $name, string $phone, DateTime $consentAt): Result
	{
		try
		{
			return EntryTable::add([
				'SLOT_ID' => $slotId,
				'SERVICE_ID' => $serviceId,
				'NAME' => $name,
				'PHONE' => $phone,
				'CONSENT_AT' => $consentAt,
			]);
		}
		catch (DuplicateEntryException $exception)
		{
			throw new SlotOccupiedException('Slot is already booked', 0, $exception);
		}
	}

	/**
	 * @return array{ID: int, SLOT_ID: int, SERVICE_ID: int, NAME: string, PHONE: string, CONSENT_AT: DateTime}|null
	 */
	public function findById(int $id): ?array
	{
		if ($id <= 0)
		{
			return null;
		}

		$row = EntryTable::getList([
			'select' => ['ID', 'SLOT_ID', 'SERVICE_ID', 'NAME', 'PHONE', 'CONSENT_AT'],
			'filter' => ['=ID' => $id],
			'limit' => 1,
		])->fetch();

		if (!$row)
		{
			return null;
		}

		$row['ID'] = (int)$row['ID'];
		$row['SLOT_ID'] = (int)$row['SLOT_ID'];
		$row['SERVICE_ID'] = (int)$row['SERVICE_ID'];

		return $row;
	}

	public function existsForSlot(int $slotId): bool
	{
		return EntryTable::getCount(['=SLOT_ID' => $slotId]) > 0;
	}

	public function delete(int $id): Result
	{
		return EntryTable::delete($id);
	}

	/**
	 * Выборка для списка админки. Поля сортировки и фильтра задаёт вызывающая страница
	 * из собственного белого списка.
	 */
	public function getAdminList(array $filter, array $order, int $offset, int $limit): QueryResult
	{
		return EntryTable::getList([
			'select' => [
				'ID', 'SLOT_ID', 'SERVICE_ID', 'NAME', 'PHONE', 'CONSENT_AT',
				'MASTER_ID' => 'SLOT.MASTER_ID',
				'STARTS_AT' => 'SLOT.STARTS_AT',
				'ENDS_AT' => 'SLOT.ENDS_AT',
			],
			'filter' => $filter,
			'order' => $order,
			'offset' => $offset,
			'limit' => $limit,
			'count_total' => true,
		]);
	}
}
