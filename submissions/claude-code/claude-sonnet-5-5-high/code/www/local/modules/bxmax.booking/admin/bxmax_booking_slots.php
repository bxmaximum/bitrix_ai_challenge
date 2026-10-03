<?php

/**
 * Список слотов расписания: закрытие и открытие слотов.
 *
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Filter;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\SlotRepository;
use Bxmax\Booking\Service\AdminService;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

const BXMAX_BOOKING_MODULE = 'bxmax.booking';

$right = $APPLICATION->GetGroupRight(BXMAX_BOOKING_MODULE);
if ($right < 'R' || !Loader::includeModule(BXMAX_BOOKING_MODULE))
{
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}
$canWrite = $right >= 'W';

$locator = ServiceLocator::getInstance();
/** @var CatalogRepository $catalog */
$catalog = $locator->get(CatalogRepository::class);
/** @var SlotRepository $slots */
$slots = $locator->get(SlotRepository::class);
/** @var AdminService $adminService */
$adminService = $locator->get(AdminService::class);

$masters = array_column($catalog->getMasters(), 'name', 'id');

$statuses = [
    'free' => Loc::getMessage('BXMAX_BOOKING_STATUS_FREE'),
    'booked' => Loc::getMessage('BXMAX_BOOKING_STATUS_BOOKED'),
    'closed' => Loc::getMessage('BXMAX_BOOKING_STATUS_CLOSED'),
];

$tableId = 'tbl_bxmax_booking_slots';
$sort = new CAdminUiSorting($tableId, 'STARTS_AT', 'asc');
$list = new CAdminUiList($tableId, $sort);

$filterFields = [
    ['id' => 'MASTER_ID', 'name' => Loc::getMessage('BXMAX_BOOKING_COL_MASTER'), 'type' => 'list', 'items' => $masters, 'default' => true],
    ['id' => 'STARTS_AT', 'name' => Loc::getMessage('BXMAX_BOOKING_COL_DATE'), 'type' => 'date', 'default' => true],
    ['id' => 'STATUS', 'name' => Loc::getMessage('BXMAX_BOOKING_COL_STATUS'), 'type' => 'list', 'items' => $statuses, 'default' => true],
];

$list->addHeaders([
    ['id' => 'ID', 'content' => 'ID', 'sort' => 'ID', 'default' => true],
    ['id' => 'MASTER_ID', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_MASTER'), 'sort' => 'MASTER_ID', 'default' => true],
    ['id' => 'STARTS_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_STARTS'), 'sort' => 'STARTS_AT', 'default' => true],
    ['id' => 'ENDS_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_ENDS'), 'sort' => 'ENDS_AT', 'default' => true],
    ['id' => 'STATUS', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_STATUS'), 'default' => true],
    ['id' => 'ENTRY_ID', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_ENTRY'), 'default' => true],
]);

if ($canWrite && ($idList = $list->GroupAction()))
{
    $action = $_REQUEST['action'] ?? '';
    if (in_array($action, ['close', 'open'], true))
    {
        if ($list->IsGroupActionToAll())
        {
            $idList = array_column($slots->listQuery()->setSelect(['ID'])->fetchAll(), 'ID');
        }
        foreach ($idList as $slotId)
        {
            $result = $adminService->setSlotClosed((int)$slotId, $action === 'close');
            if (!$result->isSuccess())
            {
                $list->AddGroupError(implode('; ', $result->getErrorMessages()), $slotId);
            }
        }
    }
}

$query = $slots->listQuery();

$sortBy = mb_strtoupper($sort->getField());
if (!in_array($sortBy, ['ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT'], true))
{
    $sortBy = 'STARTS_AT';
}
$sortOrder = mb_strtoupper($sort->getOrder()) === 'DESC' ? 'DESC' : 'ASC';
$query->setOrder([$sortBy => $sortOrder, 'ID' => 'ASC']);

$filterOption = new Filter\Options($tableId);
$filter = $filterOption->getFilter($filterFields);

if (!empty($filter['MASTER_ID']))
{
    $query->where('MASTER_ID', (int)$filter['MASTER_ID']);
}
if (!empty($filter['STARTS_AT_from']))
{
    $query->where('STARTS_AT', '>=', new DateTime($filter['STARTS_AT_from']));
}
if (!empty($filter['STARTS_AT_to']))
{
    $query->where('STARTS_AT', '<=', new DateTime($filter['STARTS_AT_to']));
}
switch ($filter['STATUS'] ?? '')
{
    case 'booked':
        $query->whereNotNull('ENTRY.ID');
        break;
    case 'closed':
        $query->where('IS_CLOSED', 'Y');
        break;
    case 'free':
        $query->where('IS_CLOSED', 'N')->whereNull('ENTRY.ID');
        break;
}

$nav = $list->getPageNavigation('bxmax-booking-slots');
$query->setOffset($nav->getOffset())->setLimit($nav->getLimit() + 1);
if ($list->isTotalCountRequest())
{
    $query->countTotal(true);
}

$rows = $query->exec();
if ($list->isTotalCountRequest())
{
    $list->sendTotalCountResponse($rows->getCount());
}

$n = 0;
$pageSize = $list->getNavSize();
while ($slot = $rows->fetch())
{
    $n++;
    if ($n > $pageSize)
    {
        break;
    }

    $isClosed = $slot['IS_CLOSED'] === 'Y';
    $status = $slot['ENTRY_ID'] !== null ? 'booked' : ($isClosed ? 'closed' : 'free');

    $row = $list->addRow($slot['ID'], $slot);
    $row->addViewField('MASTER_ID', htmlspecialcharsbx($masters[$slot['MASTER_ID']] ?? ('#' . $slot['MASTER_ID'])));
    $row->addViewField('STARTS_AT', htmlspecialcharsbx($slot['STARTS_AT']->format('d.m.Y H:i')));
    $row->addViewField('ENDS_AT', htmlspecialcharsbx($slot['ENDS_AT']->format('d.m.Y H:i')));
    $row->addViewField('STATUS', htmlspecialcharsbx($statuses[$status]));
    $row->addViewField('ENTRY_ID', $slot['ENTRY_ID'] !== null ? (int)$slot['ENTRY_ID'] : '');

    if ($canWrite)
    {
        $row->addActions([
            $isClosed
                ? ['ICON' => 'unlock', 'TEXT' => Loc::getMessage('BXMAX_BOOKING_ACTION_OPEN'), 'ACTION' => $list->actionDoGroup($slot['ID'], 'open')]
                : ['ICON' => 'lock', 'TEXT' => Loc::getMessage('BXMAX_BOOKING_ACTION_CLOSE'), 'ACTION' => $list->actionDoGroup($slot['ID'], 'close')],
        ]);
    }
}

if ($canWrite)
{
    $list->AddGroupActionTable([
        'close' => Loc::getMessage('BXMAX_BOOKING_ACTION_CLOSE'),
        'open' => Loc::getMessage('BXMAX_BOOKING_ACTION_OPEN'),
    ]);
}

$nav->setRecordCount($nav->getOffset() + $n);
$list->setNavigation($nav, Loc::getMessage('BXMAX_BOOKING_NAV'), false);
$list->AddAdminContextMenu([]);
$list->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage('BXMAX_BOOKING_SLOTS_TITLE'));

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$list->DisplayFilter($filterFields);
$list->DisplayList(['SHOW_COUNT_HTML' => true]);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
