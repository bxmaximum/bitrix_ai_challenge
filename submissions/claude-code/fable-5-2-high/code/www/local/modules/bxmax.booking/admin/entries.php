<?php

use Bitrix\Main\Context;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Filter;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\EntryRepository;
use Bxmax\Booking\Service\BookingService;

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
/** @var EntryRepository $entries */
$entries = $locator->get(EntryRepository::class);
/** @var BookingService $bookingService */
$bookingService = $locator->get(BookingService::class);

$request = Context::getCurrent()->getRequest();

$tableId = 'tbl_bxmax_booking_entries';
$sort = new CAdminUiSorting($tableId, 'STARTS_AT', 'asc');
$list = new CAdminUiList($tableId, $sort);

$masterItems = [];
foreach ($catalog->getMasters() as $master)
{
	$masterItems[$master['id']] = $master['name'];
}

$filterFields = [
	[
		'id' => 'MASTER_ID',
		'name' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_MASTER'),
		'type' => 'list',
		'items' => $masterItems,
		'params' => ['multiple' => 'Y'],
		'default' => true,
	],
	[
		'id' => 'STARTS_AT',
		'name' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_SLOT'),
		'type' => 'date',
		'default' => true,
	],
	[
		'id' => 'PHONE',
		'name' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_PHONE'),
		'default' => false,
	],
	[
		'id' => 'PERIOD',
		'name' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_PERIOD'),
		'type' => 'list',
		'items' => [
			'upcoming' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_PERIOD_UPCOMING'),
			'past' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_PERIOD_PAST'),
		],
		'default' => true,
	],
];

// По умолчанию — предстоящие визиты: с ними администратор работает каждый день.
$filterPresets = [
	'upcoming' => [
		'name' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_PERIOD_UPCOMING'),
		'default' => true,
		'fields' => ['PERIOD' => 'upcoming'],
	],
];
$list->setFilterPresets($filterPresets);

