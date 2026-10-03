<?php

declare(strict_types=1);

namespace Bxmax\Booking\Controller\Api;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\DisablePrefilters;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Bxmax\Booking\Controller\InputTrait;
use Bxmax\Booking\Service\ScheduleService;

/**
 * bxmax:booking.api.slots.list
 *
 * Публичный метод для анонимных посетителей лендинга, поэтому без Authentication и CSRF.
 */
final class Slots extends Controller
{
    use InputTrait;

    #[HttpMethod([ActionFilter\HttpMethod::METHOD_GET, ActionFilter\HttpMethod::METHOD_POST])]
    #[DisablePrefilters([ActionFilter\Authentication::class, ActionFilter\Csrf::class])]
    public function listAction(ScheduleService $schedule): ?array
    {
        $result = $schedule->getWeek((int)$this->input('masterId'), (string)$this->input('weekStart'));
        if (!$result->isSuccess())
        {
            $this->addErrors($result->getErrors());

            return null;
        }

        return $result->getData();
    }
}
