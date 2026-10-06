<?php

namespace Bxmax\Booking\Controller\Api;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\AutoWire\Parameter;
use Bitrix\Main\Engine\Controller;
use Bxmax\Booking\Service\SlotService;

/**
 * bxmax:booking.api.slots.list
 *
 * Public endpoint (no authorization), protected by the standard CSRF filter:
 * the AJAX endpoint expects the sessid of the current session.
 */
final class Slots extends Controller
{
	public function configureActions(): array
	{
		return [
			'list' => [
				'-prefilters' => [
					Authentication::class,
				],
			],
		];
	}

	public function getAutoWiredParameters(): array
	{
		return [
			new Parameter(
				SlotService::class,
				static fn(string $className) => ServiceLocator::getInstance()->get($className)
			),
		];
	}

	/**
	 * @return array{slots: array}
	 */
	public function listAction(SlotService $slotService, int $masterId = 0, string $weekStart = ''): array
	{
		$result = $slotService->getWeek($masterId, $weekStart);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return [];
		}

		return $result->getData();
	}
}
