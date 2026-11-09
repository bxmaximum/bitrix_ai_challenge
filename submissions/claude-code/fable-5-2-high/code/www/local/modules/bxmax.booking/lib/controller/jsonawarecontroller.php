<?php

declare(strict_types=1);

namespace Bxmax\Booking\Controller;

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\HttpRequest;

/**
 * Действия принимают параметры и формой, и телом application/json:
 * верхние ключи JSON становятся ещё одним источником аргументов.
 */
abstract class JsonAwareController extends Controller
{
	/**
	 * Только расширяет источники аргументов; побочных эффектов и проверок прав здесь нет
	 * (метод выполняется до фильтров Csrf и HttpMethod).
	 */
	protected function processBeforeAction(Action $action): bool
	{
		$request = $this->getRequest();
		if ($request instanceof HttpRequest && $request->isJson())
		{
			$request->decodeJson();

			$sources = $this->getSourceParametersList();
			$sources[] = $request->getJsonList()->toArray();
			$this->setSourceParametersList($sources);
		}

		return parent::processBeforeAction($action);
	}


	/**
	 * Параметры действий объявлены без типа: значение может прийти строкой из формы,
	 * числом или булевым из JSON, массивом из name[]=. Всё нескалярное считается пустым,
	 * чтобы сервис вернул ошибку контракта, а не ошибку связывания аргументов.
	 */
	protected function toString(mixed $value): string
	{
		return is_scalar($value) && !is_bool($value) ? trim((string)$value) : '';
	}
}
