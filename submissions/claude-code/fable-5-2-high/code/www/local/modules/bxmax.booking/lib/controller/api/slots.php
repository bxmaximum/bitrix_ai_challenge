<?php

declare(strict_types=1);

namespace Bxmax\Booking\Controller\Api;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\DisablePrefilters;
use Bxmax\Booking\Controller\JsonAwareController;
use Bxmax\Booking\Service\SlotService;

/**
 * bxmax:booking.api.slots.*
 */
final class Slots extends JsonAwareController
{
	/**
	 * Публичное чтение расписания: гостю доступно, состояние не меняет, поэтому без
	 * авторизации и CSRF. Данные заявок в ответ не попадают.
	 */
	#[DisablePrefilters([ActionFilter\Authentication::class, ActionFilter\Csrf::class])]
	public function listAction(SlotService $slotService, $masterId = '', $weekStart = ''): ?array
	{
		$result = $slotService->getWeek((int)$this->toString($masterId), $this->toString($weekStart));
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getData();
	}
}
