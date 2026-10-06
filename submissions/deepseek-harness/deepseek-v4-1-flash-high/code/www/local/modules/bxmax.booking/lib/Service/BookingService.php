<?php

namespace Bxmax\Booking\Service;

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Notify\MailNotifierInterface;
use Bxmax\Booking\Repository\EntryAlreadyExistsException;
use Bxmax\Booking\Repository\EntryRepository;
use Bxmax\Booking\Repository\MasterRepository;
use Bxmax\Booking\Repository\ServiceRepository;
use Bxmax\Booking\Repository\SlotRepository;
use Bxmax\Booking\Value\ErrorCode;

/**
 * Creates bookings.
 *
 * Concurrency rule of the stand: "one slot - one booking". The check below is
 * only a fast path for a friendly message; the real guarantee is the unique
 * index on bxmax_booking_entry.SLOT_ID - a competing INSERT fails and is
 * translated into SLOT_TAKEN.
 */
final class BookingService
{
	private const NAME_MIN_LENGTH = 2;
	private const NAME_MAX_LENGTH = 100;
	private const PHONE_MIN_DIGITS = 10;
	private const PHONE_MAX_DIGITS = 15;

	public function __construct(
		private readonly SlotRepository $slotRepository,
		private readonly EntryRepository $entryRepository,
		private readonly MasterRepository $masterRepository,
		private readonly ServiceRepository $serviceRepository,
		private readonly MailNotifierInterface $notifier,
	)
	{
	}

	/**
	 * @param mixed $consent raw value from the request
	 * @return Result data: ['bookingId' => int]
	 */
	public function book(int $slotId, int $serviceId, string $name, string $phone, mixed $consent): Result
	{
		$result = new Result();

		if (!$this->isConsentGiven($consent))
		{
			return $result->addError(new Error(
				'Требуется согласие на обработку персональных данных',
				ErrorCode::CONSENT_REQUIRED
			));
		}

		$name = trim($name);
		$phone = $this->normalizePhone($phone);

		$hasValidationErrors = false;
		if (mb_strlen($name) < self::NAME_MIN_LENGTH || mb_strlen($name) > self::NAME_MAX_LENGTH)
		{
			$result->addError(new Error('Укажите имя (от 2 до 100 символов)', ErrorCode::VALIDATION));
			$hasValidationErrors = true;
		}

		if ($phone === null)
		{
			$result->addError(new Error('Укажите корректный телефон', ErrorCode::VALIDATION));
			$hasValidationErrors = true;
		}

		if ($hasValidationErrors)
		{
			return $result;
		}

		$slot = $this->slotRepository->findById($slotId);
		if ($slot === null)
		{
			return $result->addError(new Error('Слот не найден', ErrorCode::SLOT_NOT_FOUND));
		}

		$service = $this->serviceRepository->getById($serviceId);
		if ($service === null)
		{
			return $result->addError(new Error('Услуга не найдена', ErrorCode::VALIDATION));
		}

		if (($slot['CLOSED'] ?? 'N') === 'Y' || $this->entryRepository->findBySlotId($slotId) !== null)
		{
			return $result->addError(new Error('Это время уже занято', ErrorCode::SLOT_TAKEN));
		}

		$consentAt = new DateTime();

		try
		{
			$bookingId = $this->entryRepository->add([
				'SLOT_ID' => $slotId,
				'MASTER_ID' => (int)$slot['MASTER_ID'],
				'SERVICE_ID' => $serviceId,
				'NAME' => $name,
				'PHONE' => $phone,
				'CONSENT_AT' => $consentAt,
			]);
		}
		catch (EntryAlreadyExistsException)
		{
			return $result->addError(new Error('Это время уже занято', ErrorCode::SLOT_TAKEN));
		}

		$this->notifyAdmin($bookingId, $slot, $service, $name, $phone, $consentAt);

		return $result->setData(['bookingId' => $bookingId]);
	}

	private function isConsentGiven(mixed $consent): bool
	{
		if (is_bool($consent))
		{
			return $consent;
		}

		if (is_int($consent))
		{
			return $consent === 1;
		}

		if (!is_string($consent))
		{
			return false;
		}

		return in_array(mb_strtolower(trim($consent)), ['1', 'y', 'yes', 'on', 'true', 'да'], true);
	}

	private function normalizePhone(string $phone): ?string
	{
		$digits = preg_replace('/\D+/', '', $phone) ?? '';
		$length = mb_strlen($digits);

		if ($length < self::PHONE_MIN_DIGITS || $length > self::PHONE_MAX_DIGITS + 1)
		{
			return null;
		}

		return '+' . $digits;
	}

	/**
	 * Notification must never break a successful booking.
	 */
	private function notifyAdmin(int $bookingId, array $slot, array $service, string $name, string $phone, DateTime $consentAt): void
	{
		/** @var DateTime $startsAt */
		$startsAt = $slot['STARTS_AT'];
		/** @var DateTime $endsAt */
		$endsAt = $slot['ENDS_AT'];

		$master = $this->masterRepository->getById((int)$slot['MASTER_ID']) ?? ['name' => '—'];

		try
		{
			$this->notifier->notifyAdminAboutBooking(
				[
					'id' => $bookingId,
					'name' => $name,
					'phone' => $phone,
					'consentAt' => $consentAt->toString(),
				],
				[
					'date' => $startsAt->format('d.m.Y'),
					'time' => $startsAt->format('H:i') . ' - ' . $endsAt->format('H:i'),
				],
				[
					'name' => (string)$service['name'],
					'price' => (int)$service['price'],
				],
				[
					'name' => (string)$master['name'],
				]
			);
		}
		catch (\Throwable $exception)
		{
			Application::getInstance()->getExceptionHandler()->writeToLog($exception);
		}
	}
}
