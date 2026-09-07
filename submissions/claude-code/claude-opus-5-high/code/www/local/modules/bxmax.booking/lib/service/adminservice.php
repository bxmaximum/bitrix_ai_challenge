<?php

declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Error;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Model\EntryTable;
use Bxmax\Booking\Model\SlotTable;
use Bxmax\Booking\Repository\EntryRepository;
use Bxmax\Booking\Repository\MasterRepository;
use Bxmax\Booking\Repository\ServiceRepository;
use Bxmax\Booking\Repository\SlotRepository;

/**
 * Прикладная логика административных списков.
 * Страницы админки только рисуют то, что вернул сервис.
 */
final class AdminService
{
	public const SLOT_STATUS_FREE = 'free';
	public const SLOT_STATUS_BOOKED = 'booked';
	public const SLOT_STATUS_CLOSED = 'closed';

	public function __construct(
		private readonly SlotRepository $slots,
		private readonly EntryRepository $entries,
		private readonly MasterRepository $masters,
		private readonly ServiceRepository $services,
	) {
	}

	/**
	 * @return array<int, string> id мастера => имя
	 */
	public function getMasterOptions(): array
	{
		$options = [];
		foreach ($this->masters->getAll() as $id => $master)
		{
			$options[$id] = $master['name'];
		}

		return $options;
	}

	/**
	 * Список заявок с расшифровкой мастера, услуги и времени слота.
	 */
	public function getEntries(array $filter, array $order, int $limit, int $offset): array
	{
		$query = EntryTable::query()
			->setSelect([
				'ID',
				'SLOT_ID',
				'SERVICE_ID',
				'NAME',
				'PHONE',
				'CONSENT_AT',
				'CREATED_AT',
				'STARTS_AT' => 'SLOT.STARTS_AT',
				'ENDS_AT' => 'SLOT.ENDS_AT',
				'MASTER_ID' => 'SLOT.MASTER_ID',
			])
			->setLimit($limit)
			->setOffset($offset);

		$this->applyEntryFilter($query, $filter);
		$query->setOrder($order ?: ['STARTS_AT' => 'DESC']);

		$masters = $this->masters->getAll();
		$services = $this->services->getAll();

		$rows = [];
		foreach ($query->fetchAll() as $row)
		{
			$row['MASTER_NAME'] = $masters[(int)$row['MASTER_ID']]['name'] ?? ('#' . (int)$row['MASTER_ID']);
			$row['SERVICE_NAME'] = $services[(int)$row['SERVICE_ID']]['name'] ?? ('#' . (int)$row['SERVICE_ID']);
			$rows[] = $row;
		}

		return $rows;
	}

	public function countEntries(array $filter): int
	{
		$query = EntryTable::query()
			->registerRuntimeField('CNT', new ExpressionField('CNT', 'COUNT(%s)', 'ID'))
			->addSelect('CNT');
		$this->applyEntryFilter($query, $filter);

		return (int)$query->fetch()['CNT'];
	}

	/**
	 * Удаление заявки освобождает слот: сам слот при этом остаётся в расписании.
	 */
	public function deleteEntry(int $id): Result
	{
		$result = new Result();

		if ($this->entries->findById($id) === null)
		{
			return $result->addError(new Error('Заявка не найдена.'));
		}

		$deleteResult = $this->entries->delete($id);
		if (!$deleteResult->isSuccess())
		{
			$result->addError(new Error(implode('; ', $deleteResult->getErrorMessages())));
		}

		return $result;
	}

	public function getSlots(array $filter, array $order, int $limit, int $offset): array
	{
		$query = SlotTable::query()
			->setSelect([
				'ID',
				'MASTER_ID',
				'STARTS_AT',
				'ENDS_AT',
				'IS_CLOSED',
				'ENTRY_ID' => 'ENTRY.ID',
				'ENTRY_NAME' => 'ENTRY.NAME',
			])
			->setLimit($limit)
			->setOffset($offset);

		$this->applySlotFilter($query, $filter);
		$query->setOrder($order ?: ['STARTS_AT' => 'ASC']);

		$masters = $this->masters->getAll();

		$rows = [];
		foreach ($query->fetchAll() as $row)
		{
			$row['MASTER_NAME'] = $masters[(int)$row['MASTER_ID']]['name'] ?? ('#' . (int)$row['MASTER_ID']);
			$row['STATUS'] = $this->resolveSlotStatus($row);
			$rows[] = $row;
		}

		return $rows;
	}

	public function countSlots(array $filter): int
	{
		$query = SlotTable::query()
			->registerRuntimeField('CNT', new ExpressionField('CNT', 'COUNT(%s)', 'ID'))
			->addSelect('CNT');
		$this->applySlotFilter($query, $filter);

		return (int)$query->fetch()['CNT'];
	}

