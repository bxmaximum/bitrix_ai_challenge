<?php

declare(strict_types=1);

namespace Bxmax\Booking\Infrastructure\Repository;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime as BxDateTime;
use Bxmax\Booking\Domain\Repository\SlotRepositoryInterface;
use Bxmax\Booking\Model\EntryTable;
use Bxmax\Booking\Model\SlotTable;

final class SlotRepository implements SlotRepositoryInterface
{
    private const ADMIN_SORT = ['ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT', 'IS_CLOSED'];

    public function getById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = SlotTable::getByPrimary($id)->fetch();

        return $row ?: null;
    }

    public function existsForMaster(int $masterId): bool
    {
        $row = SlotTable::query()
            ->setSelect(['ID'])
            ->where('MASTER_ID', $masterId)
            ->setLimit(1)
            ->fetch();

        return is_array($row);
    }

    public function listForMasterWeek(int $masterId, \DateTimeInterface $weekStart, \DateTimeInterface $weekEnd): array
    {
        $rows = SlotTable::query()
            ->setSelect(['ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT', 'IS_CLOSED'])
            ->where('MASTER_ID', $masterId)
            ->where('STARTS_AT', '>=', BxDateTime::createFromPhp(\DateTime::createFromInterface($weekStart)))
            ->where('STARTS_AT', '<', BxDateTime::createFromPhp(\DateTime::createFromInterface($weekEnd)))
            ->setOrder(['STARTS_AT' => 'ASC'])
            ->fetchAll();

        if ($rows === []) {
            return [];
        }

        $slotIds = array_map(static fn (array $row): int => (int)$row['ID'], $rows);
        $taken = [];
        $entries = EntryTable::query()
            ->setSelect(['SLOT_ID'])
            ->whereIn('SLOT_ID', $slotIds)
            ->fetchAll();
        foreach ($entries as $entry) {
            $taken[(int)$entry['SLOT_ID']] = true;
        }

        $now = new \DateTimeImmutable('now');
        $result = [];
        foreach ($rows as $row) {
            /** @var BxDateTime $starts */
            $starts = $row['STARTS_AT'];
            /** @var BxDateTime $ends */
            $ends = $row['ENDS_AT'];
            $startsAt = $starts->format('c');
            $isPast = $starts->getTimestamp() <= $now->getTimestamp();
            $isClosed = ($row['IS_CLOSED'] ?? 'N') === 'Y';
            $hasEntry = isset($taken[(int)$row['ID']]);
            $result[] = [
                'id' => (int)$row['ID'],
                'startsAt' => $startsAt,
                'endsAt' => $ends->format('c'),
                'status' => ($isClosed || $hasEntry || $isPast) ? 'taken' : 'free',
            ];
        }

        return $result;
    }

    public function listAdmin(?int $masterId, ?\DateTimeInterface $dateFrom, ?\DateTimeInterface $dateTo, string $orderBy, string $orderDir, int $limit, int $offset): array
    {
        $query = $this->adminQuery($masterId, $dateFrom, $dateTo);
        $query->setSelect(['ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT', 'IS_CLOSED']);
        $query->setOrder([$this->sanitizeSort($orderBy) => $orderDir === 'ASC' ? 'ASC' : 'DESC']);
        $query->setLimit($limit);
        $query->setOffset($offset);

        return $query->fetchAll();
    }

    public function countAdmin(?int $masterId, ?\DateTimeInterface $dateFrom, ?\DateTimeInterface $dateTo): int
    {
        $query = $this->adminQuery($masterId, $dateFrom, $dateTo);
        $query->registerRuntimeField(new ExpressionField('CNT', 'COUNT(1)'));
        $query->setSelect(['CNT']);
        $row = $query->fetch();

        return (int)($row['CNT'] ?? 0);
    }

    public function setClosed(int $id, bool $closed): bool
    {
        $result = SlotTable::update($id, ['IS_CLOSED' => $closed ? 'Y' : 'N']);

        return $result->isSuccess();
    }

    public function addMany(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        SlotTable::addMulti($rows);
    }

    private function adminQuery(?int $masterId, ?\DateTimeInterface $dateFrom, ?\DateTimeInterface $dateTo): Query
    {
        $query = SlotTable::query();
        if ($masterId !== null && $masterId > 0) {
            $query->where('MASTER_ID', $masterId);
        }
        if ($dateFrom !== null) {
            $query->where('STARTS_AT', '>=', BxDateTime::createFromPhp(\DateTime::createFromInterface($dateFrom)));
        }
        if ($dateTo !== null) {
            $query->where('STARTS_AT', '<', BxDateTime::createFromPhp(\DateTime::createFromInterface($dateTo)));
        }

        return $query;
    }

    private function sanitizeSort(string $orderBy): string
    {
        return in_array($orderBy, self::ADMIN_SORT, true) ? $orderBy : 'STARTS_AT';
    }
}