$list->AddHeaders([
	['id' => 'ID', 'content' => 'ID', 'sort' => 'ID', 'default' => true],
	['id' => 'MASTER', 'content' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_MASTER'), 'sort' => 'MASTER_ID', 'default' => true],
	['id' => 'SLOT', 'content' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_SLOT'), 'sort' => 'STARTS_AT', 'default' => true],
	['id' => 'SERVICE', 'content' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_SERVICE'), 'sort' => 'SERVICE_ID', 'default' => true],
	['id' => 'NAME', 'content' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_NAME'), 'sort' => 'NAME', 'default' => true],
	['id' => 'PHONE', 'content' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_PHONE'), 'default' => true],
	['id' => 'CONSENT_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_CONSENT_AT'), 'sort' => 'CONSENT_AT', 'default' => true],
]);

// Сортировка — только по белому списку полей.
$sortMap = [
	'ID' => 'ID',
	'MASTER_ID' => 'SLOT.MASTER_ID',
	'STARTS_AT' => 'SLOT.STARTS_AT',
	'SERVICE_ID' => 'SERVICE_ID',
	'NAME' => 'NAME',
	'CONSENT_AT' => 'CONSENT_AT',
];
$sortField = $sortMap[mb_strtoupper((string)$sort->getField())] ?? 'SLOT.STARTS_AT';
$sortOrder = mb_strtoupper((string)$sort->getOrder()) === 'DESC' ? 'DESC' : 'ASC';

$filterData = (new Filter\Options($tableId, $filterPresets))->getFilter($filterFields);
$filter = [];

if (!empty($filterData['MASTER_ID']))
{
	$filter['@SLOT.MASTER_ID'] = array_map('intval', (array)$filterData['MASTER_ID']);
}
foreach (['from' => '>=', 'to' => '<='] as $suffix => $operator)
{
	$value = (string)($filterData['STARTS_AT_' . $suffix] ?? '');
	if ($value !== '' && DateTime::isCorrect($value))
	{
		$filter[$operator . 'SLOT.STARTS_AT'] = new DateTime($value);
	}
}
switch ((string)($filterData['PERIOD'] ?? ''))
{
	case 'upcoming':
		$filter['>SLOT.STARTS_AT'] = new DateTime();
		break;
	case 'past':
		$filter['<=SLOT.STARTS_AT'] = new DateTime();
		break;
}
if (!empty($filterData['PHONE']))
{
	$filter['%PHONE'] = preg_replace('/[^\d+]/', '', (string)$filterData['PHONE']);
}
if (trim((string)($filterData['FIND'] ?? '')) !== '')
{
	$find = trim((string)$filterData['FIND']);
	$filter[] = [
		'LOGIC' => 'OR',
		'%NAME' => $find,
		'%PHONE' => $find,
	];
}

// Действия: удаление заявки освобождает слот. CSRF проверяет GroupAction().
if ($canWrite && ($ids = $list->GroupAction()) && $list->GetAction() === 'delete')
{
	if ($list->IsGroupActionToAll())
	{
		// «Для всех»: берём заявки по текущему фильтру, а не по отмеченным строкам.
		$ids = array_column($entries->getAdminList($filter, ['ID' => 'ASC'], 0, 1000)->fetchAll(), 'ID');
	}

	foreach ($ids as $id)
	{
		$result = $bookingService->cancel((int)$id);
		foreach ($result->getErrorMessages() as $message)
		{
			$list->AddGroupError($message, $id);
		}
	}
}

$nav = $list->getPageNavigation('pages-bxmax-booking-entries');
$rows = $entries->getAdminList($filter, [$sortField => $sortOrder, 'ID' => 'ASC'], $nav->getOffset(), $nav->getLimit());
$nav->setRecordCount($rows->getCount());

if ($list->isTotalCountRequest())
{
	$list->sendTotalCountResponse($rows->getCount());
}

$items = $rows->fetchAll();
$names = $catalog->getNames(array_merge(
	array_column($items, 'MASTER_ID'),
	array_column($items, 'SERVICE_ID')
));

foreach ($items as $item)
{
	$row = $list->AddRow($item['ID'], $item);

	$masterName = $names[(int)$item['MASTER_ID']] ?? Loc::getMessage('BXMAX_BOOKING_ENTRIES_DELETED', ['#ID#' => (int)$item['MASTER_ID']]);
	$serviceName = $names[(int)$item['SERVICE_ID']] ?? Loc::getMessage('BXMAX_BOOKING_ENTRIES_DELETED', ['#ID#' => (int)$item['SERVICE_ID']]);

	$row->AddViewField('MASTER', htmlspecialcharsbx($masterName));
	$row->AddViewField('SERVICE', htmlspecialcharsbx($serviceName));
	$row->AddViewField(
		'SLOT',
		htmlspecialcharsbx(FormatDate('j F Y, D', $item['STARTS_AT']->getTimestamp()))
		. ', <b>' . $item['STARTS_AT']->format('H:i') . '–' . $item['ENDS_AT']->format('H:i') . '</b>'
	);
	$row->AddViewField('NAME', htmlspecialcharsbx($item['NAME']));
	$row->AddViewField('PHONE', '<a href="tel:' . htmlspecialcharsbx($item['PHONE']) . '">' . htmlspecialcharsbx($item['PHONE']) . '</a>');
	$row->AddViewField('CONSENT_AT', htmlspecialcharsbx($item['CONSENT_AT']->toString()));

	if ($canWrite)
	{
		$row->AddActions([
			[
				'ICON' => 'delete',
				'TEXT' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_DELETE'),
				'ACTION' => "if(confirm('" . CUtil::JSEscape(Loc::getMessage('BXMAX_BOOKING_ENTRIES_DELETE_CONFIRM')) . "')) " . $list->ActionDoGroup($item['ID'], 'delete'),
			],
		]);
	}
}

if ($canWrite)
{
	$list->AddGroupActionTable(['delete' => true]);
}

$list->setNavigation($nav, Loc::getMessage('BXMAX_BOOKING_ENTRIES_NAV'), false);
$list->AddAdminContextMenu([]);
$list->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage('BXMAX_BOOKING_ENTRIES_TITLE'));

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$list->DisplayFilter($filterFields);
$list->DisplayList(['SHOW_COUNT_HTML' => true, 'ACTION_PANEL' => $canWrite ? null : false]);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
