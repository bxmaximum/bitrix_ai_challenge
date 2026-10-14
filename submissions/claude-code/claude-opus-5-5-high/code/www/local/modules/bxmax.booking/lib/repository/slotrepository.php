<?php

declare(strict_types=1);

namespace Bxmax\Booking\Repository;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Model\SlotTable;

/**
 * Доступ к таблице слотов bxmax_booking_slot.
 */
class SlotRepository
{
    /**
     * Слоты мастера в полуинтервале [$from, $to) с признаком занятости.
     *
     * @return array<int, array{ID:int, STARTS_AT:DateTime, ENDS_AT:DateTime, IS_CLOSED:bool, ENTRY_ID:?int}>
     */
    public function findByMasterAndPeriod(int $masterId, DateTime $from, DateTime $to): array
    {
        $rows = SlotTable::query()
            ->setSelect(['ID', 'STARTS_AT', 'ENDS_AT', 'IS_CLOSED', 'ENTRY_ID' => 'ENTRY.ID'])
            ->where('MASTER_ID', $masterId)
            ->where('STARTS_AT', '>=', $from)
            ->where('STARTS_AT', '<', $to)
            ->setOrder(['STARTS_AT' => 'ASC'])
            ->fetchAll();

        return array_map(static fn (array $row): array => [
            'ID' => (int)$row['ID'],
            'STARTS_AT' => $row['STARTS_AT'],
            'ENDS_AT' => $row['ENDS_AT'],
            'IS_CLOSED' => self::toBool($row['IS_CLOSED']),
            'ENTRY_ID' => $row['ENTRY_ID'] !== null ? (int)$row['ENTRY_ID'] : null,
        ], $rows);
    }

    /**
     * @return array{ID:int, MASTER_ID:int, STARTS_AT:DateTime, ENDS_AT:DateTime, IS_CLOSED:bool}|null
     */
    public function findById(int $id): ?array
    {
        $row = SlotTable::getByPrimary($id, [
            'select' => ['ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT', 'IS_CLOSED'],
        ])->fetch();

        return $row ? $this->normalize($row) : null;
    }

    /**
     * Читает слот с блокировкой строки (SELECT ... FOR UPDATE) внутри открытой транзакции.
     * ORM не умеет FOR UPDATE, поэтому запрос собран через SqlHelper.
     *
     * @return array{ID:int, MASTER_ID:int, STARTS_AT:DateTime, ENDS_AT:DateTime, IS_CLOSED:bool}|null
     */
    public function lockById(int $id): ?array
    {
        $connection = Application::getConnection();
        $helper = $connection->getSqlHelper();

        $row = $connection->query(sprintf(
            'SELECT ID, MASTER_ID, STARTS_AT, ENDS_AT, IS_CLOSED FROM %s WHERE ID = %d FOR UPDATE',
            $helper->quote(SlotTable::getTableName()),
            $id
        ))->fetch();

        if (!$row)
        {
            return null;
        }

        return $this->normalize([
            'ID' => $row['ID'],
            'MASTER_ID' => $row['MASTER_ID'],
            'STARTS_AT' => $row['STARTS_AT'] instanceof DateTime ? $row['STARTS_AT'] : new DateTime((string)$row['STARTS_AT'], 'Y-m-d H:i:s'),
            'ENDS_AT' => $row['ENDS_AT'] instanceof DateTime ? $row['ENDS_AT'] : new DateTime((string)$row['ENDS_AT'], 'Y-m-d H:i:s'),
            'IS_CLOSED' => $row['IS_CLOSED'] === 'Y',
        ]);
    }

    /**
     * Вставляет слоты, пропуская уже существующие (уникальный ключ MASTER_ID + STARTS_AT).
     *
     * @param array<int, array{MASTER_ID:int, STARTS_AT:DateTime, ENDS_AT:DateTime}> $slots
     * @return int Количество вставленных строк.
     */
    public function insertIgnore(array $slots): int
    {
        if ($slots === [])
        {
            return 0;
        }

        $connection = Application::getConnection();
        $helper = $connection->getSqlHelper();

        $inserted = 0;
        foreach (array_chunk($slots, 200) as $chunk)
        {
            $values = [];
            foreach ($chunk as $slot)
            {
                $values[] = sprintf(
                    '(%d, %s, %s, %s)',
                    $slot['MASTER_ID'],
                    $helper->convertToDbDateTime($slot['STARTS_AT']),
                    $helper->convertToDbDateTime($slot['ENDS_AT']),
                    "'N'"
                );
            }

            $connection->queryExecute($helper->getInsertIgnore(
                $helper->quote(SlotTable::getTableName()),
                ' (MASTER_ID, STARTS_AT, ENDS_AT, IS_CLOSED)',
                ' VALUES ' . implode(', ', $values)
            ));
            $inserted += $connection->getAffectedRowsCount();
        }

        SlotTable::cleanCache();

        return $inserted;
    }

    /**
     * @param int[] $ids
     */
    public function setClosed(array $ids, bool $closed): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === [])
        {
            return 0;
        }

        $result = SlotTable::updateMulti($ids, ['IS_CLOSED' => $closed]);

        return $result->isSuccess() ? count($ids) : 0;
    }

    /**
     * Запрос для административного списка слотов.
     */
    public function createListQuery(): Query
    {
        return SlotTable::query()
            ->setSelect(['ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT', 'IS_CLOSED', 'ENTRY_ID' => 'ENTRY.ID']);
    }

    /**
     * BooleanField в массивном fetch() отдаёт сырое значение 'Y'/'N', в объектах — bool.
     */
    public static function toBool(mixed $value): bool
    {
        return $value === true || $value === 'Y';
    }

    private function normalize(array $row): array
    {
        return [
            'ID' => (int)$row['ID'],
            'MASTER_ID' => (int)$row['MASTER_ID'],
            'STARTS_AT' => $row['STARTS_AT'],
            'ENDS_AT' => $row['ENDS_AT'],
            'IS_CLOSED' => self::toBool($row['IS_CLOSED']),
        ];
    }
}
