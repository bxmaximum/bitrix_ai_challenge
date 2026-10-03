<?php

declare(strict_types=1);

namespace Bxmax\Booking\Repository;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Model\SlotTable;

final class SlotRepository
{
    /**
     * @return array{ID: int, MASTER_ID: int, STARTS_AT: DateTime, ENDS_AT: DateTime, IS_CLOSED: string, ENTRY_ID: ?int}|null
     */
    public function find(int $id): ?array
    {
        $row = $this->listQuery()->where('ID', $id)->fetch();

        return $row ?: null;
    }

    /**
     * Блокирует строку слота до конца транзакции (SELECT ... FOR UPDATE) и возвращает её.
     * Вызывается только внутри транзакции.
     *
     * @return array{ID: int, IS_CLOSED: string, STARTS_AT: DateTime}|null
     */
    public function findForUpdate(int $id): ?array
    {
        $connection = Application::getConnection();
        $sql = 'SELECT ID, MASTER_ID, STARTS_AT, IS_CLOSED FROM ' . $connection->getSqlHelper()->quote(SlotTable::getTableName())
            . ' WHERE ID = ' . $id . ' FOR UPDATE';
        $row = $connection->query($sql)->fetch();
        if (!$row)
        {
            return null;
        }

        return [
            'ID' => (int)$row['ID'],
            'MASTER_ID' => (int)$row['MASTER_ID'],
            'IS_CLOSED' => (string)$row['IS_CLOSED'],
            'STARTS_AT' => $row['STARTS_AT'] instanceof DateTime ? $row['STARTS_AT'] : new DateTime((string)$row['STARTS_AT'], 'Y-m-d H:i:s'),
        ];
    }

    /**
     * Слоты мастера в интервале [from, to) вместе с признаком занятости.
     *
     * @return list<array{ID: int, STARTS_AT: DateTime, ENDS_AT: DateTime, IS_CLOSED: string, ENTRY_ID: ?int}>
     */
    public function findByMasterBetween(int $masterId, DateTime $from, DateTime $to): array
    {
        return $this->listQuery()
            ->where('MASTER_ID', $masterId)
            ->where('STARTS_AT', '>=', $from)
            ->where('STARTS_AT', '<', $to)
            ->setOrder(['STARTS_AT' => 'ASC'])
            ->fetchAll();
    }

    public function setClosed(int $id, bool $closed): bool
    {
        if (!$this->find($id))
        {
            return false;
        }

        return SlotTable::update($id, ['IS_CLOSED' => $closed ? 'Y' : 'N'])->isSuccess();
    }

    public function exists(int $masterId, DateTime $startsAt): bool
    {
        return (bool)SlotTable::query()
            ->setSelect(['ID'])
            ->where('MASTER_ID', $masterId)
            ->where('STARTS_AT', $startsAt)
            ->setLimit(1)
            ->fetch();
    }

    /**
     * Идемпотентное создание слотов (INSERT IGNORE по уникальному ключу мастер+время).
     *
     * @param list<array{MASTER_ID: int, STARTS_AT: DateTime, ENDS_AT: DateTime}> $rows
     */
    public function insertIgnore(array $rows): int
    {
        if ($rows === [])
        {
            return 0;
        }

        $connection = Application::getConnection();
        $helper = $connection->getSqlHelper();
        $inserted = 0;

        foreach (array_chunk($rows, 200) as $chunk)
        {
            $values = [];
            foreach ($chunk as $row)
            {
                $values[] = '(' . (int)$row['MASTER_ID'] . ', '
                    . $helper->convertToDbDateTime($row['STARTS_AT']) . ', '
                    . $helper->convertToDbDateTime($row['ENDS_AT']) . ", 'N')";
            }
            $connection->queryExecute(
                'INSERT IGNORE INTO ' . $helper->quote(SlotTable::getTableName())
                . ' (MASTER_ID, STARTS_AT, ENDS_AT, IS_CLOSED) VALUES ' . implode(', ', $values)
            );
            $inserted += $connection->getAffectedRowsCount();
        }
        SlotTable::cleanCache();

        return $inserted;
    }

    public function listQuery(): Query
    {
        return SlotTable::query()
            ->setSelect(['ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT', 'IS_CLOSED', 'ENTRY_ID' => 'ENTRY.ID']);
    }
}
