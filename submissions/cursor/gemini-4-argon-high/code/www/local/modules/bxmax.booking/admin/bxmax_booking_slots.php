<?php declare(strict_types=1);

const ADMIN_MODULE_NAME = 'bxmax.booking';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

/** @var CMain $APPLICATION */
/** @var CUser $USER */

use Bitrix\Main\Context;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\SlotRepository;
use Bxmax\Booking\Service\SlotService;

Loc::loadMessages(__FILE__);

$moduleRight = $APPLICATION->GetGroupRight(ADMIN_MODULE_NAME);
if ($moduleRight < 'R' || !Loader::includeModule(ADMIN_MODULE_NAME))
{
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

$canWrite = ($moduleRight >= 'W');

$locator = ServiceLocator::getInstance();
/** @var SlotService $slotService */
$slotService = $locator->get(SlotService::class);
/** @var SlotRepository $slotRepository */
$slotRepository = $locator->get(SlotRepository::class);
/** @var CatalogRepository $catalogRepository */
$catalogRepository = $locator->get(CatalogRepository::class);

$mastersMap = $catalogRepository->getMastersMap();

$sTableID = 'tbl_bxmax_booking_slots';
$oSort = new CAdminSorting($sTableID, 'STARTS_AT', 'asc');
$lAdmin = new CAdminList($sTableID, $oSort);

$filterFields = [
    'find_master_id',
    'find_date',
    'find_date_from',
    'find_date_to',
    'find_status',
];
$lAdmin->InitFilter($filterFields);

$request = Context::getCurrent()->getRequest();

if ($canWrite && $request->getQuery('gen_schedule') === 'Y' && check_bitrix_sessid())
{
    $slotService->generateScheduleForAllMasters(14);
    LocalRedirect('bxmax_booking_slots.php?lang=' . urlencode(LANGUAGE_ID));
}

$findMasterId = (int)($GLOBALS['find_master_id'] ?? $request->getQuery('find_master_id') ?? 0);
$findDate = trim((string)($GLOBALS['find_date'] ?? $request->getQuery('find_date') ?? ''));
$findDateFrom = trim((string)($GLOBALS['find_date_from'] ?? $request->getQuery('find_date_from') ?? ''));
$findDateTo = trim((string)($GLOBALS['find_date_to'] ?? $request->getQuery('find_date_to') ?? ''));
$findStatus = trim((string)($GLOBALS['find_status'] ?? $request->getQuery('find_status') ?? ''));

$parseDateInput = static function (string $input): ?\DateTimeImmutable {
    $input = trim($input);
    if ($input === '')
    {
        return null;
    }
    foreach (['!Y-m-d', '!d.m.Y'] as $fmt)
    {
        $dt = \DateTimeImmutable::createFromFormat($fmt, $input);
        if ($dt instanceof \DateTimeImmutable)
        {
            return $dt;
        }
    }
    return null;
};

$arFilter = [];
if ($findMasterId > 0)
{
    $arFilter['=MASTER_ID'] = $findMasterId;
}

$singleDay = $parseDateInput($findDate);
if ($singleDay !== null)
{
    $startPhp = $singleDay->setTime(0, 0, 0);
    $endPhp = $singleDay->setTime(23, 59, 59);
    $arFilter['>=STARTS_AT'] = DateTime::createFromPhp(\DateTime::createFromImmutable($startPhp));
    $arFilter['<=STARTS_AT'] = DateTime::createFromPhp(\DateTime::createFromImmutable($endPhp));
}
else
{
    $fromDay = $parseDateInput($findDateFrom);
    if ($fromDay !== null)
    {
        $arFilter['>=STARTS_AT'] = DateTime::createFromPhp(
            \DateTime::createFromImmutable($fromDay->setTime(0, 0, 0))
        );
    }
    $toDay = $parseDateInput($findDateTo);
    if ($toDay !== null)
    {
        $arFilter['<=STARTS_AT'] = DateTime::createFromPhp(
            \DateTime::createFromImmutable($toDay->setTime(23, 59, 59))
        );
    }
}

if ($findStatus === 'closed')
{
    $arFilter['=IS_CLOSED'] = 'Y';
}
elseif ($findStatus === 'booked')
{
    $arFilter['>ENTRY.ID'] = 0;
}
elseif ($findStatus === 'free')
{
    $arFilter['=IS_CLOSED'] = 'N';
    $arFilter['=ENTRY.ID'] = null;
}

// Обработка закрытия / открытия слотов
$arID = $lAdmin->GroupAction();
if ($canWrite && !empty($arID) && is_array($arID))
{
    $action = $lAdmin->GetAction();
    if ($lAdmin->IsGroupActionToAll())
    {
        $arID = [];
        foreach ($slotRepository->listForAdmin($arFilter) as $item)
        {
            $arID[] = (int)$item['ID'];
        }
    }

    foreach ($arID as $id)
    {
        $id = (int)$id;
        if ($id <= 0)
        {
            continue;
        }

        if ($action === 'close')
        {
            $res = $slotService->closeSlot($id);
            if (!$res->isSuccess())
            {
                $lAdmin->AddGroupError(implode('; ', $res->getErrorMessages()), $id);
            }
        }
        elseif ($action === 'open')
        {
            $res = $slotService->openSlot($id);
            if (!$res->isSuccess())
            {
                $lAdmin->AddGroupError(implode('; ', $res->getErrorMessages()), $id);
            }
        }
    }
}

$lAdmin->AddHeaders([
    ['id' => 'ID', 'content' => Loc::getMessage('BXMAX_BOOKING_SLOT_COL_ID') ?: 'ID', 'sort' => 'ID', 'default' => true],
    ['id' => 'MASTER_ID', 'content' => Loc::getMessage('BXMAX_BOOKING_SLOT_COL_MASTER') ?: 'Мастер', 'sort' => 'MASTER_ID', 'default' => true],
    ['id' => 'STARTS_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_SLOT_COL_STARTS_AT') ?: 'Начало', 'sort' => 'STARTS_AT', 'default' => true],
    ['id' => 'ENDS_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_SLOT_COL_ENDS_AT') ?: 'Окончание', 'sort' => 'ENDS_AT', 'default' => true],
    ['id' => 'STATUS', 'content' => Loc::getMessage('BXMAX_BOOKING_SLOT_COL_STATUS') ?: 'Статус на лендинге', 'default' => true],
    ['id' => 'IS_CLOSED', 'content' => Loc::getMessage('BXMAX_BOOKING_SLOT_COL_IS_CLOSED') ?: 'Закрыт вручную', 'sort' => 'IS_CLOSED', 'default' => true],
    ['id' => 'BOOKING', 'content' => Loc::getMessage('BXMAX_BOOKING_SLOT_COL_BOOKING') ?: 'Заявка', 'default' => true],
]);

