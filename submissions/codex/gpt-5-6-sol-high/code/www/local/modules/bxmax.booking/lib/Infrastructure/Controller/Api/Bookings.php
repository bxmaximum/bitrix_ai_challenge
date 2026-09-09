<?php

declare(strict_types=1);

namespace Bxmax\Booking\Infrastructure\Controller\Api;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\DisablePrefilters;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Bxmax\Booking\Application\Service\BookingService;

final class Bookings extends Controller
{
    #[HttpMethod([ActionFilter\HttpMethod::METHOD_POST])]
    #[DisablePrefilters([ActionFilter\Authentication::class])]
    public function createAction(
        int $slotId,
        int $serviceId,
        string $name,
        string $phone,
        BookingService $bookingService,
        bool $consent = false,
    ): array {
        $result = $bookingService->create($slotId, $serviceId, $name, $phone, $consent);
        if (!$result->isSuccess())
        {
            $this->addErrors($result->getErrors());
            return [];
        }

        return $result->getData();
    }
}
