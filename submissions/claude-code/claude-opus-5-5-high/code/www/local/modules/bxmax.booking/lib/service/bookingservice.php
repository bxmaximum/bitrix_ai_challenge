<?php

declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Application;
use Bitrix\Main\DB\DuplicateEntryException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\ErrorCode;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\EntryRepository;
use Bxmax\Booking\Repository\SlotRepository;

/**
 * Создание и удаление заявок.
 *
 * «Один слот — одна запись» держит уникальный индекс по bxmax_booking_entry.SLOT_ID:
 * из конкурирующих INSERT успешен ровно один, остальные получают DuplicateEntryException → SLOT_TAKEN.
 * Блокировка строки слота (FOR UPDATE) дополнительно сериализует запись с закрытием слота в админке.
 */
class BookingService
{
    private const NAME_PATTERN = '/^\p{L}[\p{L}\s\'’.-]{0,59}$/u';
    private const CONSENT_TRUE = ['1', 'true', 'y', 'yes', 'on'];

    public function __construct(
        private readonly SlotRepository $slots,
        private readonly EntryRepository $entries,
        private readonly CatalogRepository $catalog,
        private readonly SlotService $slotService,
        private readonly NotificationService $notifications,
    )
    {
    }

    /**
     * @return Result data: ['bookingId' => int]
     */
    public function create(string $slotId, string $serviceId, string $name, string $phone, string $consent): Result
    {
        $result = new Result();

        if (!$this->isConsentGiven($consent))
        {
            return $result->addError(new Error(
                Loc::getMessage('BXMAX_BOOKING_ERROR_CONSENT_REQUIRED'),
                ErrorCode::CONSENT_REQUIRED,
                ['field' => 'consent']
            ));
        }

        $name = $this->normalizeName($name);
        $normalizedPhone = $this->normalizePhone($phone);
        $slotIdInt = $this->toPositiveInt($slotId);
        $serviceIdInt = $this->toPositiveInt($serviceId);

        if ($slotIdInt === null)
        {
            $result->addError($this->validationError('slotId', 'BXMAX_BOOKING_ERROR_SLOT_REQUIRED'));
        }
        if ($serviceIdInt === null)
        {
            $result->addError($this->validationError('serviceId', 'BXMAX_BOOKING_ERROR_SERVICE_REQUIRED'));
        }
        if (!preg_match(self::NAME_PATTERN, $name))
        {
            $result->addError($this->validationError('name', 'BXMAX_BOOKING_ERROR_NAME'));
        }
        if ($normalizedPhone === null)
        {
            $result->addError($this->validationError('phone', 'BXMAX_BOOKING_ERROR_PHONE'));
        }
        if (!$result->isSuccess())
        {
            return $result;
        }

        $service = $this->catalog->getService($serviceIdInt);
        if ($service === null)
        {
            return $result->addError($this->validationError('serviceId', 'BXMAX_BOOKING_ERROR_SERVICE_NOT_FOUND'));
        }

        $connection = Application::getConnection();
        $connection->startTransaction();
        try
        {
            $slot = $this->slots->lockById($slotIdInt);
            if ($slot === null)
            {
                $connection->rollbackTransaction();

                return $result->addError(new Error(
                    Loc::getMessage('BXMAX_BOOKING_ERROR_SLOT_NOT_FOUND'),
                    ErrorCode::SLOT_NOT_FOUND
                ));
            }

            $master = $this->catalog->getMaster($slot['MASTER_ID']);
            if ($master === null || !$master->canPerform($service->id))
            {
                $connection->rollbackTransaction();

                return $result->addError($this->validationError('serviceId', 'BXMAX_BOOKING_ERROR_SERVICE_MASTER'));
            }

            if (!$this->slotService->isBookable($slot))
            {
                $connection->rollbackTransaction();

                return $result->addError($this->slotTakenError());
            }

            $bookingId = $this->entries->add($slot['ID'], $service->id, $name, $normalizedPhone, new DateTime());
            $connection->commitTransaction();
        }
        catch (DuplicateEntryException)
        {
            $connection->rollbackTransaction();

            return $result->addError($this->slotTakenError());
        }
        catch (\Throwable $exception)
        {
            $connection->rollbackTransaction();
            Application::getInstance()->getExceptionHandler()->writeToLog($exception);

            return $result->addError(new Error(Loc::getMessage('BXMAX_BOOKING_ERROR_INTERNAL'), ErrorCode::INTERNAL));
        }

        $this->notifications->notifyNewBooking($bookingId);

        return $result->setData(['bookingId' => $bookingId]);
    }

    /**
     * Удаляет заявку; слот освобождается автоматически, т.к. занятость вычисляется по наличию заявки.
     */
    public function delete(int $entryId): Result
    {
        $result = new Result();
        if (!$this->entries->delete($entryId))
        {
            $result->addError(new Error(Loc::getMessage('BXMAX_BOOKING_ERROR_ENTRY_NOT_FOUND'), ErrorCode::ENTRY_NOT_FOUND));
        }

        return $result;
    }

    public function normalizePhone(string $phone): ?string
    {
        $phone = trim($phone);
        if ($phone === '' || preg_match('/[^\d\s()+\-.]/', $phone))
        {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) === 11 && ($digits[0] === '8' || $digits[0] === '7'))
        {
            return '+7' . substr($digits, 1);
        }
        if (strlen($digits) === 10 && $digits[0] === '9')
        {
            return '+7' . $digits;
        }
        if (str_starts_with($phone, '+') && strlen($digits) >= 10 && strlen($digits) <= 15)
        {
            return '+' . $digits;
        }

        return null;
    }

    private function normalizeName(string $name): string
    {
        return trim((string)preg_replace('/\s+/u', ' ', $name));
    }

    private function isConsentGiven(string $consent): bool
    {
        return in_array(mb_strtolower(trim($consent)), self::CONSENT_TRUE, true);
    }

    private function toPositiveInt(string $value): ?int
    {
        $value = trim($value);

        return preg_match('/^[1-9]\d{0,9}$/', $value) ? (int)$value : null;
    }

    private function validationError(string $field, string $messageCode): Error
    {
        return new Error(Loc::getMessage($messageCode), ErrorCode::VALIDATION, ['field' => $field]);
    }

    private function slotTakenError(): Error
    {
        return new Error(Loc::getMessage('BXMAX_BOOKING_ERROR_SLOT_TAKEN'), ErrorCode::SLOT_TAKEN);
    }
}
