<?php

declare(strict_types=1);

namespace Bxmax\Booking\Controller;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\Authentication;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\HttpMethod;
use Bitrix\Main\Engine\ActionFilter\FilterType;
use Bitrix\Main\Engine\ActionFilter\HttpMethod as HttpMethodFilter;
use Bitrix\Main\Engine\Controller;
use Bxmax\Booking\Dto\SlotView;
use Bxmax\Booking\Service\ScheduleService;

/**
 * Публичное чтение расписания.
 * Действие: bxmax:booking.api.slots.list
 */
final class Slots extends Controller
{
	/**
	 * @return array{slots: array<int, array>}|null
	 */
	#[Authentication(type: FilterType::DisablePrefilter)]
	#[HttpMethod([HttpMethodFilter::METHOD_GET, HttpMethodFilter::METHOD_POST])]
	public function listAction(
		ScheduleService $schedule,
		int $masterId = 0,
		string $weekStart = ''
	): ?array {
		$result = $schedule->getWeek($masterId, $weekStart);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return [
			'slots' => array_map(
				static fn(SlotView $slot): array => $slot->toArray(),
				$result->getData()['slots']
			),
		];
	}
}
