<?php

declare(strict_types=1);

define('ADMIN_MODULE_NAME', 'bxmax.booking');
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bxmax\Booking\Infrastructure\Repository\BookingRepository;
use Bxmax\Booking\Infrastructure\Repository\ContentRepository;
use Bxmax\Booking\Infrastructure\Repository\SlotRepository;
use Bxmax\Booking\Model\BookingEntryTable;

if (!Loader::includeModule('bxmax.booking') || CMain::GetGroupRight('bxmax.booking') < 'R')
{
    $APPLICATION->AuthForm('Доступ запрещён');
}

$right = CMain::GetGroupRight('bxmax.booking');
$request = Context::getCurrent()->getRequest();
$tableId = 'bxmax_booking_entries';
$list = new CAdminList($tableId);
$bookingRepository = ServiceLocator::getInstance()->get(BookingRepository::class);
$slotRepository = ServiceLocator::getInstance()->get(SlotRepository::class);
$contentRepository = ServiceLocator::getInstance()->get(ContentRepository::class);
$masters = $contentRepository->getMasters();
$services = $contentRepository->getServices();
$masterNames = array_column($masters, 'NAME', 'ID');
$serviceNames = array_column($services, 'NAME', 'ID');

$action = (string)$request->get('action');
$id = (int)$request->get('ID');
if ($action === 'delete' && $id > 0 && $right >= 'W' && check_bitrix_sessid())
{
    $bookingRepository->delete($id);
    LocalRedirect('/local/modules/bxmax.booking/admin/entries.php?lang=' . LANGUAGE_ID);
}

$filterMasterId = max(0, (int)$request->get('filter_master_id'));
$filterDate = trim((string)$request->get('filter_date'));
$items = [];
$rows = BookingEntryTable::query()->setSelect(['*'])->setOrder(['CONSENT_AT' => 'DESC'])->fetchAll();
foreach ($rows as $row)
{
    $slot = $slotRepository->find((int)$row['SLOT_ID']);
    if ($slot === null || ($filterMasterId > 0 && (int)$slot['MASTER_ID'] !== $filterMasterId))
    {
        continue;
    }
    if ($filterDate !== '' && $slot['STARTS_AT']->format('d.m.Y') !== $filterDate)
    {
        continue;
    }
    $items[] = [
        'ID' => (int)$row['ID'],
        'MASTER' => (string)($masterNames[(int)$slot['MASTER_ID']] ?? '—'),
        'SLOT' => $slot['STARTS_AT']->format('d.m.Y H:i'),
        'SERVICE' => (string)($serviceNames[(int)$row['SERVICE_ID']] ?? '—'),
        'NAME' => (string)$row['NAME'],
        'PHONE' => (string)$row['PHONE'],
        'CONSENT_AT' => $row['CONSENT_AT']->format('d.m.Y H:i:s'),
    ];
}

$list->AddHeaders([
    ['id' => 'ID', 'content' => 'ID', 'default' => true],
    ['id' => 'MASTER', 'content' => 'Мастер', 'default' => true],
    ['id' => 'SLOT', 'content' => 'Дата и время', 'default' => true],
    ['id' => 'SERVICE', 'content' => 'Услуга', 'default' => true],
    ['id' => 'NAME', 'content' => 'Имя', 'default' => true],
    ['id' => 'PHONE', 'content' => 'Телефон', 'default' => true],
    ['id' => 'CONSENT_AT', 'content' => 'Согласие получено', 'default' => true],
]);
$dbResult = new CDBResult();
$dbResult->InitFromArray($items);
$adminResult = new CAdminResult($dbResult, $tableId);
while ($item = $adminResult->Fetch())
{
    $row = $list->AddRow((string)$item['ID'], $item);
    if ($right >= 'W')
    {
        $row->AddActions([[
            'ICON' => 'delete',
            'TEXT' => 'Удалить',
            'ACTION' => "if(confirm('Удалить заявку и освободить слот?')) window.location='/local/modules/bxmax.booking/admin/entries.php?lang=" . LANGUAGE_ID . '&action=delete&ID=' . (int)$item['ID'] . '&' . bitrix_sessid_get() . "';",
        ]]);
    }
}
$list->CheckListMode();
$APPLICATION->SetTitle('Заявки «Лак&Точка»');
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$filter = new CAdminFilter($tableId . '_filter', ['Мастер', 'Дата слота']);
$filter->Begin();
?>
<tr>
    <td>Мастер:</td>
    <td><select name="filter_master_id"><option value="0">Все</option><?php foreach ($masters as $master): ?><option value="<?= (int)$master['ID'] ?>"<?= $filterMasterId === (int)$master['ID'] ? ' selected' : '' ?>><?= htmlspecialcharsbx($master['NAME']) ?></option><?php endforeach; ?></select></td>
</tr>
<tr><td>Дата слота:</td><td><?php echo CalendarDate('filter_date', htmlspecialcharsbx($filterDate), 'find_form', '10'); ?></td></tr>
<?php
$filter->Buttons(['table_id' => $tableId, 'url' => $APPLICATION->GetCurPage(), 'form' => 'find_form']);
$filter->End();
$list->DisplayList();
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
