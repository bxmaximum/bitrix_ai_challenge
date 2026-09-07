<?php

declare(strict_types=1);

namespace Bxmax\Booking\Repository;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Dto\SlotView;
use Bxmax\Booking\Model\SlotTable;

/**
 * Доступ к слотам расписания.
 */
final class SlotRepository
{
	/**
	 * Слоты одного мастера за период вместе с признаком занятости.
	 *
	 * Слот считается занятым, если он закрыт администратором либо на него уже есть заявка.
	 * Наружу не выходит ничего, кроме id, времени и статуса.
	 *
	 * @return SlotView[]
	 */
	public function listForMasterInRange(int $masterId, DateTime $from, DateTime $to): array
	{
		$rows = SlotTable::query()
			->setSelect(['ID', 'STARTS_AT', 'ENDS_AT', 'IS_CLOSED', 'ENTRY_ID' => 'ENTRY.ID'])
			->where('MASTER_ID', $masterId)
			->where('STARTS_AT', '>=', $from)
			->where('STARTS_AT', '<', $to)
			->setOrder(['STARTS_AT' => 'ASC'])
			->fetchAll();

		$result = [];
		foreach ($rows as $row)
		{
			$taken = $row['IS_CLOSED'] === 'Y' || (int)($row['ENTRY_ID'] ?? 0) > 0;
			$result[] = new SlotView(
				(int)$row['ID'],
				$row['STARTS_AT'],
				$row['ENDS_AT'],
				$taken ? SlotView::STATUS_TAKEN : SlotView::STATUS_FREE
			);
		}

		return $result;
	}

	/**
	 * @return array{ID:int,MASTER_ID:int,STARTS_AT:DateTime,ENDS_AT:DateTime,IS_CLOSED:string}|null
	 */
	public function findById(int $id): ?array
	{
		$row = SlotTable::query()
			->setSelect(['ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT', 'IS_CLOSED'])
			->where('ID', $id)
			->fetch();

		return $row ?: null;
	}

	public function isClosed(int $id): bool
	{
		$row = $this->findById($id);

		return $row !== null && $row['IS_CLOSED'] === 'Y';
	}

	public function setClosed(int $id, bool $closed): \Bitrix\Main\ORM\Data\UpdateResult
	{
		return SlotTable::update($id, ['IS_CLOSED' => $closed]);
	}

	/**
	 * Массовая вставка слотов. Дубли по (MASTER_ID, STARTS_AT) отсекает уникальный индекс,
	 * поэтому повторный запуск генератора расписания безопасен.
	 *
	 * @param array<int, array{MASTER_ID:int,STARTS_AT:string,ENDS_AT:string}> $rows
	 */
	public function insertIgnoreBatch(array $rows): int
	{
		if ($rows === [])
		{
			return 0;
		}

		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$table = $helper->quote(SlotTable::getTableName());
		$now = (new DateTime())->format('Y-m-d H:i:s');

		$values = [];
		foreach ($rows as $row)
		{
			$values[] = sprintf(
				'(%d, %s, %s, %s, %s)',
				(int)$row['MASTER_ID'],
				$helper->convertToDbString($row['STARTS_AT']),
				$helper->convertToDbString($row['ENDS_AT']),
				$helper->convertToDbString('N'),
				$helper->convertToDbString($now)
			);
		}

		$sql = 'INSERT IGNORE INTO ' . $table
			. ' (' . $helper->quote('MASTER_ID') . ', ' . $helper->quote('STARTS_AT') . ', '
			. $helper->quote('ENDS_AT') . ', ' . $helper->quote('IS_CLOSED') . ', '
			. $helper->quote('CREATED_AT') . ') VALUES ' . implode(', ', $values);

		$connection->queryExecute($sql);

		return $connection->getAffectedRowsCount();
	}

	public function countForMaster(int $masterId): int
	{
		return (int)SlotTable::query()
			->registerRuntimeField('CNT', new ExpressionField('CNT', 'COUNT(%s)', 'ID'))
			->addSelect('CNT')
			->where('MASTER_ID', $masterId)
			->fetch()['CNT'];
	}
}
