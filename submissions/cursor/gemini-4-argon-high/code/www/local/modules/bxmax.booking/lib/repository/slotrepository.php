<?php declare(strict_types=1);

namespace Bxmax\Booking\Repository;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Model\SlotTable;

final class SlotRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByMasterAndRange(int $masterId, DateTime $from, DateTime $to): array
    {
        return SlotTable::query()
            ->setSelect([
                'ID',
                'MASTER_ID',
                'STARTS_AT',
                'ENDS_AT',
                'IS_CLOSED',
                'ENTRY_ID' => 'ENTRY.ID',
            ])
            ->where('MASTER_ID', $masterId)
            ->where('STARTS_AT', '>=', $from)
            ->where('STARTS_AT', '<', $to)
            ->setOrder(['STARTS_AT' => 'ASC', 'ID' => 'ASC'])
            ->fetchAll();
    }

    public function findById(int $slotId): ?array
    {
        if ($slotId <= 0)
        {
            return null;
        }

        $row = SlotTable::query()
            ->setSelect([
                'ID',
                'MASTER_ID',
                'STARTS_AT',
                'ENDS_AT',
                'IS_CLOSED',
                'ENTRY_ID' => 'ENTRY.ID',
            ])
            ->where('ID', $slotId)
            ->setLimit(1)
            ->fetch();

        return is_array($row) ? $row : null;
    }

    public function setClosed(int $slotId, bool $closed): Result
    {
        $result = new Result();
        $slot = $this->findById($slotId);
        if ($slot === null)
        {
            return $result->addError(new Error('Слот не найден', 'SLOT_NOT_FOUND'));
        }

        $updateResult = SlotTable::update($slotId, [
            'IS_CLOSED' => $closed ? 'Y' : 'N',
        ]);

        if (!$updateResult->isSuccess())
        {
            return $result->addErrors($updateResult->getErrors());
        }

        SlotTable::cleanCache();

        return $result;
    }

    public function ensureSlot(int $masterId, DateTime $startsAt, DateTime $endsAt): bool
    {
        $existing = SlotTable::query()
            ->setSelect(['ID'])
            ->where('MASTER_ID', $masterId)
            ->where('STARTS_AT', $startsAt)
            ->setLimit(1)
            ->fetch();

        if ($existing)
        {
            return false;
        }

        try
        {
            $addResult = SlotTable::add([
                'MASTER_ID' => $masterId,
                'STARTS_AT' => $startsAt,
                'ENDS_AT' => $endsAt,
                'IS_CLOSED' => 'N',
                'CREATED_AT' => new DateTime(),
            ]);

            return $addResult->isSuccess();
        }
        catch (SqlQueryException)
        {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $filter
     * @param array<string, string> $order
     * @return array<int, array<string, mixed>>
     */
    public function listForAdmin(array $filter = [], array $order = ['STARTS_AT' => 'ASC'], ?int $limit = null, ?int $offset = null): array
    {
        $allowedOrderFields = ['ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT', 'IS_CLOSED'];
        $safeOrder = [];
        foreach ($order as $field => $dir)
        {
            $fieldUpper = mb_strtoupper((string)$field);
            $dirUpper = mb_strtoupper((string)$dir) === 'DESC' ? 'DESC' : 'ASC';
            if (in_array($fieldUpper, $allowedOrderFields, true))
            {
                $safeOrder[$fieldUpper] = $dirUpper;
            }
        }
        if (empty($safeOrder))
        {
            $safeOrder = ['STARTS_AT' => 'ASC', 'ID' => 'ASC'];
        }

        $query = SlotTable::query()
            ->setSelect([
                'ID',
                'MASTER_ID',
                'STARTS_AT',
                'ENDS_AT',
                'IS_CLOSED',
                'ENTRY_ID' => 'ENTRY.ID',
                'ENTRY_NAME' => 'ENTRY.NAME',
                'ENTRY_PHONE' => 'ENTRY.PHONE',
            ])
            ->setFilter($filter)
            ->setOrder($safeOrder);

        if ($limit !== null)
        {
            $query->setLimit($limit);
        }
        if ($offset !== null)
        {
            $query->setOffset($offset);
        }

        return $query->fetchAll();
    }

    /**
     * @param array<string, mixed> $filter
     */
    public function countForAdmin(array $filter = []): int
    {
        $row = SlotTable::query()
            ->setSelect([new ExpressionField('CNT', 'COUNT(1)')])
            ->setFilter($filter)
            ->fetch();

        return (int)($row['CNT'] ?? 0);
    }
}
