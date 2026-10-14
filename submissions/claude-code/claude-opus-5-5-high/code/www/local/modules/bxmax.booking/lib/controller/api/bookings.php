<?php

declare(strict_types=1);

namespace Bxmax\Booking\Controller\Api;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bxmax\Booking\Service\BookingService;

/**
 * bxmax:booking.api.bookings.create — создание заявки с лендинга.
 */
final class Bookings extends Controller
{
    public function configureActions(): array
    {
        return [
            'create' => [
                // Записываться могут гости; CSRF (sessid) остаётся обязательным.
                '-prefilters' => [ActionFilter\Authentication::class],
                '+prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\CloseSession(),
                ],
            ],
        ];
    }

    public function createAction(
        BookingService $bookingService,
        string $slotId = '',
        string $serviceId = '',
        string $name = '',
        string $phone = '',
        string $consent = '',
    ): ?array
    {
        $result = $bookingService->create($slotId, $serviceId, $name, $phone, $consent);
        if (!$result->isSuccess())
        {
            $this->addErrors($result->getErrors());

            return null;
        }

        return ['bookingId' => (int)$result->getData()['bookingId']];
    }
}
