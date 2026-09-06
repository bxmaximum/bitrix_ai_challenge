<?php

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bxmax\Booking\Application\Service\CatalogService;
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
/** @var SlotRepositoryInterface $slots */
$slots = $locator->get(SlotRepositoryInterface::class);
/** @var CatalogService $catalog */
$catalog = $locator->get(CatalogService::class);

$masters = $catalog->getMasters();
$masterNames = [];
$masterRef = [];
$masterRefId = [];
foreach ($masters as $master) {
    $masterNames[(int)$master['id']] = (string)$master['name'];
    $masterRef[] = (string)$master['name'];
    $masterRefId[] = (int)$master['id'];
}

$sTableID = 'bxmax_booking_slots';
$oSort = new CAdminSorting($sTableID, 'STARTS_AT', 'asc');
$lAdmin = new CAdminList($sTableID, $oSort);

$filterFields = ['find_master_id', 'find_date'];
$lAdmin->InitFilter($filterFields);

$find_master_id = isset($find_master_id) ? (int)$find_master_id : 0;
$find_date = isset($find_date) ? trim((string)$find_date) : '';

$filterMaster = $find_master_id > 0 ? $find_master_id : null;
$dateFrom = null;
$dateTo = null;
if ($find_date !== '' && preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $find_date)) {
    $parsed = \DateTimeImmutable::createFromFormat('d.m.Y', $find_date);
    if ($parsed instanceof \DateTimeImmutable) {
        $dateFrom = $parsed->setTime(0, 0, 0);
        $dateTo = $dateFrom->modify('+1 day');
    }
} elseif ($find_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $find_date)) {
    $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $find_date);
    if ($parsed instanceof \DateTimeImmutable) {
        $dateFrom = $parsed->setTime(0, 0, 0);
        $dateTo = $dateFrom->modify('+1 day');
    }
}

if ($canWrite && ($arID = $lAdmin->GroupAction())) {
    $action = (string)($_REQUEST['action'] ?? '');
    if ($_REQUEST['action_target'] === 'selected') {
        $arID = [];
        foreach ($slots->listAdmin($filterMaster, $dateFrom, $dateTo, 'ID', 'ASC', 10000, 0) as $row) {
            $arID[] = (int)$row['ID'];
        }
    }
    foreach ($arID as $id) {
        $id = (int)$id;
        if ($id <= 0) {
            continue;
        }
        if ($action === 'close') {
            $slots->setClosed($id, true);
        } elseif ($action === 'open') {
            $slots->setClosed($id, false);
        }
    }
}

$by = (string)$oSort->getField();
$order = strtoupper((string)$oSort->getOrder()) === 'DESC' ? 'DESC' : 'ASC';
$total = $slots->countAdmin($filterMaster, $dateFrom, $dateTo);
$rows = $slots->listAdmin($filterMaster, $dateFrom, $dateTo, $by, $order, 500, 0);

$lAdmin->AddHeaders([
    ['id' => 'ID', 'content' => 'ID', 'sort' => 'ID', 'default' => true],
    ['id' => 'MASTER_ID', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_MASTER'), 'sort' => 'MASTER_ID', 'default' => true],
    ['id' => 'STARTS_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_START'), 'sort' => 'STARTS_AT', 'default' => true],
    ['id' => 'ENDS_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_END'), 'sort' => 'ENDS_AT', 'default' => true],
    ['id' => 'IS_CLOSED', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_CLOSED'), 'sort' => 'IS_CLOSED', 'default' => true],
]);

foreach ($rows as $row) {
    $id = (int)$row['ID'];
    $item = $lAdmin->AddRow($id, $row);
    $item->AddViewField('ID', (string)$id);
    $item->AddViewField('MASTER_ID', htmlspecialcharsbx($masterNames[(int)$row['MASTER_ID']] ?? ('#' . $row['MASTER_ID'])));
    $item->AddViewField('STARTS_AT', htmlspecialcharsbx((string)$row['STARTS_AT']));
    $item->AddViewField('ENDS_AT', htmlspecialcharsbx((string)$row['ENDS_AT']));
    $closed = ($row['IS_CLOSED'] ?? 'N') === 'Y';
    $item->AddViewField('IS_CLOSED', $closed ? Loc::getMessage('BXMAX_BOOKING_YES') : Loc::getMessage('BXMAX_BOOKING_NO'));

    if ($canWrite) {
        $actions = [];
        if ($closed) {
            $actions[] = [
                'TEXT' => Loc::getMessage('BXMAX_BOOKING_ACTION_OPEN'),
                'ACTION' => $lAdmin->ActionDoGroup($id, 'open'),
            ];
        } else {
            $actions[] = [
                'TEXT' => Loc::getMessage('BXMAX_BOOKING_ACTION_CLOSE'),
                'ACTION' => $lAdmin->ActionDoGroup($id, 'close'),
            ];
        }
        $item->AddActions($actions);
    }
}

$lAdmin->AddFooter([
    ['title' => Loc::getMessage('MAIN_ADMIN_LIST_SELECTED'), 'value' => $total],
]);

if ($canWrite) {
    $lAdmin->AddGroupActionTable([
        'close' => Loc::getMessage('BXMAX_BOOKING_ACTION_CLOSE'),
        'open' => Loc::getMessage('BXMAX_BOOKING_ACTION_OPEN'),
    ]);
}

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage('BXMAX_BOOKING_SLOTS_TITLE'));

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$filter = new CAdminFilter($sTableID . '_filter', [
    Loc::getMessage('BXMAX_BOOKING_FILTER_MASTER'),
    Loc::getMessage('BXMAX_BOOKING_FILTER_DATE'),
]);
?>
<form name="find_form" method="get" action="<?= $APPLICATION->GetCurPage() ?>">
    <?php $filter->Begin(); ?>
    <tr>
        <td><?= Loc::getMessage('BXMAX_BOOKING_FILTER_MASTER') ?>:</td>
        <td>
            <select name="find_master_id">
                <option value="0"><?= Loc::getMessage('BXMAX_BOOKING_FILTER_ALL') ?></option>
                <?php foreach ($masters as $master): ?>
                    <option value="<?= (int)$master['id'] ?>"<?= $find_master_id === (int)$master['id'] ? ' selected' : '' ?>>
                        <?= htmlspecialcharsbx((string)$master['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('BXMAX_BOOKING_FILTER_DATE') ?>:</td>
        <td><?php echo CalendarDate('find_date', $find_date, 'find_form', '10'); ?></td>
    </tr>
    <?php
    $filter->Buttons(['table_id' => $sTableID, 'url' => $APPLICATION->GetCurPage(), 'form' => 'find_form']);
    $filter->End();
    ?>
</form>
<?php
$lAdmin->DisplayList();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
