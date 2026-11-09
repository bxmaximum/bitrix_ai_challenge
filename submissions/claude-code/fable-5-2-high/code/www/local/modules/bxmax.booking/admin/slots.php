<?php

use Bitrix\Main\Context;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Filter;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\SlotRepository;
use Bxmax\Booking\Service\ScheduleService;
use Bxmax\Booking\Service\SlotService;

/**
 * @global CMain $APPLICATION
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

$moduleId = 'bxmax.booking';
$right = $APPLICATION->GetGroupRight($moduleId);
if ($right < 'R')
{
	$APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}
$canWrite = $right >= 'W';

Loader::requireModule($moduleId);

$locator = ServiceLocator::getInstance();
/** @var CatalogRepository $catalog */
$catalog = $locator->get(CatalogRepository::class);
/** @var SlotRepository $slots */
$slots = $locator->get(SlotRepository::class);
/** @var SlotService $slotService */
$slotService = $locator->get(SlotService::class);

$request = Context::getCurrent()->getRequest();
$pageUrl = 'bxmax_booking_slots.php?lang=' . LANGUAGE_ID;

// Кнопка «Дополнить расписание»: тот же генератор, что у агента и при развёртывании.
if ($canWrite && $request->get('generate') === 'Y' && check_bitrix_sessid())
{
	$created = $locator->get(ScheduleService::class)->generate();
	LocalRedirect($pageUrl . '&generated=' . $created);
}

$tableId = 'tbl_bxmax_booking_slots';
$sort = new CAdminUiSorting($tableId, 'STARTS_AT', 'asc');
$list = new CAdminUiList($tableId, $sort);

$masterItems = [];
foreach ($catalog->getMasters() as $master)
{
	$masterItems[$master['id']] = $master['name'];
}

$stateItems = [
	'free' => Loc::getMessage('BXMAX_BOOKING_SLOTS_STATE_FREE'),
	'booked' => Loc::getMessage('BXMAX_BOOKING_SLOTS_STATE_BOOKED'),
	'closed' => Loc::getMessage('BXMAX_BOOKING_SLOTS_STATE_CLOSED'),
];

$filterFields = [
	[
		'id' => 'MASTER_ID',
		'name' => Loc::getMessage('BXMAX_BOOKING_SLOTS_MASTER'),
		'type' => 'list',
		'items' => $masterItems,
		'params' => ['multiple' => 'Y'],
		'default' => true,
	],
	[
		'id' => 'STARTS_AT',
		'name' => Loc::getMessage('BXMAX_BOOKING_SLOTS_DATE'),
		'type' => 'date',
		'default' => true,
	],
	[
		'id' => 'STATE',
		'name' => Loc::getMessage('BXMAX_BOOKING_SLOTS_STATE'),
		'type' => 'list',
		'items' => $stateItems,
		'default' => true,
	],
	[
		'id' => 'PERIOD',
		'name' => Loc::getMessage('BXMAX_BOOKING_SLOTS_PERIOD'),
		'type' => 'list',
		'items' => [
			'upcoming' => Loc::getMessage('BXMAX_BOOKING_SLOTS_PERIOD_UPCOMING'),
			'past' => Loc::getMessage('BXMAX_BOOKING_SLOTS_PERIOD_PAST'),
		],
		'default' => true,
	],
];

// По умолчанию администратор видит предстоящие слоты, а не историю с первого дня работы.
$filterPresets = [
	'upcoming' => [
		'name' => Loc::getMessage('BXMAX_BOOKING_SLOTS_PERIOD_UPCOMING'),
		'default' => true,
		'fields' => ['PERIOD' => 'upcoming'],
	],
	'closed' => [
		'name' => Loc::getMessage('BXMAX_BOOKING_SLOTS_STATE_CLOSED'),
		'fields' => ['STATE' => 'closed'],
	],
];
$list->setFilterPresets($filterPresets);

