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
use Bxmax\Booking\Repository\BookingRepository;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Service\BookingService;

Loc::loadMessages(__FILE__);

$moduleRight = $APPLICATION->GetGroupRight(ADMIN_MODULE_NAME);
if ($moduleRight < 'R' || !Loader::includeModule(ADMIN_MODULE_NAME))
{
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

$canWrite = ($moduleRight >= 'W');

$locator = ServiceLocator::getInstance();
/** @var BookingService $bookingService */
$bookingService = $locator->get(BookingService::class);
/** @var BookingRepository $bookingRepository */
$bookingRepository = $locator->get(BookingRepository::class);
/** @var CatalogRepository $catalogRepository */
$catalogRepository = $locator->get(CatalogRepository::class);

$mastersMap = $catalogRepository->getMastersMap();
$servicesMap = $catalogRepository->getServicesMap();

$sTableID = 'tbl_bxmax_booking_entries';
$oSort = new CAdminSorting($sTableID, 'ID', 'desc');
$lAdmin = new CAdminList($sTableID, $oSort);

$filterFields = [
    'find_master_id',
    'find_service_id',
    'find_client',
];
$lAdmin->InitFilter($filterFields);

$request = Context::getCurrent()->getRequest();
$findMasterId = (int)($GLOBALS['find_master_id'] ?? $request->getQuery('find_master_id') ?? 0);
$findServiceId = (int)($GLOBALS['find_service_id'] ?? $request->getQuery('find_service_id') ?? 0);
$findClient = trim((string)($GLOBALS['find_client'] ?? $request->getQuery('find_client') ?? ''));

$arFilter = [];
if ($findMasterId > 0)
{
    $arFilter['=SLOT.MASTER_ID'] = $findMasterId;
}
if ($findServiceId > 0)
{
    $arFilter['=SERVICE_ID'] = $findServiceId;
}
if ($findClient !== '')
{
    $arFilter[] = [
        'LOGIC' => 'OR',
        ['%NAME' => $findClient],
        ['%PHONE' => $findClient],
    ];
}

// Обработка одиночного и группового удаления заявок
$arID = $lAdmin->GroupAction();
if ($canWrite && !empty($arID) && is_array($arID))
{
    $action = $lAdmin->GetAction();
    if ($lAdmin->IsGroupActionToAll())
    {
        $arID = [];
        foreach ($bookingRepository->listForAdmin($arFilter) as $item)
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

        if ($action === 'delete')
        {
            $delResult = $bookingService->deleteBooking($id);
            if (!$delResult->isSuccess())
            {
                $lAdmin->AddGroupError(implode('; ', $delResult->getErrorMessages()), $id);
            }
        }
    }
}

$lAdmin->AddHeaders([
    ['id' => 'ID', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_ID') ?: 'ID', 'sort' => 'ID', 'default' => true],
    ['id' => 'MASTER_ID', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_MASTER') ?: 'Мастер', 'sort' => 'MASTER_ID', 'default' => true],
    ['id' => 'STARTS_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_SLOT') ?: 'Дата и время слота', 'sort' => 'STARTS_AT', 'default' => true],
    ['id' => 'SERVICE_ID', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_SERVICE') ?: 'Услуга', 'sort' => 'SERVICE_ID', 'default' => true],
    ['id' => 'NAME', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_NAME') ?: 'Имя клиента', 'sort' => 'NAME', 'default' => true],
    ['id' => 'PHONE', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_PHONE') ?: 'Телефон', 'sort' => 'PHONE', 'default' => true],
    ['id' => 'CONSENT_AT', 'content' => Loc::getMessage('BXMAX_BOOKING_COL_CONSENT_AT') ?: 'Время согласия', 'sort' => 'CONSENT_AT', 'default' => true],
]);

$by = mb_strtoupper((string)$oSort->getField());
$order = mb_strtoupper((string)$oSort->getOrder());
$rows = $bookingRepository->listForAdmin($arFilter, [$by => $order]);

$rsData = new CDBResult();
$rsData->InitFromArray($rows);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart(20);

$lAdmin->NavText($rsData->GetNavPrint(Loc::getMessage('BXMAX_BOOKING_NAV_TITLE') ?: 'Заявки'));

while ($arRes = $rsData->Fetch())
{
    $rowId = (int)$arRes['ID'];
    $row = &$lAdmin->AddRow($rowId, $arRes);

    $masterId = (int)($arRes['MASTER_ID'] ?? 0);
    $masterTitle = $mastersMap[$masterId] ?? ('Мастер #' . $masterId);
    $row->AddViewField('MASTER_ID', htmlspecialcharsbx($masterTitle) . ' <span style="color:#888;">[#' . $masterId . ']</span>');

    $slotText = 'Слот #' . (int)$arRes['SLOT_ID'];
    if ($arRes['STARTS_AT'] instanceof DateTime)
    {
        $slotText = $arRes['STARTS_AT']->format('d.m.Y H:i');
        if ($arRes['ENDS_AT'] instanceof DateTime)
        {
            $slotText .= ' – ' . $arRes['ENDS_AT']->format('H:i');
        }
    }
    $row->AddViewField('STARTS_AT', '<strong>' . htmlspecialcharsbx($slotText) . '</strong> <span style="color:#888;">[#' . (int)$arRes['SLOT_ID'] . ']</span>');

    $serviceId = (int)$arRes['SERVICE_ID'];
    if (isset($servicesMap[$serviceId]))
    {
        $serviceLabel = $servicesMap[$serviceId]['name'] . ' (' . $servicesMap[$serviceId]['priceFormatted'] . ')';
    }
    else
    {
        $serviceLabel = 'Услуга #' . $serviceId;
    }
    $row->AddViewField('SERVICE_ID', htmlspecialcharsbx($serviceLabel));
    $row->AddViewField('NAME', htmlspecialcharsbx((string)$arRes['NAME']));
    $row->AddViewField('PHONE', htmlspecialcharsbx((string)$arRes['PHONE']));

    $consentText = $arRes['CONSENT_AT'] instanceof DateTime
        ? $arRes['CONSENT_AT']->format('d.m.Y H:i:s')
        : (string)$arRes['CONSENT_AT'];
    $row->AddViewField('CONSENT_AT', htmlspecialcharsbx($consentText));

    $arActions = [];
    if ($canWrite)
    {
        $arActions[] = [
            'ICON' => 'delete',
            'TEXT' => Loc::getMessage('BXMAX_BOOKING_ACT_DELETE') ?: 'Удалить заявку',
            'ACTION' => "if(confirm('" . CUtil::JSEscape(Loc::getMessage('BXMAX_BOOKING_ACT_DELETE_CONFIRM') ?: 'Удалить заявку?') . "')) "
                . $lAdmin->ActionDoGroup($rowId, 'delete'),
        ];
    }
    $row->AddActions($arActions);
}

if ($canWrite)
{
    $lAdmin->AddGroupActionTable([
        'delete' => Loc::getMessage('BXMAX_BOOKING_ACT_DELETE') ?: 'Удалить заявку (освободить слот)',
    ]);
}

$lAdmin->AddAdminContextMenu([
    [
        'TEXT' => Loc::getMessage('BXMAX_BOOKING_BTN_SLOTS') ?: 'Перейти к расписанию слотов',
        'LINK' => 'bxmax_booking_slots.php?lang=' . LANGUAGE_ID,
        'ICON' => 'btn_list',
    ],
]);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage('BXMAX_BOOKING_ENTRIES_TITLE') ?: 'Заявки на онлайн-запись');
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$oFilter = new CAdminFilter(
    $sTableID . '_filter',
    [
        Loc::getMessage('BXMAX_BOOKING_FILTER_MASTER') ?: 'Мастер',
        Loc::getMessage('BXMAX_BOOKING_FILTER_SERVICE') ?: 'Услуга',
        Loc::getMessage('BXMAX_BOOKING_FILTER_CLIENT') ?: 'Имя или телефон',
    ]
);
?>
<form name="find_form" method="get" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>">
    <?php $oFilter->Begin(); ?>
    <tr>
        <td><?= Loc::getMessage('BXMAX_BOOKING_FILTER_MASTER') ?: 'Мастер' ?>:</td>
        <td>
            <select name="find_master_id">
                <option value="0"><?= Loc::getMessage('BXMAX_BOOKING_FILTER_ALL') ?: '(все)' ?></option>
                <?php foreach ($mastersMap as $mId => $mName): ?>
                    <option value="<?= (int)$mId ?>"<?= $findMasterId === (int)$mId ? ' selected' : '' ?>>
                        <?= htmlspecialcharsbx($mName) ?> [#<?= (int)$mId ?>]
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('BXMAX_BOOKING_FILTER_SERVICE') ?: 'Услуга' ?>:</td>
        <td>
            <select name="find_service_id">
                <option value="0"><?= Loc::getMessage('BXMAX_BOOKING_FILTER_ALL') ?: '(все)' ?></option>
                <?php foreach ($servicesMap as $sId => $sData): ?>
                    <option value="<?= (int)$sId ?>"<?= $findServiceId === (int)$sId ? ' selected' : '' ?>>
                        <?= htmlspecialcharsbx($sData['name']) ?> (<?= htmlspecialcharsbx($sData['priceFormatted']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('BXMAX_BOOKING_FILTER_CLIENT') ?: 'Имя или телефон' ?>:</td>
        <td>
            <input type="text" name="find_client" size="40" value="<?= htmlspecialcharsbx($findClient) ?>">
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
