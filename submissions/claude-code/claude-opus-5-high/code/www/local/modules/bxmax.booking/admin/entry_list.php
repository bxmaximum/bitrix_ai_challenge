<?php

/**
 * Список заявок на онлайн-запись.
 * Подключается из /bitrix/admin/bxmax_booking_entry_list.php после prolog_admin_before.php.
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

$sTableID = 'tbl_bxmax_booking_entry';
$oSort = new CAdminUiSorting($sTableID, 'STARTS_AT', 'DESC');
$lAdmin = new CAdminUiList($sTableID, $oSort);

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
		'id' => 'PHONE',
		'name' => Loc::getMessage('BXMAX_BOOKING_ADMIN_F_PHONE'),
		'filterable' => '%',
	],
];

$rawFilter = [];
$lAdmin->AddFilter($filterFields, $rawFilter);
$filter = AdminService::normalizeGridFilter($rawFilter, 'STARTS_AT');

if ($canWrite && check_bitrix_sessid() && ($ids = $lAdmin->GroupAction()))
{
	if ($request->get('action_all_rows_' . $sTableID) === 'Y')
	{
		$ids = array_column($adminService->getEntries($filter, [], 10000, 0), 'ID');
	}

	if ($lAdmin->GetAction() === 'delete')
	{
		foreach ($ids as $id)
		{
			$deleteResult = $adminService->deleteEntry((int)$id);
			if (!$deleteResult->isSuccess())
			{
				$lAdmin->AddGroupError(implode('; ', $deleteResult->getErrorMessages()), $id);
			}
		}
	}
}

$nav = $lAdmin->getPageNavigation($sTableID);
$nav->setRecordCount($adminService->countEntries($filter));

$lAdmin->AddHeaders([
	['id' => 'ID', 'content' => Loc::getMessage('BXMAX_BOOKING_ADMIN_H_ID'), 'sort' => 'ID', 'default' => true, 'align' => 'right'],
	['id' => 'STARTS_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_ADMIN_H_SLOT'), 'sort' => 'STARTS_AT', 'default' => true],
	['id' => 'MASTER_NAME', 'content' => Loc::getMessage('BXMAX_BOOKING_ADMIN_H_MASTER'), 'default' => true],
	['id' => 'SERVICE_NAME', 'content' => Loc::getMessage('BXMAX_BOOKING_ADMIN_H_SERVICE'), 'default' => true],
	['id' => 'NAME', 'content' => Loc::getMessage('BXMAX_BOOKING_ADMIN_H_NAME'), 'sort' => 'NAME', 'default' => true],
	['id' => 'PHONE', 'content' => Loc::getMessage('BXMAX_BOOKING_ADMIN_H_PHONE'), 'default' => true],
	['id' => 'CONSENT_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_ADMIN_H_CONSENT'), 'sort' => 'CONSENT_AT', 'default' => true],
	['id' => 'CREATED_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_ADMIN_H_CREATED'), 'sort' => 'CREATED_AT'],
]);

$allowedSort = ['ID', 'STARTS_AT', 'NAME', 'CONSENT_AT', 'CREATED_AT'];
$sortField = in_array($oSort->getField(), $allowedSort, true) ? $oSort->getField() : 'STARTS_AT';
$order = [$sortField => mb_strtoupper($oSort->getOrder()) === 'ASC' ? 'ASC' : 'DESC'];

$rows = $adminService->getEntries($filter, $order, (int)$nav->getLimit(), (int)$nav->getOffset());
$lAdmin->setNavigation($nav, Loc::getMessage('BXMAX_BOOKING_ADMIN_NAV'), false);

foreach ($rows as $row)
{
	$listRow =& $lAdmin->AddRow((int)$row['ID'], $row);
	$listRow->AddViewField('STARTS_AT', htmlspecialcharsbx(
		$row['STARTS_AT']->format('d.m.Y H:i') . '–' . $row['ENDS_AT']->format('H:i')
	));
	$listRow->AddViewField('MASTER_NAME', htmlspecialcharsbx((string)$row['MASTER_NAME']));
	$listRow->AddViewField('SERVICE_NAME', htmlspecialcharsbx((string)$row['SERVICE_NAME']));
	$listRow->AddViewField('NAME', htmlspecialcharsbx((string)$row['NAME']));
	$listRow->AddViewField('PHONE', htmlspecialcharsbx((string)$row['PHONE']));
	$listRow->AddViewField('CONSENT_AT', htmlspecialcharsbx($row['CONSENT_AT']->format('d.m.Y H:i:s')));
	$listRow->AddViewField('CREATED_AT', htmlspecialcharsbx($row['CREATED_AT']->format('d.m.Y H:i:s')));

	if ($canWrite)
	{
		$listRow->AddActions([
			[
				'ICON' => 'delete',
				'TEXT' => Loc::getMessage('BXMAX_BOOKING_ADMIN_A_DELETE'),
				'ACTION' => $lAdmin->ActionDoGroup((int)$row['ID'], 'delete', 'lang=' . LANGUAGE_ID),
			],
		]);
	}
}

if ($canWrite)
{
	$lAdmin->AddGroupActionTable(['delete' => Loc::getMessage('BXMAX_BOOKING_ADMIN_A_DELETE')]);
}

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage('BXMAX_BOOKING_ADMIN_ENTRIES_TITLE'));

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$lAdmin->DisplayFilter($filterFields);
$lAdmin->DisplayList(['SHOW_COUNT_HTML' => true]);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
