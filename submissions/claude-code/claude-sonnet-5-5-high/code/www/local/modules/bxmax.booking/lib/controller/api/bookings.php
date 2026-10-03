<?php

declare(strict_types=1);

namespace Bxmax\Booking\Controller\Api;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\DisablePrefilters;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Bxmax\Booking\Controller\InputTrait;
use Bxmax\Booking\Dto\CreateBookingRequest;
use Bxmax\Booking\Service\BookingService;

/**
 * bxmax:booking.api.bookings.create
 *
 * Публичный метод для анонимных посетителей лендинга, поэтому без Authentication и CSRF.
 */
final class Bookings extends Controller
{
    use InputTrait;

    #[HttpMethod([ActionFilter\HttpMethod::METHOD_POST])]
    #[DisablePrefilters([ActionFilter\Authentication::class, ActionFilter\Csrf::class])]
    public function createAction(BookingService $bookings): ?array
    {
        $result = $bookings->create(CreateBookingRequest::fromRaw(
            $this->input('slotId'),
            $this->input('serviceId'),
            $this->input('name'),
            $this->input('phone'),
            $this->input('consent'),
        ));

        if (!$result->isSuccess())
        {
            $this->addErrors($result->getErrors());

            return null;
        }

        return $result->getData();
    }
}
