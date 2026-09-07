<?php

declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Dto\BookingConfirmation;
use Bxmax\Booking\Dto\BookingRequest;
use Bxmax\Booking\ErrorCode;
use Bxmax\Booking\Repository\EntryRepository;
use Bxmax\Booking\Repository\MasterRepository;
use Bxmax\Booking\Repository\ServiceRepository;
use Bxmax\Booking\Repository\SlotRepository;
use Bxmax\Booking\Repository\SlotTakenException;

/**
 * Создание заявки на запись.
 */
final class BookingService
{
	private const NAME_MIN = 2;
	private const NAME_MAX = 120;

	public function __construct(
		private readonly SlotRepository $slots,
		private readonly EntryRepository $entries,
		private readonly ServiceRepository $services,
		private readonly MasterRepository $masters,
		private readonly NotificationService $notifications,
	) {
	}

	/**
	 * @return Result данные: ['confirmation' => BookingConfirmation]
	 */
	public function create(BookingRequest $request): Result
	{
		$result = new Result();

		if (!$request->consent)
		{
			return $result->addError(new Error(
				'Без согласия на обработку персональных данных запись невозможна.',
				ErrorCode::CONSENT_REQUIRED
			));
		}

		if ($request->slotId <= 0)
		{
			return $result->addError(new Error('Слот не найден.', ErrorCode::SLOT_NOT_FOUND));
		}

		$validation = $this->validate($request);
		if (!$validation->isSuccess())
		{
			return $validation;
		}

		$slot = $this->slots->findById($request->slotId);
		if ($slot === null)
		{
			return $result->addError(new Error('Слот не найден.', ErrorCode::SLOT_NOT_FOUND));
		}

		if ($slot['IS_CLOSED'] === 'Y' || $slot['STARTS_AT']->getTimestamp() <= time())
		{
			return $result->addError(new Error('Этот слот недоступен для записи.', ErrorCode::SLOT_TAKEN));
		}

		$consentAt = new DateTime();

		try
		{
			$bookingId = $this->entries->create(
				$request->slotId,
				$request->serviceId,
				$request->name,
				$request->phone,
				$consentAt
			);
		}
		catch (SlotTakenException)
		{
			return $result->addError(new Error('Этот слот только что заняли.', ErrorCode::SLOT_TAKEN));
		}

		$service = $this->services->find($request->serviceId) ?? [];
		$master = $this->masters->find((int)$slot['MASTER_ID']) ?? [];

		$confirmation = new BookingConfirmation(
			$bookingId,
			$request->slotId,
			$slot['STARTS_AT'],
			$slot['ENDS_AT'],
			(int)$slot['MASTER_ID'],
			(string)($master['name'] ?? ''),
			$request->serviceId,
			(string)($service['name'] ?? ''),
			(string)($service['price'] ?? ''),
			$request->name,
			$request->phone,
			$consentAt
		);

		$this->notifications->notifyAdmin($confirmation);

		return $result->setData(['confirmation' => $confirmation]);
	}

	private function validate(BookingRequest $request): Result
	{
		$result = new Result();
		$messages = [];

		$nameLength = mb_strlen($request->name);
		if ($nameLength < self::NAME_MIN || $nameLength > self::NAME_MAX)
		{
			$messages[] = 'Укажите имя (от 2 до 120 символов).';
		}

		if (preg_match('/^[\d\s()+\-]{7,32}$/u', $request->phone) !== 1
			|| mb_strlen(preg_replace('/\D/', '', $request->phone)) < 10)
		{
			$messages[] = 'Укажите телефон в формате +7 999 000-00-00.';
		}

		if ($request->serviceId <= 0 || !$this->services->exists($request->serviceId))
		{
			$messages[] = 'Выберите услугу из списка.';
		}

		if ($messages !== [])
		{
			$result->addError(new Error(implode(' ', $messages), ErrorCode::VALIDATION));
		}

		return $result;
	}
}
