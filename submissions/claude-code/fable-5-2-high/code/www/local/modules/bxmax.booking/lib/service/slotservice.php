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
use Bxmax\Booking\Repository\SlotRepository;

Loc::loadMessages(__FILE__);

/**
 * Расписание для посетителя и управление доступностью слотов.
 */
class SlotService
{
	public const ERROR_MASTER_NOT_FOUND = 'MASTER_NOT_FOUND';
	public const ERROR_VALIDATION = 'VALIDATION';
	public const ERROR_SLOT_NOT_FOUND = 'SLOT_NOT_FOUND';
	public const ERROR_SLOT_HAS_ENTRY = 'SLOT_HAS_ENTRY';

	public const STATUS_FREE = 'free';
	public const STATUS_TAKEN = 'taken';

	public function __construct(
		private readonly SlotRepository $slots,
		private readonly EntryRepository $entries,
		private readonly CatalogRepository $catalog,
	)
	{
	}

	/**
	 * Слоты мастера на неделю с понедельника $weekStart.
	 * В ответе только id, время и статус: данные заявок наружу не уходят.
	 *
	 * @return Result Успех: data = ['slots' => list<array{id: int, startsAt: string, endsAt: string, status: string}>].
	 */
	public function getWeek(int $masterId, string $weekStart): Result
	{
		$result = new Result();

		if ($this->catalog->findMaster($masterId) === null)
		{
			return $result->addError(new Error(
				Loc::getMessage('BXMAX_BOOKING_ERR_MASTER_NOT_FOUND'),
				self::ERROR_MASTER_NOT_FOUND
			));
		}

		$monday = $this->parseMonday($weekStart);
		if ($monday === null)
		{
			return $result->addError(new Error(
				Loc::getMessage('BXMAX_BOOKING_ERR_WEEK_START'),
				self::ERROR_VALIDATION,
				['field' => 'weekStart']
			));
		}

		$from = DateTime::createFromPhp(\DateTime::createFromImmutable($monday));
		$to = DateTime::createFromPhp(\DateTime::createFromImmutable($monday->modify('+7 days')));
		$now = time();

		$slots = [];
		foreach ($this->slots->findByMasterAndPeriod($masterId, $from, $to) as $slot)
		{
			$isFree = $slot['ENTRY_ID'] === null
				&& !$slot['CLOSED']
				&& $slot['STARTS_AT']->getTimestamp() > $now;

			$slots[] = [
				'id' => $slot['ID'],
				'startsAt' => $slot['STARTS_AT']->format(\DateTimeInterface::ATOM),
				'endsAt' => $slot['ENDS_AT']->format(\DateTimeInterface::ATOM),
				'status' => $isFree ? self::STATUS_FREE : self::STATUS_TAKEN,
			];
		}

		return $result->setData(['slots' => $slots]);
	}

	/**
	 * Закрывает слот для записи. Слот с заявкой закрыть нельзя: сначала удаляется заявка.
	 */
	public function close(int $slotId): Result
	{
		$result = new Result();
		$connection = Application::getConnection();
		$connection->startTransaction();

		try
		{
			// Та же блокировка строки, что и при записи: клиент не займёт слот, пока его закрывают.
			$slot = $this->slots->lockById($slotId);
			if ($slot === null)
			{
				$result->addError($this->slotNotFoundError($slotId));
			}
			elseif ($this->entries->existsForSlot($slotId))
			{
				$result->addError(new Error(
					Loc::getMessage('BXMAX_BOOKING_ERR_SLOT_HAS_ENTRY', ['#ID#' => $slotId]),
					self::ERROR_SLOT_HAS_ENTRY
				));
			}
			elseif (!$slot['CLOSED'])
			{
				$updateResult = $this->slots->setClosed($slotId, true);
				$result->addErrors($updateResult->getErrors());
			}

			if ($result->isSuccess())
			{
				$connection->commitTransaction();
			}
			else
			{
				$connection->rollbackTransaction();
			}
		}
		catch (\Throwable $exception)
		{
			$connection->rollbackTransaction();

			throw $exception;
		}

		return $result;
	}

	public function open(int $slotId): Result
	{
		$result = new Result();

		$slot = $this->slots->findById($slotId);
		if ($slot === null)
		{
			return $result->addError($this->slotNotFoundError($slotId));
		}

		if ($slot['CLOSED'])
		{
			$result->addErrors($this->slots->setClosed($slotId, false)->getErrors());
		}

		return $result;
	}

	private function parseMonday(string $weekStart): ?\DateTimeImmutable
	{
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStart) !== 1)
		{
			return null;
		}

		$date = \DateTimeImmutable::createFromFormat('!Y-m-d', $weekStart);
		if ($date === false || $date->format('Y-m-d') !== $weekStart || $date->format('N') !== '1')
		{
			return null;
		}

		return $date;
	}

	private function slotNotFoundError(int $slotId): Error
	{
		return new Error(
			Loc::getMessage('BXMAX_BOOKING_ERR_ADMIN_SLOT_NOT_FOUND', ['#ID#' => $slotId]),
			self::ERROR_SLOT_NOT_FOUND
		);
	}
}