$by = mb_strtoupper((string)$oSort->getField());
$order = mb_strtoupper((string)$oSort->getOrder());
$rows = $slotRepository->listForAdmin($arFilter, [$by => $order]);

$rsData = new CDBResult();
$rsData->InitFromArray($rows);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart(50);

$lAdmin->NavText($rsData->GetNavPrint(Loc::getMessage('BXMAX_BOOKING_SLOT_NAV_TITLE') ?: 'Слоты'));

while ($arRes = $rsData->Fetch())
{
    $rowId = (int)$arRes['ID'];
    $row = &$lAdmin->AddRow($rowId, $arRes);

    $masterId = (int)$arRes['MASTER_ID'];
    $masterName = $mastersMap[$masterId] ?? ('Мастер #' . $masterId);
    $row->AddViewField('MASTER_ID', htmlspecialcharsbx($masterName) . ' <span style="color:#888;">[#' . $masterId . ']</span>');

    $startsAtStr = $arRes['STARTS_AT'] instanceof DateTime
        ? $arRes['STARTS_AT']->format('d.m.Y H:i')
        : (string)$arRes['STARTS_AT'];
    $endsAtStr = $arRes['ENDS_AT'] instanceof DateTime
        ? $arRes['ENDS_AT']->format('d.m.Y H:i')
        : (string)$arRes['ENDS_AT'];

    $row->AddViewField('STARTS_AT', '<strong>' . htmlspecialcharsbx($startsAtStr) . '</strong>');
    $row->AddViewField('ENDS_AT', htmlspecialcharsbx($endsAtStr));

    $isClosed = ((string)$arRes['IS_CLOSED'] === 'Y');
    $entryId = (int)($arRes['ENTRY_ID'] ?? 0);

    if ($entryId > 0)
    {
        $statusHtml = '<span style="color:#b45309;font-weight:600;">'
            . (Loc::getMessage('BXMAX_BOOKING_SLOT_STATUS_TAKEN_ENTRY') ?: 'Занят (заявка)')
            . '</span>';
    }
    elseif ($isClosed)
    {
        $statusHtml = '<span style="color:#b91c1c;font-weight:600;">'
            . (Loc::getMessage('BXMAX_BOOKING_SLOT_STATUS_TAKEN_CLOSED') ?: 'Занят (закрыт админом)')
            . '</span>';
    }
    else
    {
        $statusHtml = '<span style="color:#15803d;font-weight:600;">'
            . (Loc::getMessage('BXMAX_BOOKING_SLOT_STATUS_FREE') ?: 'Свободен')
            . '</span>';
    }
    $row->AddViewField('STATUS', $statusHtml);
    $row->AddViewField('IS_CLOSED', $isClosed ? 'Да' : 'Нет');

    if ($entryId > 0)
    {
        $clientInfo = htmlspecialcharsbx((string)$arRes['ENTRY_NAME']) . ' (' . htmlspecialcharsbx((string)$arRes['ENTRY_PHONE']) . ')';
        $row->AddViewField(
            'BOOKING',
            '<a href="bxmax_booking_entries.php?lang=' . LANGUAGE_ID . '">Заявка #' . $entryId . '</a> — ' . $clientInfo
        );
    }
    else
    {
        $row->AddViewField('BOOKING', '—');
    }

    $arActions = [];
    if ($canWrite)
    {
        if ($isClosed)
        {
            $arActions[] = [
                'ICON' => 'edit',
                'DEFAULT' => true,
                'TEXT' => Loc::getMessage('BXMAX_BOOKING_SLOT_ACT_OPEN') ?: 'Открыть слот',
                'ACTION' => $lAdmin->ActionDoGroup($rowId, 'open'),
            ];
        }
        else
        {
            $arActions[] = [
                'ICON' => 'ban',
                'DEFAULT' => true,
                'TEXT' => Loc::getMessage('BXMAX_BOOKING_SLOT_ACT_CLOSE') ?: 'Закрыть слот',
                'ACTION' => $lAdmin->ActionDoGroup($rowId, 'close'),
            ];
        }
    }
    $row->AddActions($arActions);
}

