<?php

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bxmax\Booking\Application\Service\CatalogService;
use Bxmax\Booking\Domain\Repository\EntryRepositoryInterface;
use Bxmax\Booking\Domain\Repository\SlotRepositoryInterface;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

global $APPLICATION, $USER;

if (!Loader::includeModule('bxmax.booking')) {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
    ShowError(Loc::getMessage('BXMAX_BOOKING_MODULE_NOT_INSTALLED'));
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
    die();
}

$moduleRight = $APPLICATION->GetGroupRight('bxmax.booking');
if ($moduleRight < 'R') {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

$canWrite = $moduleRight >= 'W' || $USER->IsAdmin();

$locator = ServiceLocator::getInstance();
/** @var EntryRepositoryInterface $entries */
$entries = $locator->get(EntryRepositoryInterface::class);
/** @var SlotRepositoryInterface $slots */
$slots = $locator->get(SlotRepositoryInterface::class);
/** @var CatalogService $catalog */
$catalog = $locator->get(CatalogService::class);

$masterNames = [];
foreach ($catalog->getMasters() as $master) {
    $masterNames[(int)$master['id']] = (string)$master['name'];
}
$serviceNames = [];
foreach ($catalog->getServices() as $service) {
    $serviceNames[(int)$service['id']] = (string)$service['name'];
}

$sTableID = 'bxmax_booking_entries';
$oSort = new CAdminSorting($sTableID, 'ID', 'desc');
$lAdmin = new CAdminList($sTableID, $oSort);

if ($canWrite && ($arID = $lAdmin->GroupAction())) {
    if ($_REQUEST['action_target'] === 'selected') {
        $arID = [];
        foreach ($entries->listAdmin('ID', 'DESC', 5000, 0) as $row) {
            $arID[] = (int)$row['ID'];
        }
    }
    foreach ($arID as $id) {
        $id = (int)$id;
        if ($id <= 0) {
            continue;
        }
        if (($_REQUEST['action'] ?? '') === 'delete') {
            $entries->delete($id);
        }
    }
}

$by = (string)$oSort->getField();
$order = strtoupper((string)$oSort->getOrder()) === 'ASC' ? 'ASC' : 'DESC';
$total = $entries->countAdmin();
$nav = new CAdminResult(null, $sTableID);
$nav->NavStart();
$navyPageSize = (int)$nav->GetNavParams()['nPageSize'];
$page = max(1, (int)($nav->NavPageNomer ?? 1));
$offset = ($page - 1) * $navyPageSize;
$rows = $entries->listAdmin($by, $order, $navyPageSize, $offset);

$lAdmin->AddHeaders([
    ['id' => 'ID', 'content' => 'ID', 'sort' => 'ID', 'default' => true],
    ['id' => 'MASTER', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_MASTER'), 'default' => true],
    ['id' => 'SLOT', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_SLOT'), 'sort' => 'SLOT_ID', 'default' => true],
    ['id' => 'SERVICE', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_SERVICE'), 'default' => true],
    ['id' => 'NAME', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_NAME'), 'sort' => 'NAME', 'default' => true],
    ['id' => 'PHONE', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_PHONE'), 'sort' => 'PHONE', 'default' => true],
    ['id' => 'CONSENT_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_CONSENT'), 'sort' => 'CONSENT_AT', 'default' => true],
]);

$rsData = new CAdminResult(new CDBResult(), $sTableID);
$rsData->NavStart();
$lAdmin->NavText('');

foreach ($rows as $row) {
    $slot = $slots->getById((int)$row['SLOT_ID']);
    $masterId = (int)($slot['MASTER_ID'] ?? 0);
    $slotLabel = '';
    if ($slot && isset($slot['STARTS_AT'])) {
        $slotLabel = (string)$slot['STARTS_AT'];
    }

    $item = $lAdmin->AddRow((int)$row['ID'], $row);
    $item->AddViewField('ID', (string)$row['ID']);
    $item->AddViewField('MASTER', htmlspecialcharsbx($masterNames[$masterId] ?? ('#' . $masterId)));
    $item->AddViewField('SLOT', htmlspecialcharsbx($slotLabel));
    $item->AddViewField('SERVICE', htmlspecialcharsbx($serviceNames[(int)$row['SERVICE_ID']] ?? ('#' . $row['SERVICE_ID'])));
    $item->AddViewField('NAME', htmlspecialcharsbx((string)$row['NAME']));
    $item->AddViewField('PHONE', htmlspecialcharsbx((string)$row['PHONE']));
    $item->AddViewField('CONSENT_AT', htmlspecialcharsbx((string)$row['CONSENT_AT']));

    if ($canWrite) {
        $actions = [[
            'ICON' => 'delete',
            'TEXT' => Loc::getMessage('BXMAX_BOOKING_ACTION_DELETE'),
            'ACTION' => "if(confirm('" . CUtil::JSEscape(Loc::getMessage('BXMAX_BOOKING_CONFIRM_DELETE')) . "')) " . $lAdmin->ActionDoGroup((int)$row['ID'], 'delete'),
        ]];
        $item->AddActions($actions);
    }
}

$lAdmin->AddFooter([
    ['title' => Loc::getMessage('MAIN_ADMIN_LIST_SELECTED'), 'value' => $total],
]);

if ($canWrite) {
    $lAdmin->AddGroupActionTable([
        'delete' => Loc::getMessage('MAIN_ADMIN_LIST_DELETE'),
    ]);
}

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage('BXMAX_BOOKING_ENTRIES_TITLE'));

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$lAdmin->DisplayList();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
