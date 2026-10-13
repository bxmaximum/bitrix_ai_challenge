<?php declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\Result;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Repository\BookingRepository;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\SlotRepository;

final class BookingService
{
    public const MAIL_EVENT_NAME = 'BXMAX_BOOKING_NEW';

    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly SlotRepository $slotRepository,
        private readonly CatalogRepository $catalogRepository
    ) {
    }

    /**
     * @param array{
     *     slotId?: mixed,
     *     serviceId?: mixed,
     *     name?: mixed,
     *     phone?: mixed,
     *     consent?: mixed
     * } $input
     */
    public function createBooking(array $input): Result
    {
        $result = new Result();

        $consent = $input['consent'] ?? null;
        if (!$this->isTruthyConsent($consent))
        {
            return $result->addError(new Error(
                'Для записи необходимо согласие на обработку персональных данных',
                'CONSENT_REQUIRED'
            ));
        }

        $name = trim((string)($input['name'] ?? ''));
        $phoneRaw = trim((string)($input['phone'] ?? ''));
        $phoneDigits = preg_replace('/\D+/', '', $phoneRaw) ?? '';

        if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 255)
        {
            return $result->addError(new Error('Укажите ваше имя (от 2 символов)', 'VALIDATION'));
        }

        if ($phoneRaw === '' || strlen($phoneDigits) < 10 || strlen($phoneDigits) > 15)
        {
            return $result->addError(new Error('Укажите корректный номер телефона', 'VALIDATION'));
        }

        $rawServiceId = $input['serviceId'] ?? null;
        if ($rawServiceId === null || $rawServiceId === '' || !is_numeric($rawServiceId) || (int)$rawServiceId <= 0)
        {
            return $result->addError(new Error('Выберите услугу из списка', 'VALIDATION'));
        }

        $serviceId = (int)$rawServiceId;
        $service = $this->catalogRepository->findActiveServiceById($serviceId);
        if ($service === null)
        {
            return $result->addError(new Error('Выбранная услуга не найдена', 'VALIDATION'));
        }

        $rawSlotId = $input['slotId'] ?? null;
        if ($rawSlotId === null || $rawSlotId === '' || !is_numeric($rawSlotId))
        {
            return $result->addError(new Error('Выберите свободный слот расписания', 'VALIDATION'));
        }

        $slotId = (int)$rawSlotId;
        if ($slotId <= 0)
        {
            return $result->addError(new Error('Выбранный слот не найден', 'SLOT_NOT_FOUND'));
        }

        $consentAt = new DateTime();
        $atomicResult = $this->bookingRepository->createAtomic(
            $slotId,
            $serviceId,
            $name,
            $phoneRaw,
            $consentAt
        );

        if (!$atomicResult->isSuccess())
        {
            return $result->addErrors($atomicResult->getErrors());
        }

        $data = $atomicResult->getData();
        $bookingId = (int)$data['bookingId'];
        $slotInfo = $this->slotRepository->findById($slotId);

        $this->sendAdminNotification($bookingId, $slotInfo, $service, $name, $phoneRaw, $consentAt);

        return $result->setData([
            'bookingId' => $bookingId,
        ]);
    }

    public function deleteBooking(int $bookingId): Result
    {
        return $this->bookingRepository->deleteById($bookingId);
    }

    private function isTruthyConsent(mixed $consent): bool
    {
        if (is_bool($consent))
        {
            return $consent === true;
        }
        if (is_int($consent))
        {
            return $consent === 1;
        }
        if (is_string($consent))
        {
            $normalized = mb_strtolower(trim($consent));
            return in_array($normalized, ['1', 'true', 'y', 'yes', 'on', 'да'], true);
        }

        return false;
    }

    /**
     * @param array<string, mixed>|null $slotInfo
     * @param array<string, mixed> $service
     */
    private function sendAdminNotification(
        int $bookingId,
        ?array $slotInfo,
        array $service,
        string $clientName,
        string $clientPhone,
        DateTime $consentAt
    ): void {
        $masterName = 'Мастер студии';
        $slotStartFormatted = '';
        $slotEndFormatted = '';

        if ($slotInfo !== null)
        {
            $master = $this->catalogRepository->findActiveMasterById((int)$slotInfo['MASTER_ID']);
            if ($master !== null)
            {
                $masterName = (string)$master['name'];
            }
            if ($slotInfo['STARTS_AT'] instanceof DateTime)
            {
                $slotStartFormatted = $slotInfo['STARTS_AT']->format('d.m.Y H:i');
            }
            if ($slotInfo['ENDS_AT'] instanceof DateTime)
            {
                $slotEndFormatted = $slotInfo['ENDS_AT']->format('H:i');
            }
        }

        $adminEmail = trim((string)Option::get('bxmax.booking', 'admin_email', ''));
        if ($adminEmail === '' || !check_email($adminEmail))
        {
            $adminEmail = trim((string)Option::get('main', 'email_from', 'ai@example.com'));
        }
        if ($adminEmail === '' || !check_email($adminEmail))
        {
            $adminEmail = 'admin@localhost';
        }

        $siteId = 's1';
        $defaultSite = SiteTable::query()
            ->setSelect(['LID'])
            ->where('ACTIVE', 'Y')
            ->setOrder(['DEF' => 'DESC', 'SORT' => 'ASC'])
            ->setLimit(1)
            ->fetch();
        if ($defaultSite && !empty($defaultSite['LID']))
        {
            $siteId = (string)$defaultSite['LID'];
        }

        $cleanName = str_replace(["\r", "\n"], ' ', $clientName);
        $cleanPhone = str_replace(["\r", "\n"], ' ', $clientPhone);

        Event::send([
            'EVENT_NAME' => self::MAIL_EVENT_NAME,
            'LID' => $siteId,
            'LANGUAGE_ID' => 'ru',
            'C_FIELDS' => [
                'BOOKING_ID' => (string)$bookingId,
                'MASTER_NAME' => $masterName,
                'SERVICE_NAME' => (string)$service['name'],
                'SERVICE_PRICE' => (string)$service['priceFormatted'],
                'SLOT_TIME' => trim($slotStartFormatted . ($slotEndFormatted !== '' ? ' – ' . $slotEndFormatted : '')),
                'CLIENT_NAME' => $cleanName,
                'CLIENT_PHONE' => $cleanPhone,
                'CONSENT_AT' => $consentAt->format('d.m.Y H:i:s'),
                'EMAIL_TO' => $adminEmail,
            ],
        ]);
    }
}
