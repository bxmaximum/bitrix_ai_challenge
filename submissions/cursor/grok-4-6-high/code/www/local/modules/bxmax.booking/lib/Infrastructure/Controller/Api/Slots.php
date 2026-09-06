<?php

declare(strict_types=1);

namespace Bxmax\Booking\Infrastructure\Controller\Api;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Bxmax\Booking\Application\Service\SlotService;

final class Slots extends Controller
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

    #[HttpMethod([ActionFilter\HttpMethod::METHOD_GET, ActionFilter\HttpMethod::METHOD_POST])]
    public function listAction(int $masterId, string $weekStart, SlotService $slotService): array
    {
        $result = $slotService->listForWeek($masterId, $weekStart);
        if (!$result->isSuccess()) {
            $this->addErrors($result->getErrors());

            return [];
        }

        return $result->getData();
    }
}