if ($canWrite)
{
    $lAdmin->AddGroupActionTable([
        'close' => Loc::getMessage('BXMAX_BOOKING_SLOT_ACT_CLOSE') ?: 'Закрыть слот',
        'open' => Loc::getMessage('BXMAX_BOOKING_SLOT_ACT_OPEN') ?: 'Открыть слот',
    ]);
}

$contextMenu = [
    [
        'TEXT' => Loc::getMessage('BXMAX_BOOKING_SLOT_BTN_ENTRIES') ?: 'Список заявок',
        'LINK' => 'bxmax_booking_entries.php?lang=' . LANGUAGE_ID,
        'ICON' => 'btn_list',
    ],
];
if ($canWrite)
{
    $contextMenu[] = [
        'TEXT' => Loc::getMessage('BXMAX_BOOKING_SLOT_BTN_GENERATE') ?: 'Догенерировать слоты (14 дней)',
        'LINK' => 'bxmax_booking_slots.php?lang=' . LANGUAGE_ID . '&gen_schedule=Y&' . bitrix_sessid_get(),
        'ICON' => 'btn_new',
    ];
}
$lAdmin->AddAdminContextMenu($contextMenu);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage('BXMAX_BOOKING_SLOTS_TITLE') ?: 'Расписание слотов мастеров');
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$oFilter = new CAdminFilter(
    $sTableID . '_filter',
    [
        Loc::getMessage('BXMAX_BOOKING_SLOT_FILTER_MASTER') ?: 'Мастер',
        Loc::getMessage('BXMAX_BOOKING_SLOT_FILTER_DATE') ?: 'Дата',
        'Период дат',
        Loc::getMessage('BXMAX_BOOKING_SLOT_FILTER_STATUS') ?: 'Статус',
    ]
);
?>
<form name="find_form" method="get" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>">
    <?php $oFilter->Begin(); ?>
    <tr>
        <td><?= Loc::getMessage('BXMAX_BOOKING_SLOT_FILTER_MASTER') ?: 'Мастер' ?>:</td>
        <td>
            <select name="find_master_id">
                <option value="0"><?= Loc::getMessage('BXMAX_BOOKING_SLOT_FILTER_ALL') ?: '(все)' ?></option>
                <?php foreach ($mastersMap as $mId => $mName): ?>
                    <option value="<?= (int)$mId ?>"<?= $findMasterId === (int)$mId ? ' selected' : '' ?>>
                        <?= htmlspecialcharsbx($mName) ?> [#<?= (int)$mId ?>]
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('BXMAX_BOOKING_SLOT_FILTER_DATE') ?: 'Дата (ДД.ММ.ГГГГ или ГГГГ-ММ-ДД)' ?>:</td>
        <td>
            <input type="text" name="find_date" size="20" value="<?= htmlspecialcharsbx($findDate) ?>" placeholder="<?= date('Y-m-d') ?>">
        </td>
    </tr>
    <tr>
        <td>Период дат:</td>
        <td>
            <?= CalendarPeriod('find_date_from', htmlspecialcharsbx($findDateFrom), 'find_date_to', htmlspecialcharsbx($findDateTo), 'find_form', 'Y') ?>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('BXMAX_BOOKING_SLOT_FILTER_STATUS') ?: 'Статус' ?>:</td>
        <td>
            <select name="find_status">
                <option value=""><?= Loc::getMessage('BXMAX_BOOKING_SLOT_FILTER_ALL') ?: '(все)' ?></option>
                <option value="free"<?= $findStatus === 'free' ? ' selected' : '' ?>>Свободен</option>
                <option value="booked"<?= $findStatus === 'booked' ? ' selected' : '' ?>>Занят (есть заявка)</option>
                <option value="closed"<?= $findStatus === 'closed' ? ' selected' : '' ?>>Закрыт администратором</option>
            </select>
        </td>
    </tr>
    <?php
    $oFilter->Buttons([
        'table_id' => $sTableID,
        'url' => $APPLICATION->GetCurPage(),
        'form' => 'find_form',
    ]);
    $oFilter->End();
    ?>
</form>
<?php
$lAdmin->DisplayList();

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
