<?php

/**
 * Список слотов расписания с фильтром по мастеру и дате.
 * Подключается из /bitrix/admin/bxmax_booking_slot_list.php после prolog_admin_before.php.
 *
 * @global CMain $APPLICATION
 */

use Bitrix\Main\Application;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bxmax\Booking\Service\AdminService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CMain $APPLICATION */
global $APPLICATION;

Loader::includeModule('bxmax.booking');
Loc::loadMessages(__FILE__);

$moduleRight = $APPLICATION->GetGroupRight('bxmax.booking');
if ($moduleRight === 'D')
{
	$APPLICATION->AuthForm(Loc::getMessage('BXMAX_BOOKING_ADMIN_ACCESS_DENIED'));
}
$canWrite = $moduleRight >= 'W';

/** @var AdminService $adminService */
$adminService = ServiceLocator::getInstance()->get(AdminService::class);
$request = Application::getInstance()->getContext()->getRequest();

$sTableID = 'tbl_bxmax_booking_slot';
$oSort = new CAdminUiSorting($sTableID, 'STARTS_AT', 'ASC');
$lAdmin = new CAdminUiList($sTableID, $oSort);

$statusNames = [
	AdminService::SLOT_STATUS_FREE => Loc::getMessage('BXMAX_BOOKING_ADMIN_S_FREE'),
	AdminService::SLOT_STATUS_BOOKED => Loc::getMessage('BXMAX_BOOKING_ADMIN_S_BOOKED'),
	AdminService::SLOT_STATUS_CLOSED => Loc::getMessage('BXMAX_BOOKING_ADMIN_S_CLOSED'),
];

$filterFields = [
	[
		'id' => 'MASTER_ID',
		'name' => Loc::getMessage('BXMAX_BOOKING_ADMIN_F_MASTER'),
		'type' => 'list',
		'items' => ['' => Loc::getMessage('BXMAX_BOOKING_ADMIN_F_ANY')] + $adminService->getMasterOptions(),
		'filterable' => '',
		'default' => true,
	],
	[
		'id' => 'STARTS_AT',
		'name' => Loc::getMessage('BXMAX_BOOKING_ADMIN_F_DATE'),
		'type' => 'date',
		'filterable' => '',
		'default' => true,
	],
	[
		'id' => 'STATUS',
		'name' => Loc::getMessage('BXMAX_BOOKING_ADMIN_F_STATUS'),
		'type' => 'list',
		'items' => ['' => Loc::getMessage('BXMAX_BOOKING_ADMIN_F_ANY')] + $statusNames,
		'filterable' => '',
		'default' => true,
	],
];

$rawFilter = [];
$lAdmin->AddFilter($filterFields, $rawFilter);
$filter = AdminService::normalizeGridFilter($rawFilter, 'STARTS_AT');

if ($canWrite && check_bitrix_sessid() && ($ids = $lAdmin->GroupAction()))
{
	$action = $lAdmin->GetAction();

	if ($request->get('action_all_rows_' . $sTableID) === 'Y')
	{
		$ids = array_column($adminService->getSlots($filter, [], 10000, 0), 'ID');
	}

	if ($action === 'close' || $action === 'open')
	{
		foreach ($ids as $id)
		{
			$actionResult = $adminService->setSlotClosed((int)$id, $action === 'close');
			if (!$actionResult->isSuccess())
			{
				$lAdmin->AddGroupError(implode('; ', $actionResult->getErrorMessages()), $id);
			}
		}
	}
}

$nav = $lAdmin->getPageNavigation($sTableID);
$nav->setRecordCount($adminService->countSlots($filter));

$lAdmin->AddHeaders([
	['id' => 'ID', 'content' => Loc::getMessage('BXMAX_BOOKING_ADMIN_H_ID'), 'sort' => 'ID', 'default' => true, 'align' => 'right'],
	['id' => 'STARTS_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_ADMIN_H_SLOT'), 'sort' => 'STARTS_AT', 'default' => true],
	['id' => 'MASTER_NAME', 'content' => Loc::getMessage('BXMAX_BOOKING_ADMIN_H_MASTER'), 'default' => true],
	['id' => 'STATUS', 'content' => Loc::getMessage('BXMAX_BOOKING_ADMIN_H_STATUS'), 'default' => true],
	['id' => 'ENTRY_NAME', 'content' => Loc::getMessage('BXMAX_BOOKING_ADMIN_H_ENTRY'), 'default' => true],
]);

$allowedSort = ['ID', 'STARTS_AT', 'MASTER_ID'];
$sortField = in_array($oSort->getField(), $allowedSort, true) ? $oSort->getField() : 'STARTS_AT';
$order = [$sortField => mb_strtoupper($oSort->getOrder()) === 'DESC' ? 'DESC' : 'ASC'];

$rows = $adminService->getSlots($filter, $order, (int)$nav->getLimit(), (int)$nav->getOffset());
$lAdmin->setNavigation($nav, Loc::getMessage('BXMAX_BOOKING_ADMIN_NAV_SLOTS'), false);

foreach ($rows as $row)
{
	$listRow =& $lAdmin->AddRow((int)$row['ID'], $row);
	$listRow->AddViewField('STARTS_AT', htmlspecialcharsbx(
		$row['STARTS_AT']->format('d.m.Y H:i') . '–' . $row['ENDS_AT']->format('H:i')
	));
	$listRow->AddViewField('MASTER_NAME', htmlspecialcharsbx((string)$row['MASTER_NAME']));
	$listRow->AddViewField('STATUS', htmlspecialcharsbx((string)($statusNames[$row['STATUS']] ?? $row['STATUS'])));
	$listRow->AddViewField('ENTRY_NAME', htmlspecialcharsbx((string)($row['ENTRY_NAME'] ?? '')));

	if (!$canWrite)
	{
		continue;
	}

	$actions = [];
	if ($row['STATUS'] === AdminService::SLOT_STATUS_FREE)
	{
		$actions[] = [
			'ICON' => 'lock',
			'TEXT' => Loc::getMessage('BXMAX_BOOKING_ADMIN_A_CLOSE'),
			'ACTION' => $lAdmin->ActionDoGroup((int)$row['ID'], 'close', 'lang=' . LANGUAGE_ID),
			'DEFAULT' => true,
		];
	}
	elseif ($row['STATUS'] === AdminService::SLOT_STATUS_CLOSED)
	{
		$actions[] = [
			'ICON' => 'unlock',
			'TEXT' => Loc::getMessage('BXMAX_BOOKING_ADMIN_A_OPEN'),
			'ACTION' => $lAdmin->ActionDoGroup((int)$row['ID'], 'open', 'lang=' . LANGUAGE_ID),
			'DEFAULT' => true,
		];
	}
	else
	{
		$actions[] = [
			'ICON' => 'edit',
			'TEXT' => Loc::getMessage('BXMAX_BOOKING_ADMIN_A_GOTO_ENTRY'),
			'ACTION' => $lAdmin->ActionRedirect('bxmax_booking_entry_list.php?lang=' . LANGUAGE_ID),
			'DEFAULT' => true,
		];
	}

	$listRow->AddActions($actions);
}

if ($canWrite)
{
	$lAdmin->AddGroupActionTable([
		'close' => Loc::getMessage('BXMAX_BOOKING_ADMIN_A_CLOSE'),
		'open' => Loc::getMessage('BXMAX_BOOKING_ADMIN_A_OPEN'),
	]);
}

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage('BXMAX_BOOKING_ADMIN_SLOTS_TITLE'));

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$lAdmin->DisplayFilter($filterFields);
$lAdmin->DisplayList(['SHOW_COUNT_HTML' => true]);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
