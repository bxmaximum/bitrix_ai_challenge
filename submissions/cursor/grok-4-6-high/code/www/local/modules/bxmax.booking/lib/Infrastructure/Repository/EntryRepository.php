<?php

declare(strict_types=1);

namespace Bxmax\Booking\Infrastructure\Repository;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Application;
use Bitrix\Main\DB\DuplicateEntryException;
use Bitrix\Main\DB\SqlQueryException;
use Bxmax\Booking\Domain\Repository\EntryRepositoryInterface;
use Bxmax\Booking\Model\EntryTable;
use Bxmax\Booking\Model\SlotTable;

final class EntryRepository implements EntryRepositoryInterface
{
    private const ADMIN_SORT = ['ID', 'SLOT_ID', 'SERVICE_ID', 'NAME', 'PHONE', 'CONSENT_AT'];

    public function getById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = EntryTable::getByPrimary($id)->fetch();

        return $row ?: null;
    }

    public function existsForSlot(int $slotId): bool
    {
        $row = EntryTable::query()
            ->setSelect(['ID'])
            ->where('SLOT_ID', $slotId)
            ->setLimit(1)
            ->fetch();

        return is_array($row);
    }

    public function createExclusive(array $fields): ?array
    {
        $connection = Application::getConnection();
        $helper = $connection->getSqlHelper();
        $slotId = (int)$fields['SLOT_ID'];

        $connection->startTransaction();
        try {
            $table = $helper->quote(SlotTable::getTableName());
            $connection->queryExecute(
                'SELECT ID FROM ' . $table . ' WHERE ID = ' . $slotId . ' FOR UPDATE'
            );

            $result = EntryTable::add($fields);
            if (!$result->isSuccess()) {
                $connection->rollbackTransaction();

                return null;
            }

            $connection->commitTransaction();

            return ['id' => (int)$result->getId()];
        } catch (DuplicateEntryException) {
            $connection->rollbackTransaction();

            return null;
        } catch (SqlQueryException $exception) {
            $connection->rollbackTransaction();
            if (str_contains($exception->getMessage(), '1062') || str_contains($exception->getMessage(), 'Duplicate')) {
                return null;
            }
            throw $exception;
        }
    }

    public function delete(int $id): bool
    {
        $result = EntryTable::delete($id);

        return $result->isSuccess();
    }

    public function listAdmin(string $orderBy, string $orderDir, int $limit, int $offset): array
    {
        return EntryTable::query()
            ->setSelect(['ID', 'SLOT_ID', 'SERVICE_ID', 'NAME', 'PHONE', 'CONSENT_AT'])
            ->setOrder([$this->sanitizeSort($orderBy) => $orderDir === 'ASC' ? 'ASC' : 'DESC'])
            ->setLimit($limit)
            ->setOffset($offset)
            ->fetchAll();
    }

    public function countAdmin(): int
    {
        $row = EntryTable::query()
            ->registerRuntimeField(new ExpressionField('CNT', 'COUNT(1)'))
            ->setSelect(['CNT'])
            ->fetch();

        return (int)($row['CNT'] ?? 0);
    }

    private function sanitizeSort(string $orderBy): string
    {
        return in_array($orderBy, self::ADMIN_SORT, true) ? $orderBy : 'ID';
    }
}