$list->AddHeaders([
	['id' => 'ID', 'content' => 'ID', 'sort' => 'ID', 'default' => true],
	['id' => 'MASTER', 'content' => Loc::getMessage('BXMAX_BOOKING_SLOTS_MASTER'), 'sort' => 'MASTER_ID', 'default' => true],
	['id' => 'DATE', 'content' => Loc::getMessage('BXMAX_BOOKING_SLOTS_DATE'), 'sort' => 'STARTS_AT', 'default' => true],
	['id' => 'TIME', 'content' => Loc::getMessage('BXMAX_BOOKING_SLOTS_TIME'), 'default' => true],
	['id' => 'STATE', 'content' => Loc::getMessage('BXMAX_BOOKING_SLOTS_STATE'), 'sort' => 'CLOSED', 'default' => true],
]);

// Сортировка — только по белому списку полей.
$sortMap = ['ID' => 'ID', 'MASTER_ID' => 'MASTER_ID', 'STARTS_AT' => 'STARTS_AT', 'CLOSED' => 'CLOSED'];
$sortField = $sortMap[mb_strtoupper((string)$sort->getField())] ?? 'STARTS_AT';
$sortOrder = mb_strtoupper((string)$sort->getOrder()) === 'DESC' ? 'DESC' : 'ASC';

$filterData = (new Filter\Options($tableId, $filterPresets))->getFilter($filterFields);
$filter = [];

if (!empty($filterData['MASTER_ID']))
{
	$filter['@MASTER_ID'] = array_map('intval', (array)$filterData['MASTER_ID']);
}
foreach (['from' => '>=', 'to' => '<='] as $suffix => $operator)
{
	$value = (string)($filterData['STARTS_AT_' . $suffix] ?? '');
	if ($value !== '' && DateTime::isCorrect($value))
	{
		$filter[$operator . 'STARTS_AT'] = new DateTime($value);
	}
}
switch ((string)($filterData['PERIOD'] ?? ''))
{
	case 'upcoming':
		$filter['>STARTS_AT'] = new DateTime();
		break;
	case 'past':
		$filter['<=STARTS_AT'] = new DateTime();
		break;
}
switch ((string)($filterData['STATE'] ?? ''))
{
	case 'free':
		$filter['=CLOSED'] = false;
		$filter['=ENTRY.ID'] = null;
		break;
	case 'booked':
		$filter['!=ENTRY.ID'] = null;
		break;
	case 'closed':
		$filter['=CLOSED'] = true;
		break;
}

// Действия «закрыть» / «открыть». CSRF проверяет GroupAction().
if ($canWrite && ($ids = $list->GroupAction()) && in_array($list->GetAction(), ['close', 'open'], true))
{
	if ($list->IsGroupActionToAll())
	{
		// «Для всех»: берём слоты по текущему фильтру, а не по отмеченным строкам.
		$ids = array_column($slots->getAdminList($filter, ['ID' => 'ASC'], 0, 1000)->fetchAll(), 'ID');
	}

	foreach ($ids as $id)
	{
		$result = $list->GetAction() === 'close'
			? $slotService->close((int)$id)
			: $slotService->open((int)$id);

		foreach ($result->getErrorMessages() as $message)
		{
			$list->AddGroupError($message, $id);
		}
	}
}

$nav = $list->getPageNavigation('pages-bxmax-booking-slots');
$rows = $slots->getAdminList($filter, [$sortField => $sortOrder, 'ID' => 'ASC'], $nav->getOffset(), $nav->getLimit());
$nav->setRecordCount($rows->getCount());

if ($list->isTotalCountRequest())
{
	$list->sendTotalCountResponse($rows->getCount());
}

$items = $rows->fetchAll();
$names = $catalog->getNames(array_column($items, 'MASTER_ID'));
$now = time();

