<?php

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Query;
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
if ($right < 'R' || !Loader::includeModule($moduleId))
{
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}
$canEdit = $right >= 'W';

$serviceLocator = ServiceLocator::getInstance();
$catalog = $serviceLocator->get(CatalogRepository::class);
$entries = $serviceLocator->get(EntryRepository::class);

$masterNames = $catalog->getMasterNames();
$serviceNames = $catalog->getServiceNames();

$tableId = 'tbl_bxmax_booking_entries';
$sorting = new CAdminUiSorting($tableId, 'STARTS_AT', 'desc');
$list = new CAdminUiList($tableId, $sorting);

$filterFields = [
    [
        'id' => 'MASTER_ID',
        'name' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_MASTER'),
        'type' => 'list',
        'items' => ['' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_ANY')] + $masterNames,
        'default' => true,
    ],
    [
        'id' => 'STARTS_AT',
        'name' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_SLOT'),
        'type' => 'date',
        'default' => true,
    ],
    [
        'id' => 'SERVICE_ID',
        'name' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_SERVICE'),
        'type' => 'list',
        'items' => ['' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_ANY')] + $serviceNames,
    ],
    ['id' => 'NAME', 'name' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_NAME')],
    ['id' => 'PHONE', 'name' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_PHONE')],
];

$list->addHeaders([
    ['id' => 'ID', 'content' => 'ID', 'sort' => 'ID', 'default' => true],
    ['id' => 'MASTER_ID', 'content' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_MASTER'), 'sort' => 'MASTER_ID', 'default' => true],
    ['id' => 'STARTS_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_SLOT'), 'sort' => 'STARTS_AT', 'default' => true],
    ['id' => 'SERVICE_ID', 'content' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_SERVICE'), 'sort' => 'SERVICE_ID', 'default' => true],
    ['id' => 'NAME', 'content' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_NAME'), 'sort' => 'NAME', 'default' => true],
    ['id' => 'PHONE', 'content' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_PHONE'), 'default' => true],
    ['id' => 'CONSENT_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_CONSENT_AT'), 'sort' => 'CONSENT_AT', 'default' => true],
    ['id' => 'CREATED_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_CREATED_AT'), 'sort' => 'CREATED_AT', 'default' => false],
]);

$filter = (new Filter\Options($tableId))->getFilter($filterFields);
$applyFilter = static function (Query $query) use ($filter): Query {
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
        $query->whereLike('PHONE', '%' . preg_replace('/[^\d+]/', '', (string)$filter['PHONE']) . '%');
    }
    if (!empty($filter['FIND']))
    {
        $query->where(Query::filter()
            ->logic('or')
            ->whereLike('NAME', '%' . $filter['FIND'] . '%')
            ->whereLike('PHONE', '%' . $filter['FIND'] . '%'));
    }

    return $query;
};

// Групповые действия: удаление заявки освобождает слот.
if ($canEdit && ($ids = $list->GroupAction()))
{
    $bookingService = $serviceLocator->get(BookingService::class);
    if ($list->GetAction() === 'delete')
    {
        if ($list->IsGroupActionToAll())
        {
            $ids = array_column($applyFilter($entries->createListQuery())->setSelect(['ID'])->fetchAll(), 'ID');
        }
        foreach ($ids as $id)
        {
            $result = $bookingService->delete((int)$id);
            if (!$result->isSuccess())
            {
                $list->AddGroupError(implode('; ', $result->getErrorMessages()), $id);
            }
        }
    }
}

$query = $applyFilter($entries->createListQuery());

$sortableFields = ['ID' => 'ID', 'MASTER_ID' => 'SLOT.MASTER_ID', 'STARTS_AT' => 'SLOT.STARTS_AT', 'SERVICE_ID' => 'SERVICE_ID', 'NAME' => 'NAME', 'CONSENT_AT' => 'CONSENT_AT', 'CREATED_AT' => 'CREATED_AT'];
$sortBy = $sortableFields[strtoupper((string)$sorting->getField())] ?? 'SLOT.STARTS_AT';
$sortOrder = strtoupper((string)$sorting->getOrder()) === 'ASC' ? 'ASC' : 'DESC';
$query->setOrder([$sortBy => $sortOrder, 'ID' => 'DESC']);

$nav = $list->getPageNavigation('pages-bxmax-booking-entries');
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
while ($entry = $rows->fetch())
{
    $n++;
    if ($n > $pageSize)
    {
        break;
    }

    $row = $list->AddRow($entry['ID'], $entry);
    $masterId = (int)$entry['MASTER_ID'];
    $serviceId = (int)$entry['SERVICE_ID'];
    $row->AddViewField('MASTER_ID', htmlspecialcharsbx($masterNames[$masterId] ?? ('#' . $masterId)));
    $row->AddViewField('SERVICE_ID', htmlspecialcharsbx($serviceNames[$serviceId] ?? ('#' . $serviceId)));
    $row->AddViewField('STARTS_AT', $entry['STARTS_AT'] instanceof DateTime ? $entry['STARTS_AT']->format('d.m.Y H:i') : '');
    $row->AddViewField('NAME', htmlspecialcharsbx($entry['NAME']));
    $row->AddViewField('PHONE', htmlspecialcharsbx($entry['PHONE']));
    $row->AddViewField('CONSENT_AT', $entry['CONSENT_AT'] instanceof DateTime ? $entry['CONSENT_AT']->format('d.m.Y H:i:s') : '');

    if ($canEdit)
    {
        $row->AddActions([
            [
                'ICON' => 'delete',
                'TEXT' => Loc::getMessage('BXMAX_BOOKING_ENTRIES_DELETE'),
                'ACTION' => "if (confirm('" . CUtil::JSEscape(Loc::getMessage('BXMAX_BOOKING_ENTRIES_DELETE_CONFIRM')) . "')) "
                    . $list->ActionDoGroup($entry['ID'], 'delete'),
            ],
        ]);
    }
}

$nav->setRecordCount($nav->getOffset() + $n);
$list->setNavigation($nav, Loc::getMessage('BXMAX_BOOKING_ENTRIES_NAV'), false);

if ($canEdit)
{
    $list->AddGroupActionTable(['delete' => true]);
}

$list->AddAdminContextMenu([], false);
$list->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage('BXMAX_BOOKING_ENTRIES_TITLE'));

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$list->DisplayFilter($filterFields);
$list->DisplayList(['SHOW_COUNT_HTML' => true, 'ACTION_PANEL' => $canEdit ? null : false]);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
