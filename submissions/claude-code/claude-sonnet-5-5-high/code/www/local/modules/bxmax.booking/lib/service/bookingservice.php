<?php

declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Dto\CreateBookingRequest;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\EntryRepository;
use Bxmax\Booking\Repository\SlotRepository;

final class BookingService
{
    public function __construct(
        private readonly SlotRepository $slots,
        private readonly EntryRepository $entries,
        private readonly CatalogRepository $catalog,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @return Result data: ['bookingId' => int]; коды ошибок: CONSENT_REQUIRED, VALIDATION, SLOT_NOT_FOUND, SLOT_TAKEN
     */
    public function create(CreateBookingRequest $request): Result
    {
        $result = new Result();

        if (!$request->consent)
        {
            return $result->addError(new Error('Необходимо согласие на обработку персональных данных', 'CONSENT_REQUIRED'));
        }

        $name = $this->normalizeName($request->name);
        $phone = $this->normalizePhone($request->phone);
        if ($name === null)
        {
            return $result->addError(new Error('Укажите имя (от 2 до 100 символов)', 'VALIDATION', ['field' => 'name']));
        }
        if ($phone === null)
        {
            return $result->addError(new Error('Укажите корректный номер телефона', 'VALIDATION', ['field' => 'phone']));
        }

        $service = $request->serviceId > 0 ? $this->catalog->findService($request->serviceId) : null;
        if ($service === null)
        {
            return $result->addError(new Error('Выберите услугу', 'VALIDATION', ['field' => 'serviceId']));
        }

        if ($request->slotId <= 0 || $this->slots->find($request->slotId) === null)
        {
            return $result->addError(new Error('Слот не найден', 'SLOT_NOT_FOUND'));
        }

        $connection = Application::getConnection();
        $connection->startTransaction();
        try
        {
            // блокировка строки слота: закрытие слота администратором и параллельные заявки сериализуются
            $slot = $this->slots->findForUpdate($request->slotId);
            if ($slot === null)
            {
                $connection->rollbackTransaction();

                return $result->addError(new Error('Слот не найден', 'SLOT_NOT_FOUND'));
            }

            $now = new DateTime();
            if ($slot['IS_CLOSED'] === 'Y' || $slot['STARTS_AT']->getTimestamp() <= $now->getTimestamp())
            {
                $connection->rollbackTransaction();

                return $result->addError(new Error('Слот уже занят', 'SLOT_TAKEN'));
            }

            // итоговая гарантия — уникальный индекс по SLOT_ID: вторая вставка получит DuplicateEntryException
            $bookingId = $this->entries->add($slot['ID'], $service['id'], $name, $phone, $now);
            if ($bookingId === null)
            {
                $connection->rollbackTransaction();

                return $result->addError(new Error('Слот уже занят', 'SLOT_TAKEN'));
            }

            $connection->commitTransaction();
        }
        catch (\Throwable $e)
        {
            $connection->rollbackTransaction();
            throw $e;
        }

        // письмо уходит после коммита и не может сорвать запись
        try
        {
            $master = $this->catalog->findMaster($slot['MASTER_ID']);
            $this->notifications->notifyNewBooking(
                $bookingId,
                $name,
                $phone,
                $service['name'],
                $master['name'] ?? '',
                $slot['STARTS_AT'],
            );
        }
        catch (\Throwable)
        {
            // уведомление — best effort
        }

        $result->setData(['bookingId' => $bookingId]);

        return $result;
    }

    private function normalizeName(string $name): ?string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $length = mb_strlen($name);
        if ($length < 2 || $length > 100)
        {
            return null;
        }

        return $name;
    }

    /**
     * Допускает +, пробелы, скобки и дефисы; хранит в виде «+» (если был) и цифр.
     */
    private function normalizePhone(string $phone): ?string
    {
        $phone = trim($phone);
        if (!preg_match('/^\+?[\d\s().-]{7,25}$/', $phone))
        {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $length = strlen($digits);
        if ($length < 10 || $length > 15)
        {
            return null;
        }

        return ($phone[0] === '+' ? '+' : '') . $digits;
    }
}
