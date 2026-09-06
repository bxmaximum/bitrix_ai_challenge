<?php

declare(strict_types=1);

namespace Bxmax\Booking\Application\Service;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Mail\Event;
use Bxmax\Booking\Domain\Repository\CatalogRepositoryInterface;
use Bxmax\Booking\Model\EntryTable;

final class MailNotifier
{
    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
    ) {
    }

    public function notifyNewBooking(int $bookingId, array $slot, int $serviceId, string $name, string $phone): void
    {
        $entry = EntryTable::getByPrimary($bookingId)->fetch();
        $consentAt = '';
        if (is_array($entry) && isset($entry['CONSENT_AT'])) {
            $consentAt = (string)$entry['CONSENT_AT'];
        }

        $starts = isset($slot['STARTS_AT']) ? (string)$slot['STARTS_AT'] : '';
        $masterId = (int)($slot['MASTER_ID'] ?? 0);

        Event::sendImmediate([
            'EVENT_NAME' => 'BXMAX_BOOKING_NEW',
            'LID' => defined('SITE_ID') ? SITE_ID : 's1',
            'C_FIELDS' => [
                'EMAIL_TO' => $this->adminEmail(),
                'BOOKING_ID' => (string)$bookingId,
                'NAME' => $name,
                'PHONE' => $phone,
                'SERVICE' => $this->catalog->getServiceName($serviceId),
                'MASTER' => $this->catalog->getMasterName($masterId),
                'SLOT' => $starts,
                'CONSENT_AT' => $consentAt,
            ],
        ]);
    }

    private function adminEmail(): string
    {
        $configured = trim((string)Option::get('bxmax.booking', 'notify_email', ''));
        if ($configured !== '') {
            return $configured;
        }

        $from = trim((string)Option::get('main', 'email_from', ''));

        return $from !== '' ? $from : 'admin@localhost';
    }
}
