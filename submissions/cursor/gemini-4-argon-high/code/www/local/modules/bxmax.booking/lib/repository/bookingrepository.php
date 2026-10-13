<?php declare(strict_types=1);

namespace Bxmax\Booking\Repository;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Model\BookingEntryTable;
use Bxmax\Booking\Model\SlotTable;

final class BookingRepository
{
    /**
     * Атомарно создаёт запись на слот внутри транзакции с блокировкой строки слота (FOR UPDATE)
     * и защитой уникальным индексом ux_bxmax_booking_entry_slot (SLOT_ID) на уровне СУБД.
     */
    public function createAtomic(
        int $slotId,
        int $serviceId,
        string $name,
        string $phone,
        DateTime $consentAt
    ): Result {
        $result = new Result();
        $connection = Application::getConnection();

        $connection->startTransaction();
        try
        {
            $lockSql = (new SqlExpression(
                'SELECT ID, MASTER_ID, STARTS_AT, ENDS_AT, IS_CLOSED FROM bxmax_booking_slot WHERE ID = ?i FOR UPDATE',
                $slotId
            ))->compile();
            $slotRow = $connection->query($lockSql)->fetch();

            if (!is_array($slotRow))
            {
                $connection->rollbackTransaction();
                return $result->addError(new Error('Выбранный слот не найден', 'SLOT_NOT_FOUND'));
            }

            if ((string)$slotRow['IS_CLOSED'] === 'Y')
            {
                $connection->rollbackTransaction();
                return $result->addError(new Error('Выбранный слот закрыт для записи', 'SLOT_TAKEN'));
            }

            $checkSql = (new SqlExpression(
                'SELECT ID FROM bxmax_booking_entry WHERE SLOT_ID = ?i LIMIT 1',
                $slotId
            ))->compile();
            if ($connection->query($checkSql)->fetch())
            {
                $connection->rollbackTransaction();
                return $result->addError(new Error('Выбранный слот уже занят', 'SLOT_TAKEN'));
            }

            $addResult = BookingEntryTable::add([
                'SLOT_ID' => $slotId,
                'SERVICE_ID' => $serviceId,
                'NAME' => $name,
                'PHONE' => $phone,
                'CONSENT_AT' => $consentAt,
                'CREATED_AT' => new DateTime(),
            ]);

            if (!$addResult->isSuccess())
            {
                $connection->rollbackTransaction();
                return $result->addError(new Error('Выбранный слот уже занят', 'SLOT_TAKEN'));
            }

            $bookingId = (int)$addResult->getId();
            $connection->commitTransaction();

            BookingEntryTable::cleanCache();
            SlotTable::cleanCache();

            return $result->setData([
                'bookingId' => $bookingId,
                'slot' => $slotRow,
            ]);
        }
        catch (SqlQueryException)
        {
            $connection->rollbackTransaction();
            return $result->addError(new Error('Выбранный слот уже занят', 'SLOT_TAKEN'));
        }
        catch (\Throwable $e)
        {
            $connection->rollbackTransaction();
            throw $e;
        }
    }

    public function deleteById(int $bookingId): Result
    {
        $result = new Result();
        if ($bookingId <= 0)
        {
            return $result->addError(new Error('Заявка не найдена', 'BOOKING_NOT_FOUND'));
        }

        $row = BookingEntryTable::getById($bookingId)->fetch();
        if (!$row)
        {
            return $result->addError(new Error('Заявка не найдена', 'BOOKING_NOT_FOUND'));
        }

        $deleteResult = BookingEntryTable::delete($bookingId);
        if (!$deleteResult->isSuccess())
        {
            return $result->addErrors($deleteResult->getErrors());
        }

        BookingEntryTable::cleanCache();
        SlotTable::cleanCache();

        return $result;
    }

    /**
     * @param array<string, mixed> $filter
     * @param array<string, string> $order
     * @return array<int, array<string, mixed>>
     */
    public function listForAdmin(array $filter = [], array $order = ['ID' => 'DESC'], ?int $limit = null, ?int $offset = null): array
    {
        $allowedOrderFields = [
            'ID' => 'ID',
            'SLOT_ID' => 'SLOT_ID',
            'SERVICE_ID' => 'SERVICE_ID',
            'NAME' => 'NAME',
            'PHONE' => 'PHONE',
            'CONSENT_AT' => 'CONSENT_AT',
            'STARTS_AT' => 'SLOT.STARTS_AT',
            'MASTER_ID' => 'SLOT.MASTER_ID',
        ];

        $safeOrder = [];
        foreach ($order as $field => $dir)
        {
            $fieldUpper = mb_strtoupper((string)$field);
            $dirUpper = mb_strtoupper((string)$dir) === 'ASC' ? 'ASC' : 'DESC';
            if (isset($allowedOrderFields[$fieldUpper]))
            {
                $safeOrder[$allowedOrderFields[$fieldUpper]] = $dirUpper;
            }
        }
        if (empty($safeOrder))
        {
            $safeOrder = ['ID' => 'DESC'];
        }

        $query = BookingEntryTable::query()
            ->setSelect([
                'ID',
                'SLOT_ID',
                'SERVICE_ID',
                'NAME',
                'PHONE',
                'CONSENT_AT',
                'CREATED_AT',
                'MASTER_ID' => 'SLOT.MASTER_ID',
                'STARTS_AT' => 'SLOT.STARTS_AT',
                'ENDS_AT' => 'SLOT.ENDS_AT',
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
        $row = BookingEntryTable::query()
            ->setSelect([new ExpressionField('CNT', 'COUNT(1)')])
            ->setFilter($filter)
            ->fetch();

        return (int)($row['CNT'] ?? 0);
    }
}
