<?php

declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Type\DateTime;

/**
 * Уведомление администратора о новой заявке через почтовое событие BXMAX_BOOKING_NEW.
 */
final class NotificationService
{
    public const MODULE_ID = 'bxmax.booking';
    public const EVENT_NAME = 'BXMAX_BOOKING_NEW';

    public function notifyNewBooking(int $bookingId, string $name, string $phone, string $service, string $master, DateTime $startsAt): bool
    {
        $adminEmail = $this->getAdminEmail();
        if ($adminEmail === '')
        {
            return false;
        }

        $result = Event::send([
            'EVENT_NAME' => self::EVENT_NAME,
            'LID' => $this->getSiteId(),
            'C_FIELDS' => [
                'ADMIN_EMAIL' => $adminEmail,
                'BOOKING_ID' => (string)$bookingId,
                'NAME' => $this->singleLine($name),
                'PHONE' => $this->singleLine($phone),
                'SERVICE' => $this->singleLine($service),
                'MASTER' => $this->singleLine($master),
                'DATE_TIME' => $startsAt->format('d.m.Y H:i'),
            ],
            'DUPLICATE' => 'N',
        ]);

        return $result->isSuccess();
    }

    private function getAdminEmail(): string
    {
        $email = trim((string)Option::get(self::MODULE_ID, 'admin_email', ''));
        if ($email === '')
        {
            $email = trim((string)Option::get('main', 'email_from', ''));
        }

        return check_email($email) ? $email : '';
    }

    private function getSiteId(): string
    {
        $siteId = Context::getCurrent()?->getSite();
        if ($siteId)
        {
            return $siteId;
        }

        $site = SiteTable::getList(['select' => ['LID'], 'filter' => ['=ACTIVE' => 'Y'], 'order' => ['DEF' => 'DESC'], 'limit' => 1])->fetch();

        return (string)($site['LID'] ?? 's1');
    }

    /**
     * Значения идут в тему/заголовки письма — переводы строк недопустимы.
     */
    private function singleLine(string $value): string
    {
        return trim(preg_replace('/[\r\n]+/', ' ', $value) ?? '');
    }
}