	public function setSlotClosed(int $id, bool $closed): Result
	{
		$result = new Result();

		$slot = $this->slots->findById($id);
		if ($slot === null)
		{
			return $result->addError(new Error('Слот не найден.'));
		}

		if ($closed && $this->entries->existsForSlot($id))
		{
			return $result->addError(new Error('На слот есть заявка — сначала удалите её.'));
		}

		$updateResult = $this->slots->setClosed($id, $closed);
		if (!$updateResult->isSuccess())
		{
			$result->addError(new Error(implode('; ', $updateResult->getErrorMessages())));
		}

		return $result;
	}

	/**
	 * Приводит фильтр админского грида (ключи вида ">=STARTS_AT") к виду, понятному сервису.
	 */
	public static function normalizeGridFilter(array $rawFilter, string $dateField): array
	{
		$filter = [];

		if (!empty($rawFilter['MASTER_ID']))
		{
			$filter['MASTER_ID'] = (int)$rawFilter['MASTER_ID'];
		}

		if (!empty($rawFilter['PHONE']))
		{
			$filter['PHONE'] = (string)$rawFilter['PHONE'];
		}

		if (!empty($rawFilter['STATUS']))
		{
			$filter['STATUS'] = (string)$rawFilter['STATUS'];
		}

		$map = ['>=' . $dateField => 'DATE_FROM', '<=' . $dateField => 'DATE_TO'];
		foreach ($map as $source => $target)
		{
			if (empty($rawFilter[$source]))
			{
				continue;
			}

			$value = $rawFilter[$source];
			$filter[$target] = $value instanceof \Bitrix\Main\Type\Date
				? $value->format('d.m.Y')
				: (string)$value;
		}

		return $filter;
	}

	private function resolveSlotStatus(array $row): string
	{
		if ((int)($row['ENTRY_ID'] ?? 0) > 0)
		{
			return self::SLOT_STATUS_BOOKED;
		}

		return $row['IS_CLOSED'] === 'Y' ? self::SLOT_STATUS_CLOSED : self::SLOT_STATUS_FREE;
	}

	private function applyEntryFilter(Query $query, array $filter): void
	{
		if (!empty($filter['MASTER_ID']))
		{
			$query->where('SLOT.MASTER_ID', (int)$filter['MASTER_ID']);
		}

		if (!empty($filter['DATE_FROM']))
		{
			$query->where('SLOT.STARTS_AT', '>=', self::dayStart($filter['DATE_FROM']));
		}

		if (!empty($filter['DATE_TO']))
		{
			$query->where('SLOT.STARTS_AT', '<', self::dayEnd($filter['DATE_TO']));
		}

		if (!empty($filter['PHONE']))
		{
			$query->whereLike('PHONE', '%' . (string)$filter['PHONE'] . '%');
		}
	}

	private function applySlotFilter(Query $query, array $filter): void
	{
		if (!empty($filter['MASTER_ID']))
		{
			$query->where('MASTER_ID', (int)$filter['MASTER_ID']);
		}

		if (!empty($filter['DATE_FROM']))
		{
			$query->where('STARTS_AT', '>=', self::dayStart($filter['DATE_FROM']));
		}

		if (!empty($filter['DATE_TO']))
		{
			$query->where('STARTS_AT', '<', self::dayEnd($filter['DATE_TO']));
		}

		$status = (string)($filter['STATUS'] ?? '');
		if ($status === self::SLOT_STATUS_BOOKED)
		{
			$query->whereNotNull('ENTRY.ID');
		}
		elseif ($status === self::SLOT_STATUS_CLOSED)
		{
			$query->where('IS_CLOSED', true)->whereNull('ENTRY.ID');
		}
		elseif ($status === self::SLOT_STATUS_FREE)
		{
			$query->where('IS_CLOSED', false)->whereNull('ENTRY.ID');
		}
	}

	private static function dayStart(string $raw): DateTime
	{
		return DateTime::createFromTimestamp(self::parseDate($raw));
	}

	private static function dayEnd(string $raw): DateTime
	{
		return DateTime::createFromTimestamp(strtotime('+1 day', self::parseDate($raw)));
	}

	private static function parseDate(string $raw): int
	{
		$raw = trim($raw);
		$raw = (string)strtok($raw, ' ');
		$formats = ['d.m.Y', 'Y-m-d'];
		foreach ($formats as $format)
		{
			$parsed = \DateTimeImmutable::createFromFormat('!' . $format, $raw);
			if ($parsed instanceof \DateTimeImmutable)
			{
				return $parsed->getTimestamp();
			}
		}

		return strtotime('today');
	}
}
