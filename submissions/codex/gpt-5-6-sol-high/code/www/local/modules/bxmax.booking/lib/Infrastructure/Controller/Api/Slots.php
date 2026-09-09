<?php

declare(strict_types=1);

namespace Bxmax\Booking\Infrastructure\Controller\Api;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\DisablePrefilters;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Bxmax\Booking\Application\Service\SlotService;

final class Slots extends Controller
{
    #[HttpMethod([ActionFilter\HttpMethod::METHOD_GET])]
    #[DisablePrefilters([ActionFilter\Authentication::class, ActionFilter\Csrf::class])]
    public function listAction(int $masterId, string $weekStart, SlotService $slotService): array
    {
        $result = $slotService->list($masterId, $weekStart);
        if (!$result->isSuccess())
        {
            $this->addErrors($result->getErrors());
            return [];
        }

        return $result->getData();
    }
}
