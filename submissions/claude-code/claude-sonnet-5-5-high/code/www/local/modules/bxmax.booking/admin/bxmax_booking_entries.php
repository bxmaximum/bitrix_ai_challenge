<?php

/**
 * Список заявок на запись.
 *
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

use Bitrix\Main\Context;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Filter;
use Bxmax\Booking\Model\EntryTable;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\EntryRepository;
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
/** @var EntryRepository $entries */
$entries = $locator->get(EntryRepository::class);
/** @var AdminService $adminService */
$adminService = $locator->get(AdminService::class);

$masters = array_column($catalog->getMasters(), 'name', 'id');
$services = array_column($catalog->getServices(), 'name', 'id');

$tableId = 'tbl_bxmax_booking_entries';
$sort = new CAdminUiSorting($tableId, 'ID', 'desc');
$list = new CAdminUiList($tableId, $sort);

$filterFields = [
    ['id' => 'MASTER_ID', 'name' => Loc::getMessage('BXMAX_BOOKING_COL_MASTER'), 'type' => 'list', 'items' => $masters, 'default' => true],
    ['id' => 'STARTS_AT', 'name' => Loc::getMessage('BXMAX_BOOKING_COL_SLOT'), 'type' => 'date', 'default' => true],
    ['id' => 'SERVICE_ID', 'name' => Loc::getMessage('BXMAX_BOOKING_COL_SERVICE'), 'type' => 'list', 'items' => $services, 'default' => true],
    ['id' => 'NAME', 'name' => Loc::getMessage('BXMAX_BOOKING_COL_NAME'), 'default' => false],
    ['id' => 'PHONE', 'name' => Loc::getMessage('BXMAX_BOOKING_COL_PHONE'), 'default' => false],
];

$list->addHeaders([
    ['id' => 'ID', 'content' => 'ID', 'sort' => 'ID', 'default' => true],
    ['id' => 'MASTER_ID', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_MASTER'), 'sort' => 'MASTER_ID', 'default' => true],
    ['id' => 'STARTS_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_SLOT'), 'sort' => 'STARTS_AT', 'default' => true],
    ['id' => 'SERVICE_ID', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_SERVICE'), 'sort' => 'SERVICE_ID', 'default' => true],
    ['id' => 'NAME', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_NAME'), 'sort' => 'NAME', 'default' => true],
    ['id' => 'PHONE', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_PHONE'), 'sort' => 'PHONE', 'default' => true],
    ['id' => 'CONSENT_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_CONSENT'), 'sort' => 'CONSENT_AT', 'default' => true],
    ['id' => 'CREATED_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_CREATED'), 'sort' => 'CREATED_AT', 'default' => false],
]);

if ($canWrite && ($idList = $list->GroupAction()))
{
    if ($list->IsGroupActionToAll())
    {
        $idList = array_column($entries->listQuery()->setSelect(['ID'])->fetchAll(), 'ID');
    }
    if (($_REQUEST['action'] ?? '') === 'delete')
    {
        foreach ($idList as $entryId)
        {
            $result = $adminService->deleteEntry((int)$entryId);
            if (!$result->isSuccess())
            {
                $list->AddGroupError(implode('; ', $result->getErrorMessages()), $entryId);
            }
        }
    }
}

$query = $entries->listQuery();

$sortBy = mb_strtoupper($sort->getField());
$allowedSort = ['ID', 'MASTER_ID', 'STARTS_AT', 'SERVICE_ID', 'NAME', 'PHONE', 'CONSENT_AT', 'CREATED_AT'];
if (!in_array($sortBy, $allowedSort, true))
{
    $sortBy = 'ID';
}
$sortOrder = mb_strtoupper($sort->getOrder()) === 'ASC' ? 'ASC' : 'DESC';
$query->setOrder([$sortBy => $sortOrder]);

$filterOption = new Filter\Options($tableId);
$filter = $filterOption->getFilter($filterFields);

if (!empty($filter['FIND']))
{
    $query->where(
        \Bitrix\Main\ORM\Query\Query::filter()
            ->logic('or')
            ->whereLike('NAME', '%' . $filter['FIND'] . '%')
            ->whereLike('PHONE', '%' . $filter['FIND'] . '%')
    );
}
if (!empty($filter['MASTER_ID']))
{
    $query->where('SLOT.MASTER_ID', (int)$filter['MASTER_ID']);
}
if (!empty($filter['SERVICE_ID']))
{
    $query->where('SERVICE_ID', (int)$filter['SERVICE_ID']);
}
if (!empty($filter['STARTS_AT_from']))
{
    $query->where('SLOT.STARTS_AT', '>=', new DateTime($filter['STARTS_AT_from']));
}
if (!empty($filter['STARTS_AT_to']))
{
    $query->where('SLOT.STARTS_AT', '<=', new DateTime($filter['STARTS_AT_to']));
}
if (!empty($filter['NAME']))
{
    $query->whereLike('NAME', '%' . $filter['NAME'] . '%');
}
if (!empty($filter['PHONE']))
{
    $query->whereLike('PHONE', '%' . $filter['PHONE'] . '%');
}

$nav = $list->getPageNavigation('bxmax-booking-entries');
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
while ($entry = $rows->fetch())
{
    $n++;
    if ($n > $pageSize)
    {
        break;
    }

    $row = $list->addRow($entry['ID'], $entry);
    $row->addViewField('MASTER_ID', htmlspecialcharsbx($masters[$entry['MASTER_ID']] ?? ('#' . $entry['MASTER_ID'])));
    $row->addViewField('SERVICE_ID', htmlspecialcharsbx($services[$entry['SERVICE_ID']] ?? ('#' . $entry['SERVICE_ID'])));
    $row->addViewField('STARTS_AT', htmlspecialcharsbx($entry['STARTS_AT']->format('d.m.Y H:i')));
    $row->addViewField('NAME', htmlspecialcharsbx($entry['NAME']));
    $row->addViewField('PHONE', htmlspecialcharsbx($entry['PHONE']));
    $row->addViewField('CONSENT_AT', htmlspecialcharsbx($entry['CONSENT_AT']->format('d.m.Y H:i:s')));
    $row->addViewField('CREATED_AT', htmlspecialcharsbx($entry['CREATED_AT']->format('d.m.Y H:i:s')));

    if ($canWrite)
    {
        $row->addActions([
            [
                'ICON' => 'delete',
                'TEXT' => Loc::getMessage('BXMAX_BOOKING_ACTION_DELETE'),
                'ACTION' => "if(confirm('" . CUtil::JSEscape(Loc::getMessage('BXMAX_BOOKING_CONFIRM_DELETE')) . "')) " . $list->actionDoGroup($entry['ID'], 'delete'),
            ],
        ]);
    }
}

if ($canWrite)
{
    $list->AddGroupActionTable(['delete' => true]);
}

$nav->setRecordCount($nav->getOffset() + $n);
$list->setNavigation($nav, Loc::getMessage('BXMAX_BOOKING_NAV'), false);
$list->AddAdminContextMenu([]);
$list->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage('BXMAX_BOOKING_ENTRIES_TITLE'));

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$list->DisplayFilter($filterFields);
$list->DisplayList(['SHOW_COUNT_HTML' => true]);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
