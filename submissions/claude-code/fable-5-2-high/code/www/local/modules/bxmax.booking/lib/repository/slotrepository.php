<?php

declare(strict_types=1);

namespace Bxmax\Booking\Repository;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Model\SlotTable;

class SlotRepository
{
	/**
	 * @return array{ID: int, MASTER_ID: int, STARTS_AT: DateTime, ENDS_AT: DateTime, CLOSED: bool, ENTRY_ID: int|null}|null
	 */
	public function findById(int $id): ?array
	{
		if ($id <= 0)
		{
			return null;
		}

		$row = SlotTable::getList([
			'select' => ['ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT', 'CLOSED', 'ENTRY_ID' => 'ENTRY.ID'],
			'filter' => ['=ID' => $id],
			'limit' => 1,
		])->fetch();

		return $row ? $this->normalize($row) : null;
	}

	/**
	 * Блокирует строку слота до конца транзакции: запись и закрытие слота идут строго по очереди.
	 * В ORM нет FOR UPDATE, поэтому прямой запрос; значение приведено к int.
	 *
	 * @return array{ID: int, MASTER_ID: int, STARTS_AT: DateTime, ENDS_AT: DateTime, CLOSED: bool}|null
	 */
	public function lockById(int $id): ?array
	{
		$connection = Application::getConnection(SlotTable::getConnectionName());
		$helper = $connection->getSqlHelper();

		$row = $connection->query(sprintf(
			'SELECT ID, MASTER_ID, STARTS_AT, ENDS_AT, CLOSED FROM %s WHERE ID = %d FOR UPDATE',
			$helper->quote(SlotTable::getTableName()),
			$id
		))->fetch();

		if (!$row)
		{
			return null;
		}

		return $this->normalize($row);
	}

	/**
	 * Слоты мастера в полуинтервале [$from, $to) с признаком занятости заявкой.
	 *
	 * @return list<array{ID: int, MASTER_ID: int, STARTS_AT: DateTime, ENDS_AT: DateTime, CLOSED: bool, ENTRY_ID: int|null}>
	 */
	public function findByMasterAndPeriod(int $masterId, DateTime $from, DateTime $to): array
	{
		$rows = SlotTable::getList([
			'select' => ['ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT', 'CLOSED', 'ENTRY_ID' => 'ENTRY.ID'],
			'filter' => [
				'=MASTER_ID' => $masterId,
				'>=STARTS_AT' => $from,
				'<STARTS_AT' => $to,
			],
			'order' => ['STARTS_AT' => 'ASC'],
		]);

		$slots = [];
		while ($row = $rows->fetch())
		{
			$slots[] = $this->normalize($row);
		}

		return $slots;
	}

	/**
	 * Метки времени начала уже существующих слотов мастера в полуинтервале [$from, $to).
	 *
	 * @return array<int, true>
	 */
	public function getExistingStarts(int $masterId, DateTime $from, DateTime $to): array
	{
		$rows = SlotTable::getList([
			'select' => ['STARTS_AT'],
			'filter' => [
				'=MASTER_ID' => $masterId,
				'>=STARTS_AT' => $from,
				'<STARTS_AT' => $to,
			],
		]);

		$starts = [];
		while ($row = $rows->fetch())
		{
			$starts[$row['STARTS_AT']->getTimestamp()] = true;
		}

		return $starts;
	}

	/**
	 * @param list<array{MASTER_ID: int, STARTS_AT: DateTime, ENDS_AT: DateTime}> $rows
	 */
	public function addMany(array $rows): Result
	{
		return SlotTable::addMulti($rows, true);
	}

	public function setClosed(int $id, bool $closed): Result
	{
		return SlotTable::update($id, ['CLOSED' => $closed]);
	}

	/**
	 * Выборка для списка админки. Поля сортировки и фильтра задаёт вызывающая страница
	 * из собственного белого списка.
	 */
	public function getAdminList(array $filter, array $order, int $offset, int $limit): QueryResult
	{
		return SlotTable::getList([
			'select' => [
				'ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT', 'CLOSED',
				'ENTRY_ID' => 'ENTRY.ID',
				'ENTRY_NAME' => 'ENTRY.NAME',
			],
			'filter' => $filter,
			'order' => $order,
			'offset' => $offset,
			'limit' => $limit,
			'count_total' => true,
		]);
	}

	private function normalize(array $row): array
	{
		$row['ID'] = (int)$row['ID'];
		$row['MASTER_ID'] = (int)$row['MASTER_ID'];
		// Выборка массивом отдаёт значение колонки как есть ('Y' / 'N'), объектный API — bool.
		$row['CLOSED'] = $row['CLOSED'] === true || $row['CLOSED'] === 'Y';
		if (array_key_exists('ENTRY_ID', $row))
		{
			$row['ENTRY_ID'] = $row['ENTRY_ID'] !== null ? (int)$row['ENTRY_ID'] : null;
		}

		return $row;
	}
}
