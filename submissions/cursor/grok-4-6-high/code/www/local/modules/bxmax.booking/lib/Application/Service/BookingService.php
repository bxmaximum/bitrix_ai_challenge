<?php

declare(strict_types=1);

namespace Bxmax\Booking\Application\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime as BxDateTime;
use Bxmax\Booking\Domain\Repository\CatalogRepositoryInterface;
use Bxmax\Booking\Domain\Repository\EntryRepositoryInterface;
use Bxmax\Booking\Domain\Repository\SlotRepositoryInterface;

final class BookingService
{
    public function __construct(
        private readonly SlotRepositoryInterface $slots,
        private readonly EntryRepositoryInterface $entries,
        private readonly CatalogRepositoryInterface $catalog,
        private readonly MailNotifier $mail,
    ) {
    }

    public function create(
        int $slotId,
        int $serviceId,
        string $name,
        string $phone,
        mixed $consent,
    ): Result {
        $result = new Result();

        if (!$this->isTruthy($consent)) {
            $result->addError(new Error('Consent is required', 'CONSENT_REQUIRED'));

            return $result;
        }

        $name = trim($name);
        $phone = trim($phone);
        if ($name === '' || mb_strlen($name) < 2 || $this->normalizePhone($phone) === '') {
            $result->addError(new Error('Invalid name or phone', 'VALIDATION'));

            return $result;
        }

        $slot = $this->slots->getById($slotId);
        if ($slot === null) {
            $result->addError(new Error('Slot not found', 'SLOT_NOT_FOUND'));

            return $result;
        }

        if (($slot['IS_CLOSED'] ?? 'N') === 'Y') {
            $result->addError(new Error('Slot is taken', 'SLOT_TAKEN'));

            return $result;
        }

        /** @var BxDateTime $starts */
        $starts = $slot['STARTS_AT'];
        if ($starts->getTimestamp() <= time()) {
            $result->addError(new Error('Slot is taken', 'SLOT_TAKEN'));

            return $result;
        }

        if (!$this->catalog->serviceExists($serviceId)) {
            $result->addError(new Error('Invalid service', 'VALIDATION'));

            return $result;
        }

        $created = $this->entries->createExclusive([
            'SLOT_ID' => $slotId,
            'SERVICE_ID' => $serviceId,
            'NAME' => $name,
            'PHONE' => $this->normalizePhone($phone),
            'CONSENT_AT' => new BxDateTime(),
        ]);

        if ($created === null) {
            $result->addError(new Error('Slot is taken', 'SLOT_TAKEN'));

            return $result;
        }

        $bookingId = (int)$created['id'];
        $this->mail->notifyNewBooking($bookingId, $slot, $serviceId, $name, $this->normalizePhone($phone));
        $result->setData(['bookingId' => $bookingId]);

        return $result;
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int)$value === 1;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'y', 'true', 'on', 'yes'], true);
        }

        return false;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return '';
        }

        return $phone;
    }
}
