<?php

declare(strict_types=1);

namespace Bxmax\Booking\Application\Service;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Infrastructure\Repository\BookingRepository;
use Bxmax\Booking\Infrastructure\Repository\ContentRepository;
use Bxmax\Booking\Infrastructure\Repository\SlotRepository;

final class BookingService
{
    private const MODULE_ID = 'bxmax.booking';

    public function __construct(
        private readonly SlotRepository $slots,
        private readonly BookingRepository $bookings,
        private readonly ContentRepository $content,
    ) {}

    public function create(
        int $slotId,
        int $serviceId,
        string $name,
        string $phone,
        bool $consent,
    ): Result {
        $result = new Result();
        if (!$consent)
        {
            return $result->addError(new Error('Необходимо согласие на обработку данных.', 'CONSENT_REQUIRED'));
        }

        $name = trim($name);
        $phone = trim($phone);
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || !preg_match('/^[+0-9()\-\s]{7,32}$/u', $phone))
        {
            return $result->addError(new Error('Проверьте имя и телефон.', 'VALIDATION'));
        }

        $slot = $this->slots->find($slotId);
        if ($slot === null)
        {
            return $result->addError(new Error('Слот не найден.', 'SLOT_NOT_FOUND'));
        }
        if (!$this->content->serviceExists($serviceId))
        {
            return $result->addError(new Error('Услуга не найдена.', 'VALIDATION'));
        }
        if ($slot['IS_CLOSED'] === 'Y' || $this->bookings->existsForSlot($slotId))
        {
            return $result->addError(new Error('Этот слот уже занят.', 'SLOT_TAKEN'));
        }

        try
        {
            $addResult = $this->bookings->create($slotId, $serviceId, $name, $phone, new DateTime());
        }
        catch (\Throwable $exception)
        {
            if ($this->bookings->existsForSlot($slotId))
            {
                return $result->addError(new Error('Этот слот уже занят.', 'SLOT_TAKEN'));
            }
            throw $exception;
        }

        if (!$addResult->isSuccess())
        {
            if ($this->bookings->existsForSlot($slotId))
            {
                return $result->addError(new Error('Этот слот уже занят.', 'SLOT_TAKEN'));
            }
            return $result->addErrors($addResult->getErrors());
        }

        $service = $this->content->getService($serviceId);
        $master = $this->content->getMaster((int)$slot['MASTER_ID']);
        $notificationEmail = trim(Option::get(self::MODULE_ID, 'notification_email', ''));
        if ($notificationEmail === '')
        {
            $notificationEmail = Option::get('main', 'email_from');
        }
        Event::send([
            'EVENT_NAME' => 'BXMAX_BOOKING_NEW',
            'LID' => SITE_ID,
            'C_FIELDS' => [
                'EMAIL_TO' => $notificationEmail,
                'BOOKING_ID' => (string)$addResult->getId(),
                'NAME' => $name,
                'PHONE' => $phone,
                'SERVICE' => (string)($service['NAME'] ?? ''),
                'MASTER' => (string)($master['NAME'] ?? ''),
                'STARTS_AT' => $slot['STARTS_AT']->format('d.m.Y H:i'),
            ],
        ]);

        return $result->setData(['bookingId' => (int)$addResult->getId()]);
    }
}
