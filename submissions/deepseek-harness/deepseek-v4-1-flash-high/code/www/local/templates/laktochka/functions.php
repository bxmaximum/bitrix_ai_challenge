<?php

/**
 * Мелкие помощники шаблона лендинга «Лак&Точка».
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

function lt_e(string $value): string
{
	return htmlspecialcharsbx($value);
}

/**
 * URL файла шаблона с версией по времени изменения.
 *
 * Nginx на стенде отдаёт статику с Cache-Control: max-age=315360000, поэтому
 * без параметра версии браузер держит первую загруженную копию CSS/JS/картинки
 * практически вечно и правки на странице не видны.
 */
function lt_asset(string $path): string
{
	static $urls = [];

	$path = '/' . ltrim($path, '/');

	if (!isset($urls[$path]))
	{
		$file = __DIR__ . $path;
		$urls[$path] = SITE_TEMPLATE_PATH . $path . (is_file($file) ? '?v=' . filemtime($file) : '');
	}

	return $urls[$path];
}

/**
 * Орнамент-разделитель из референса: симметричный росчерк с ромбом.
 */
function lt_ornament(string $class = ''): string
{
	$class = trim('ornament ' . $class);

	return '<svg class="' . lt_e($class) . '" viewBox="0 0 64 20" fill="none" aria-hidden="true" focusable="false">'
		. '<path d="M2 10h19M43 10h19" stroke="currentColor" stroke-width="1" stroke-linecap="round"/>'
		. '<path d="M32 3.6 38.4 10 32 16.4 25.6 10z" stroke="currentColor" stroke-width="1"/>'
		. '<path d="M32 7.4 34.6 10 32 12.6 29.4 10z" fill="currentColor"/>'
		. '<path d="M22.4 10c-1.8-2.7-4.3-3.4-5.6-2.7-1.3.7-.8 2.6 1.2 3.2 1.6.5 3.2 0 4.4-.5zM41.6 10c1.8-2.7 4.3-3.4 5.6-2.7 1.3.7.8 2.6-1.2 3.2-1.6.5-3.2 0-4.4-.5z" fill="currentColor" opacity=".75"/>'
		. '</svg>';
}

function lt_price(int $value): string
{
	return number_format($value, 0, ',', ' ') . ' ₽';
}

function lt_plural(int $number, array $forms): string
{
	$number = abs($number) % 100;
	$last = $number % 10;

	if ($number > 10 && $number < 20)
	{
		return $forms[2];
	}

	return match ($last) {
		1 => $forms[0],
		2, 3, 4 => $forms[1],
		default => $forms[2],
	};
}

function lt_month(int $month, bool $genitive = true): string
{
	$nominative = ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'];
	$genitiveForms = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];

	$index = max(0, min(11, $month - 1));

	return $genitive ? $genitiveForms[$index] : $nominative[$index];
}

function lt_weekdays(): array
{
	return ['воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота'];
}

function lt_weekdays_short(): array
{
	return ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];
}

/**
 * «16 — 22 февраля» или «30 марта — 5 апреля».
 */
function lt_date_range(int $fromTs, int $toTs): string
{
	$fromDay = (int)date('j', $fromTs);
	$toDay = (int)date('j', $toTs);
	$fromMonth = lt_month((int)date('n', $fromTs));
	$toMonth = lt_month((int)date('n', $toTs));

	if ($fromMonth === $toMonth)
	{
		return $fromDay . ' — ' . $toDay . ' ' . $fromMonth;
	}

	return $fromDay . ' ' . $fromMonth . ' — ' . $toDay . ' ' . $toMonth;
}

function lt_day_label(int $ts): string
{
	$weekdays = lt_weekdays_short();

	return $weekdays[(int)date('w', $ts)] . ', ' . (int)date('j', $ts) . ' ' . lt_month((int)date('n', $ts));
}

/**
 * Понедельник недели, в которую попадает момент.
 */
function lt_monday(int $ts): int
{
	$weekday = (int)date('N', $ts); // 1 — понедельник

	return mktime(0, 0, 0, (int)date('n', $ts), (int)date('j', $ts) - ($weekday - 1), (int)date('Y', $ts));
}
