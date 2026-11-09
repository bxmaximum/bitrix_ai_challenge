<?php

declare(strict_types=1);

namespace Bxmax\Booking\Service;

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\EntryRepository;
use Bxmax\Booking\Repository\SlotOccupiedException;
use Bxmax\Booking\Repository\SlotRepository;

Loc::loadMessages(__FILE__);

/**
 * Запись клиента на слот и отмена записи.
 */
class BookingService
{
	public const ERROR_SLOT_NOT_FOUND = 'SLOT_NOT_FOUND';
	public const ERROR_SLOT_TAKEN = 'SLOT_TAKEN';
	public const ERROR_VALIDATION = 'VALIDATION';
	public const ERROR_CONSENT_REQUIRED = 'CONSENT_REQUIRED';
	public const ERROR_ENTRY_NOT_FOUND = 'ENTRY_NOT_FOUND';

	private const NAME_MIN = 2;
	private const NAME_MAX = 60;

	public function __construct(
		private readonly SlotRepository $slots,
		private readonly EntryRepository $entries,
		private readonly CatalogRepository $catalog,
		private readonly NotificationService $notifications,
	)
	{
	}

	/**
	 * @return Result Успех: data = ['bookingId' => int].
	 */
	public function create(int $slotId, int $serviceId, string $name, string $phone, bool $consent): Result
	{
		$result = new Result();

		if (!$consent)
		{
			$result->addError(new Error(
				Loc::getMessage('BXMAX_BOOKING_ERR_CONSENT'),
				self::ERROR_CONSENT_REQUIRED,
				['field' => 'consent']
			));
		}

		$name = $this->normalizeName($name);
		if (!$this->isValidName($name))
		{
			$result->addError($this->validationError('name', 'BXMAX_BOOKING_ERR_NAME'));
		}

		$normalizedPhone = $this->normalizePhone($phone);
		if ($normalizedPhone === null)
		{
			$result->addError($this->validationError('phone', 'BXMAX_BOOKING_ERR_PHONE'));
		}

		$service = $this->catalog->findService($serviceId);
		if ($service === null)
		{
			$result->addError($this->validationError('serviceId', 'BXMAX_BOOKING_ERR_SERVICE'));
		}

		if ($slotId <= 0)
		{
			$result->addError($this->validationError('slotId', 'BXMAX_BOOKING_ERR_SLOT_REQUIRED'));
		}

		if (!$result->isSuccess())
		{
			return $result;
		}

		$slot = $this->slots->findById($slotId);
		if ($slot === null)
		{
			return $result->addError(new Error(
				Loc::getMessage('BXMAX_BOOKING_ERR_SLOT_NOT_FOUND'),
				self::ERROR_SLOT_NOT_FOUND
			));
		}

		$master = $this->catalog->findMaster($slot['MASTER_ID']);
		if ($master === null)
		{
			// Мастер снят с публикации: его расписание для записи недоступно.
			return $result->addError($this->slotTakenError());
		}

		if ($master['serviceIds'] && !in_array($service['id'], $master['serviceIds'], true))
		{
			return $result->addError($this->validationError('serviceId', 'BXMAX_BOOKING_ERR_SERVICE_MASTER'));
		}

		$consentAt = new DateTime();
		$connection = Application::getConnection();
		$connection->startTransaction();

		try
		{
			$lockedSlot = $this->slots->lockById($slotId);
			if ($lockedSlot === null)
			{
				$connection->rollbackTransaction();

				return $result->addError(new Error(
					Loc::getMessage('BXMAX_BOOKING_ERR_SLOT_NOT_FOUND'),
					self::ERROR_SLOT_NOT_FOUND
				));
			}

			if ($lockedSlot['CLOSED'] || $lockedSlot['STARTS_AT']->getTimestamp() <= time())
			{
				$connection->rollbackTransaction();

				return $result->addError($this->slotTakenError());
			}

			// Проверки «свободен ли слот» здесь нет намеренно: исход решает уникальный индекс.
			$addResult = $this->entries->add($slotId, $service['id'], $name, $normalizedPhone, $consentAt);
			if (!$addResult->isSuccess())
			{
				$connection->rollbackTransaction();

				return $result->addError(new Error(
					implode(' ', $addResult->getErrorMessages()),
					self::ERROR_VALIDATION
				));
			}

			$connection->commitTransaction();
		}
		catch (SlotOccupiedException)
		{
			$connection->rollbackTransaction();

			return $result->addError($this->slotTakenError());
		}
		catch (\Throwable $exception)
		{
			$connection->rollbackTransaction();

			throw $exception;
		}

		$entryId = (int)$addResult->getId();

		// Письмо — после фиксации: сбой почты не должен отменять уже принятую запись.
		$this->notifications->notifyNewEntry(
			$entryId,
			$lockedSlot['STARTS_AT'],
			$master['name'],
			$service['name'],
			$name,
			$normalizedPhone,
			$consentAt
		);

		return $result->setData(['bookingId' => $entryId]);
	}

	/**
	 * Удаляет заявку; слот снова свободен, потому что занятость слота — это наличие заявки.
	 */
	public function cancel(int $entryId): Result
	{
		$result = new Result();

		if ($this->entries->findById($entryId) === null)
		{
			return $result->addError(new Error(
				Loc::getMessage('BXMAX_BOOKING_ERR_ENTRY_NOT_FOUND', ['#ID#' => $entryId]),
				self::ERROR_ENTRY_NOT_FOUND
			));
		}

		$deleteResult = $this->entries->delete($entryId);
		if (!$deleteResult->isSuccess())
		{
			$result->addErrors($deleteResult->getErrors());
		}

		return $result;
	}

	private function normalizeName(string $name): string
	{
		return trim((string)preg_replace('/\s+/u', ' ', $name));
	}

	private function isValidName(string $name): bool
	{
		$length = mb_strlen($name);
		if ($length < self::NAME_MIN || $length > self::NAME_MAX)
		{
			return false;
		}

		// Буквы любого алфавита, пробел, дефис, апостроф, точка: «Анна-Мария», «О'Нил».
		return preg_match("/^\p{L}[\p{L} .'’\-]*$/u", $name) === 1;
	}

	/**
	 * Российский номер в любом привычном написании -> +7XXXXXXXXXX.
	 */
	private function normalizePhone(string $phone): ?string
	{
		if (preg_match('/[^\d\s()+\-.]/', $phone))
		{
			return null;
		}

		$digits = (string)preg_replace('/\D+/', '', $phone);
		if (strlen($digits) === 11 && ($digits[0] === '7' || $digits[0] === '8'))
		{
			$digits = substr($digits, 1);
		}

		if (strlen($digits) !== 10 || !in_array($digits[0], ['3', '4', '8', '9'], true))
		{
			return null;
		}

		return '+7' . $digits;
	}

	private function validationError(string $field, string $messageCode): Error
	{
		return new Error(Loc::getMessage($messageCode), self::ERROR_VALIDATION, ['field' => $field]);
	}

	private function slotTakenError(): Error
	{
		return new Error(Loc::getMessage('BXMAX_BOOKING_ERR_SLOT_TAKEN'), self::ERROR_SLOT_TAKEN);
	}
}
