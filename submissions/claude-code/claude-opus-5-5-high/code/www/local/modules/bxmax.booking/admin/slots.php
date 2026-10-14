<?php

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Filter;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\SlotRepository;
use Bxmax\Booking\Service\SlotService;

/**
 * @global CMain $APPLICATION
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

$moduleId = 'bxmax.booking';
$right = $APPLICATION->GetGroupRight($moduleId);
if ($right < 'R' || !Loader::includeModule($moduleId))
{
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}
$canEdit = $right >= 'W';

$serviceLocator = ServiceLocator::getInstance();
$slotRepository = $serviceLocator->get(SlotRepository::class);
$masterNames = $serviceLocator->get(CatalogRepository::class)->getMasterNames();

$tableId = 'tbl_bxmax_booking_slots';
$sorting = new CAdminUiSorting($tableId, 'STARTS_AT', 'asc');
$list = new CAdminUiList($tableId, $sorting);

$statusItems = [
    '' => Loc::getMessage('BXMAX_BOOKING_SLOTS_ANY'),
    'free' => Loc::getMessage('BXMAX_BOOKING_SLOTS_STATUS_FREE'),
    'booked' => Loc::getMessage('BXMAX_BOOKING_SLOTS_STATUS_BOOKED'),
    'closed' => Loc::getMessage('BXMAX_BOOKING_SLOTS_STATUS_CLOSED'),
];

$filterFields = [
    [
        'id' => 'MASTER_ID',
        'name' => Loc::getMessage('BXMAX_BOOKING_SLOTS_MASTER'),
        'type' => 'list',
        'items' => ['' => Loc::getMessage('BXMAX_BOOKING_SLOTS_ANY')] + $masterNames,
        'default' => true,
    ],
    [
        'id' => 'STARTS_AT',
        'name' => Loc::getMessage('BXMAX_BOOKING_SLOTS_DATE'),
        'type' => 'date',
        'default' => true,
    ],
    [
        'id' => 'STATUS',
        'name' => Loc::getMessage('BXMAX_BOOKING_SLOTS_STATUS'),
        'type' => 'list',
        'items' => $statusItems,
        'default' => true,
    ],
];

$list->addHeaders([
    ['id' => 'ID', 'content' => 'ID', 'sort' => 'ID', 'default' => true],
    ['id' => 'MASTER_ID', 'content' => Loc::getMessage('BXMAX_BOOKING_SLOTS_MASTER'), 'sort' => 'MASTER_ID', 'default' => true],
    ['id' => 'STARTS_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_SLOTS_STARTS_AT'), 'sort' => 'STARTS_AT', 'default' => true],
    ['id' => 'ENDS_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_SLOTS_ENDS_AT'), 'default' => true],
    ['id' => 'STATUS', 'content' => Loc::getMessage('BXMAX_BOOKING_SLOTS_STATUS'), 'default' => true],
]);

$filter = (new Filter\Options($tableId))->getFilter($filterFields);
$applyFilter = static function (Query $query) use ($filter): Query {
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
        case 'free':
            $query->where('IS_CLOSED', false)->whereNull('ENTRY.ID');
            break;
        case 'booked':
            $query->whereNotNull('ENTRY.ID');
            break;
        case 'closed':
            $query->where('IS_CLOSED', true);
            break;
    }

    return $query;
};

// Групповые действия: закрыть / открыть слот для записи.
if ($canEdit && ($ids = $list->GroupAction()))
{
    $action = $list->GetAction();
    if (in_array($action, ['close', 'open'], true))
    {
        if ($list->IsGroupActionToAll())
        {
            $ids = array_column($applyFilter($slotRepository->createListQuery())->setSelect(['ID'])->fetchAll(), 'ID');
        }

        $slotService = $serviceLocator->get(SlotService::class);
        $result = $action === 'close' ? $slotService->close($ids) : $slotService->open($ids);
        if (!$result->isSuccess())
        {
            $list->AddGroupError(implode('; ', $result->getErrorMessages()));
        }
    }
}

$query = $applyFilter($slotRepository->createListQuery());

$sortableFields = ['ID' => 'ID', 'MASTER_ID' => 'MASTER_ID', 'STARTS_AT' => 'STARTS_AT'];
$sortBy = $sortableFields[strtoupper((string)$sorting->getField())] ?? 'STARTS_AT';
$sortOrder = strtoupper((string)$sorting->getOrder()) === 'DESC' ? 'DESC' : 'ASC';
$query->setOrder([$sortBy => $sortOrder, 'MASTER_ID' => 'ASC']);

$nav = $list->getPageNavigation('pages-bxmax-booking-slots');
if ($list->isTotalCountRequest())
{
    $query->countTotal(true);
}
$query->setOffset($nav->getOffset());
$query->setLimit($nav->getLimit() + 1);

$rows = $query->exec();
if ($list->isTotalCountRequest())
{
    $list->sendTotalCountResponse($rows->getCount());
}

$n = 0;
$pageSize = $nav->getLimit();
while ($slot = $rows->fetch())
{
    $n++;
    if ($n > $pageSize)
    {
        break;
    }

    $isClosed = SlotRepository::toBool($slot['IS_CLOSED']);
    $entryId = (int)$slot['ENTRY_ID'];
    if ($isClosed)
    {
        $status = Loc::getMessage('BXMAX_BOOKING_SLOTS_STATUS_CLOSED');
    }
    elseif ($entryId > 0)
    {
        $status = Loc::getMessage('BXMAX_BOOKING_SLOTS_STATUS_BOOKED_ID', ['#ID#' => $entryId]);
    }
    else
    {
        $status = Loc::getMessage('BXMAX_BOOKING_SLOTS_STATUS_FREE');
    }
    if ($isClosed && $entryId > 0)
    {
        $status .= ' · ' . Loc::getMessage('BXMAX_BOOKING_SLOTS_STATUS_BOOKED_ID', ['#ID#' => $entryId]);
    }

    $row = $list->AddRow($slot['ID'], $slot);
    $masterId = (int)$slot['MASTER_ID'];
    $row->AddViewField('MASTER_ID', htmlspecialcharsbx($masterNames[$masterId] ?? ('#' . $masterId)));
    $row->AddViewField('STARTS_AT', $slot['STARTS_AT']->format('d.m.Y H:i'));
    $row->AddViewField('ENDS_AT', $slot['ENDS_AT']->format('H:i'));
    $row->AddViewField('STATUS', htmlspecialcharsbx($status));

    if ($canEdit)
    {
        $row->AddActions([
            $isClosed
                ? [
                    'ICON' => 'activate',
                    'TEXT' => Loc::getMessage('BXMAX_BOOKING_SLOTS_OPEN'),
                    'ACTION' => $list->ActionDoGroup($slot['ID'], 'open'),
                    'DEFAULT' => true,
                ]
                : [
                    'ICON' => 'deactivate',
                    'TEXT' => Loc::getMessage('BXMAX_BOOKING_SLOTS_CLOSE'),
                    'ACTION' => $list->ActionDoGroup($slot['ID'], 'close'),
                    'DEFAULT' => true,
                ],
        ]);
    }
}

$nav->setRecordCount($nav->getOffset() + $n);
$list->setNavigation($nav, Loc::getMessage('BXMAX_BOOKING_SLOTS_NAV'), false);

if ($canEdit)
{
    $list->AddGroupActionTable([
        'close' => Loc::getMessage('BXMAX_BOOKING_SLOTS_CLOSE'),
        'open' => Loc::getMessage('BXMAX_BOOKING_SLOTS_OPEN'),
    ]);
}

$list->AddAdminContextMenu([], false);
$list->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage('BXMAX_BOOKING_SLOTS_TITLE'));

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$list->DisplayFilter($filterFields);
$list->DisplayList(['SHOW_COUNT_HTML' => true, 'ACTION_PANEL' => $canEdit ? null : false]);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
