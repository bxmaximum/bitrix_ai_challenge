<?php

declare(strict_types=1);

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Service\ScheduleService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

/**
 * Форма онлайн-записи: готовит справочники и границы расписания,
 * вся работа со слотами и заявками идёт через контроллеры модуля.
 */
class BxmaxBookingFormComponent extends CBitrixComponent
{
	private const ACTION_SLOTS = 'bxmax:booking.api.slots.list';
	private const ACTION_CREATE = 'bxmax:booking.api.bookings.create';

	public function onPrepareComponentParams($arParams): array
	{
		$arParams['POLICY_URL'] = trim((string)($arParams['POLICY_URL'] ?? '')) ?: '/privacy/';
		$arParams['ADDRESS'] = trim((string)($arParams['ADDRESS'] ?? ''));
		$arParams['PHONE'] = trim((string)($arParams['PHONE'] ?? ''));

		return $arParams;
	}

	public function executeComponent(): void
	{
		if (!Loader::includeModule('bxmax.booking'))
		{
			// Модуль удалён: страница остаётся рабочей, блок записи просто не выводится.
			return;
		}

		/** @var CatalogRepository $catalog */
		$catalog = ServiceLocator::getInstance()->get(CatalogRepository::class);

		$services = [];
		foreach ($catalog->getServices() as $service)
		{
			$services[] = [
				'id' => $service['id'],
				'name' => $service['name'],
				'price' => $service['price'],
				'duration' => $service['duration'],
			];
		}

		$masters = [];
		foreach ($catalog->getMasters() as $master)
		{
			$masters[] = [
				'id' => $master['id'],
				'name' => $master['name'],
				'specialization' => $master['specialization'],
				'serviceIds' => $master['serviceIds'],
			];
		}

		$today = new DateTimeImmutable('today');
		$lastDay = $today->modify('+' . (ScheduleService::HORIZON_DAYS - 1) . ' days');

		$this->arResult = [
			'SERVICES' => $services,
			'MASTERS' => $masters,
			'CONFIG' => [
				'services' => $services,
				'masters' => $masters,
				'today' => $today->format('Y-m-d'),
				'firstWeek' => $today->modify('monday this week')->format('Y-m-d'),
				'lastWeek' => $lastDay->modify('monday this week')->format('Y-m-d'),
				'dayStartHour' => ScheduleService::DAY_START_HOUR,
				'dayEndHour' => ScheduleService::DAY_END_HOUR,
				'slotMinutes' => ScheduleService::SLOT_MINUTES,
				'endpoints' => [
					'slots' => '/bitrix/services/main/ajax.php?action=' . self::ACTION_SLOTS,
					'create' => '/bitrix/services/main/ajax.php?action=' . self::ACTION_CREATE,
				],
				'sessid' => bitrix_sessid(),
				'address' => $this->arParams['ADDRESS'],
			],
		];

		$this->includeComponentTemplate();
	}
}