foreach ($items as $item)
{
	$row = $list->AddRow($item['ID'], $item);

	$masterName = $names[(int)$item['MASTER_ID']] ?? Loc::getMessage('BXMAX_BOOKING_SLOTS_DELETED', ['#ID#' => (int)$item['MASTER_ID']]);
	$hasEntry = $item['ENTRY_ID'] !== null;
	$isClosed = $item['CLOSED'] === 'Y' || $item['CLOSED'] === true;
	$isPast = $item['STARTS_AT']->getTimestamp() <= $now;

	if ($isClosed)
	{
		$state = '<span style="color:#b3261e">' . Loc::getMessage('BXMAX_BOOKING_SLOTS_STATE_CLOSED') . '</span>';
	}
	elseif ($hasEntry)
	{
		$state = Loc::getMessage('BXMAX_BOOKING_SLOTS_STATE_BOOKED_BY', [
			'#ID#' => (int)$item['ENTRY_ID'],
			'#NAME#' => htmlspecialcharsbx((string)$item['ENTRY_NAME']),
		]);
	}
	else
	{
		$state = '<span style="color:#1d7a3e">' . Loc::getMessage('BXMAX_BOOKING_SLOTS_STATE_FREE') . '</span>';
	}
	if ($isPast)
	{
		$state .= ' <span style="color:#828b95">' . Loc::getMessage('BXMAX_BOOKING_SLOTS_PAST') . '</span>';
	}

	$row->AddViewField('MASTER', htmlspecialcharsbx($masterName));
	$row->AddViewField('DATE', htmlspecialcharsbx(FormatDate('j F Y, D', $item['STARTS_AT']->getTimestamp())));
	$row->AddViewField('TIME', $item['STARTS_AT']->format('H:i') . '–' . $item['ENDS_AT']->format('H:i'));
	$row->AddViewField('STATE', $state);

	if ($canWrite)
	{
		$actions = [];
		if ($isClosed)
		{
			$actions[] = [
				'TEXT' => Loc::getMessage('BXMAX_BOOKING_SLOTS_OPEN'),
				'ACTION' => $list->ActionDoGroup($item['ID'], 'open'),
			];
		}
		elseif (!$hasEntry)
		{
			$actions[] = [
				'TEXT' => Loc::getMessage('BXMAX_BOOKING_SLOTS_CLOSE'),
				'ACTION' => $list->ActionDoGroup($item['ID'], 'close'),
			];
		}
		else
		{
			$actions[] = [
				'TEXT' => Loc::getMessage('BXMAX_BOOKING_SLOTS_GO_ENTRIES'),
				'LINK' => 'bxmax_booking_entries.php?lang=' . LANGUAGE_ID,
			];
		}
		$row->AddActions($actions);
	}
}

if ($canWrite)
{
	$list->AddGroupActionTable([
		'close' => Loc::getMessage('BXMAX_BOOKING_SLOTS_CLOSE'),
		'open' => Loc::getMessage('BXMAX_BOOKING_SLOTS_OPEN'),
	]);
}

$list->setNavigation($nav, Loc::getMessage('BXMAX_BOOKING_SLOTS_NAV'), false);

$contextMenu = [];
if ($canWrite)
{
	$contextMenu[] = [
		'TEXT' => Loc::getMessage('BXMAX_BOOKING_SLOTS_GENERATE'),
		'TITLE' => Loc::getMessage('BXMAX_BOOKING_SLOTS_GENERATE_TITLE'),
		'LINK' => $pageUrl . '&generate=Y&' . bitrix_sessid_get(),
		'ICON' => 'btn_new',
	];
}
$list->AddAdminContextMenu($contextMenu);
$list->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage('BXMAX_BOOKING_SLOTS_TITLE'));

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

if ($request->get('generated') !== null)
{
	CAdminMessage::ShowNote(Loc::getMessage('BXMAX_BOOKING_SLOTS_GENERATED', ['#COUNT#' => (int)$request->get('generated')]));
}

$list->DisplayFilter($filterFields);
$list->DisplayList(['SHOW_COUNT_HTML' => true, 'ACTION_PANEL' => $canWrite ? null : false]);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
