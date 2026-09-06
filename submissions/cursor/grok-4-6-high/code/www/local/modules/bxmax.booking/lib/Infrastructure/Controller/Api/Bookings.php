<?php

declare(strict_types=1);

namespace Bxmax\Booking\Infrastructure\Controller\Api;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Bxmax\Booking\Application\Service\BookingService;

final class Bookings extends Controller
{
    protected function getDefaultPreFilters(): array
    {
        return [
            new ActionFilter\HttpMethod([
                ActionFilter\HttpMethod::METHOD_GET,
                ActionFilter\HttpMethod::METHOD_POST,
            ]),
            new ActionFilter\Csrf(),
            new ActionFilter\CloseSession(),
        ];
    }

    #[HttpMethod([ActionFilter\HttpMethod::METHOD_POST])]
    public function createAction(
        int $slotId,
        int $serviceId,
        string $name,
        string $phone,
        BookingService $bookingService,
    ): array {
        $consent = $this->getRequest()->getPost('consent');
        if ($consent === null) {
            $consent = $this->getRequest()->getQuery('consent');
        }
        $result = $bookingService->create($slotId, $serviceId, $name, $phone, $consent);
        if (!$result->isSuccess()) {
            $this->addErrors($result->getErrors());

            return [];
        }

        return $result->getData();
    }
}
