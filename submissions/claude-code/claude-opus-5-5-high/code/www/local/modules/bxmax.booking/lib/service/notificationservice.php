<?php

declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\SiteTable;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\EntryRepository;

/**
 * Уведомление администратора о новой заявке через почтовое событие BXMAX_BOOKING_NEW.
 */
class NotificationService
{
    public const EVENT_NAME = 'BXMAX_BOOKING_NEW';
    public const MODULE_ID = 'bxmax.booking';

    public function __construct(
        private readonly EntryRepository $entries,
        private readonly CatalogRepository $catalog,
    )
    {
    }

    public function notifyNewBooking(int $entryId): void
    {
        $entry = $this->entries->findWithSlot($entryId);
        if ($entry === null)
        {
            return;
        }

        $masterId = (int)$entry['MASTER_ID'];
        $serviceId = (int)$entry['SERVICE_ID'];
        $service = $this->catalog->getService($serviceId);

        $fields = [
            'BOOKING_ID' => $entryId,
            'ADMIN_EMAIL' => $this->getAdminEmail(),
            'MASTER_NAME' => $this->catalog->getMasterNames([$masterId])[$masterId] ?? ('#' . $masterId),
            'SERVICE_NAME' => $service?->name ?? ($this->catalog->getServiceNames([$serviceId])[$serviceId] ?? ('#' . $serviceId)),
            'SERVICE_PRICE' => $service ? number_format($service->price, 0, ',', ' ') : '',
            'SLOT_DATE' => $entry['STARTS_AT']->format('d.m.Y'),
            'SLOT_TIME' => $entry['STARTS_AT']->format('H:i') . '–' . $entry['ENDS_AT']->format('H:i'),
            'NAME' => $entry['NAME'],
            'PHONE' => $entry['PHONE'],
            'CONSENT_AT' => $entry['CONSENT_AT']->format('d.m.Y H:i:s'),
            'ADMIN_URL' => '/bitrix/admin/bxmax_booking_entries.php?lang=ru',
        ];

        try
        {
            Event::send([
                'EVENT_NAME' => self::EVENT_NAME,
                'LID' => $this->getSiteId(),
                'C_FIELDS' => $fields,
            ]);
        }
        catch (\Throwable $exception)
        {
            // Заявка уже сохранена: сбой почты не должен отменять запись.
            Application::getInstance()->getExceptionHandler()->writeToLog($exception);
        }
    }

    private function getAdminEmail(): string
    {
        $email = trim(Option::get(self::MODULE_ID, 'admin_email', ''));
        if ($email === '')
        {
            $email = trim(Option::get('main', 'email_from', ''));
        }

        return $email;
    }

    private function getSiteId(): string
    {
        $siteId = Context::getCurrent()->getSite();
        if (is_string($siteId) && $siteId !== '' && !defined('ADMIN_SECTION'))
        {
            return $siteId;
        }

        $site = SiteTable::getList([
            'select' => ['LID'],
            'filter' => ['=ACTIVE' => 'Y'],
            'order' => ['DEF' => 'DESC', 'SORT' => 'ASC'],
            'limit' => 1,
            'cache' => ['ttl' => 3600],
        ])->fetch();

        return $site ? (string)$site['LID'] : 's1';
    }
}
