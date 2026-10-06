<?php

namespace Bxmax\Booking\Component;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bxmax\Booking\Repository\MasterRepository;
use Bxmax\Booking\Repository\ServiceRepository;
use CBitrixComponent;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Компонент модуля bxmax.booking: услуги, мастера и виджет онлайн-записи.
 *
 * Логики бронирования здесь нет — компонент только собирает данные для
 * шаблона, всё остальное живёт в сервисах и репозиториях модуля.
 */
final class BookingSlotsComponent extends CBitrixComponent
{
	private const WEEKS_AHEAD = 2;

	private const MONTHS = [
		'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
		'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря',
	];

	private const WEEKDAYS = ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];

	public function executeComponent(): void
	{
		Loader::includeModule('bxmax.booking');

		$locator = ServiceLocator::getInstance();

		$services = [];
		foreach ($locator->get(ServiceRepository::class)->getAll() as $service)
		{
			$services[] = [
				'ID' => (int)$service['id'],
				'NAME' => (string)$service['name'],
				'PRICE' => (int)$service['price'],
				'DURATION' => (int)$service['duration'],
			];
		}

		$masters = [];
		foreach ($locator->get(MasterRepository::class)->getAll() as $master)
		{
			$masters[] = [
				'ID' => (int)$master['id'],
				'NAME' => (string)$master['name'],
				'SPECIALIZATION' => (string)$master['specialization'],
			];
		}

		$this->arResult = [
			'SERVICES' => $services,
			'MASTERS' => $masters,
			'WEEK_START' => date('Y-m-d', $this->getCurrentWeekStart()),
			'WEEKS_AHEAD' => self::WEEKS_AHEAD,
			'SESSID' => bitrix_sessid(),
			'AJAX_URL' => '/bitrix/services/main/ajax.php',
			'ACTION_SLOTS' => 'bxmax:booking.api.slots.list',
			'ACTION_BOOK' => 'bxmax:booking.api.bookings.create',
			'CONFIG' => [
				'months' => self::MONTHS,
				'weekdays' => self::WEEKDAYS,
				'errors' => [
					'SLOT_TAKEN' => 'Это время только что заняли. Выберите другое.',
					'SLOT_NOT_FOUND' => 'Слот больше недоступен — обновите расписание.',
					'MASTER_NOT_FOUND' => 'Мастер не найден, выберите другого.',
					'CONSENT_REQUIRED' => 'Отметьте согласие на обработку персональных данных.',
					'VALIDATION' => 'Проверьте, пожалуйста, имя и телефон.',
					'NETWORK' => 'Не удалось связаться со студией. Проверьте интернет и попробуйте ещё раз.',
				],
			],
		];

		$this->includeComponentTemplate();
	}

	/**
	 * Понедельник текущей недели — контракт API ждёт именно понедельник.
	 */
	private function getCurrentWeekStart(): int
	{
		$weekday = (int)date('N');

		return mktime(0, 0, 0, (int)date('n'), (int)date('j') - ($weekday - 1), (int)date('Y'));
	}
}
