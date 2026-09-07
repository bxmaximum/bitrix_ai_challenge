<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bxmax\Booking\Repository\MasterRepository;
use Bxmax\Booking\Repository\ServiceRepository;
use Bxmax\Booking\Service\ScheduleService;

/**
 * Форма онлайн-записи: выбор услуги, мастера и слота.
 * Компонент только собирает данные для шаблона, вся логика — в сервисах модуля.
 */
final class BxmaxBookingFormComponent extends CBitrixComponent
{
	public function onPrepareComponentParams($arParams): array
	{
		$arParams['CONSENT_URL'] = trim((string)($arParams['CONSENT_URL'] ?? '')) ?: '/privacy/';

		return $arParams;
	}

	public function executeComponent(): void
	{
		Loc::loadMessages(__FILE__);

		if (!Loader::includeModule('bxmax.booking'))
		{
			ShowError(Loc::getMessage('BXMAX_BOOKING_COMP_NO_MODULE'));

			return;
		}

		$locator = ServiceLocator::getInstance();

		/** @var ServiceRepository $services */
		$services = $locator->get(ServiceRepository::class);
		/** @var MasterRepository $masters */
		$masters = $locator->get(MasterRepository::class);

		$this->arResult['SERVICES'] = array_values($services->getAll());
		$this->arResult['MASTERS'] = array_values($masters->getAll());
		$this->arResult['WEEK_START'] = ScheduleService::normalizeWeekStart('')->format('Y-m-d');
		$this->arResult['WEEK_MIN'] = $this->arResult['WEEK_START'];
		$this->arResult['WEEK_MAX'] = ScheduleService::normalizeWeekStart(
			date('Y-m-d', strtotime('+13 days'))
		)->format('Y-m-d');
		$this->arResult['SESSID'] = bitrix_sessid();
		$this->arResult['AJAX_URL'] = '/bitrix/services/main/ajax.php';
		$this->arResult['ACTION_SLOTS'] = 'bxmax:booking.api.slots.list';
		$this->arResult['ACTION_CREATE'] = 'bxmax:booking.api.bookings.create';

		$this->includeComponentTemplate();
	}
}
