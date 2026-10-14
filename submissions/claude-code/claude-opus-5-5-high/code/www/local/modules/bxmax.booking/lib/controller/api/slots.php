<?php

declare(strict_types=1);

namespace Bxmax\Booking\Controller\Api;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bxmax\Booking\Service\SlotService;

/**
 * bxmax:booking.api.slots.list — расписание мастера на неделю.
 */
final class Slots extends Controller
{
    public function configureActions(): array
    {
        return [
            'list' => [
                // Публичное чтение без персональных данных: гостям доступно, CSRF не нужен.
                '-prefilters' => [ActionFilter\Authentication::class, ActionFilter\Csrf::class],
                '+prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET, ActionFilter\HttpMethod::METHOD_POST]),
                ],
            ],
        ];
    }

    public function listAction(SlotService $slotService, string $masterId = '', string $weekStart = ''): ?array
    {
        $result = $slotService->getWeek((int)$masterId, trim($weekStart));
        if (!$result->isSuccess())
        {
            $this->addErrors($result->getErrors());

            return null;
        }

        return $result->getData();
    }
}
