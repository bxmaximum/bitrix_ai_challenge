<?php

declare(strict_types=1);

define('ADMIN_MODULE_NAME', 'bxmax.booking');
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bxmax\Booking\Infrastructure\Repository\ContentRepository;
use Bxmax\Booking\Infrastructure\Repository\SlotRepository;
use Bxmax\Booking\Model\SlotTable;

if (!Loader::includeModule('bxmax.booking') || CMain::GetGroupRight('bxmax.booking') < 'R')
{
    $APPLICATION->AuthForm('Доступ запрещён');
}

$right = CMain::GetGroupRight('bxmax.booking');
$request = Context::getCurrent()->getRequest();
$tableId = 'bxmax_booking_slots';
$list = new CAdminList($tableId);
$slots = ServiceLocator::getInstance()->get(SlotRepository::class);
$content = ServiceLocator::getInstance()->get(ContentRepository::class);
$masters = $content->getMasters();
$masterNames = array_column($masters, 'NAME', 'ID');

$action = (string)$request->get('action');
$id = (int)$request->get('ID');
if (in_array($action, ['close', 'open'], true) && $id > 0 && $right >= 'W' && check_bitrix_sessid())
{
    $slots->setClosed($id, $action === 'close');
    LocalRedirect('/local/modules/bxmax.booking/admin/slots.php?lang=' . LANGUAGE_ID);
}

$filterMasterId = max(0, (int)$request->get('filter_master_id'));
$filterDate = trim((string)$request->get('filter_date'));
$query = SlotTable::query()->setSelect(['*'])->setOrder(['STARTS_AT' => 'ASC']);
if ($filterMasterId > 0)
{
    $query->where('MASTER_ID', $filterMasterId);
}
$rawRows = $query->fetchAll();
$bookedIds = $slots->findBookedSlotIds(array_column($rawRows, 'ID'));
$items = [];
foreach ($rawRows as $slot)
{
    if ($filterDate !== '' && $slot['STARTS_AT']->format('d.m.Y') !== $filterDate)
    {
        continue;
    }
    $slotId = (int)$slot['ID'];
    $taken = isset($bookedIds[$slotId]);
    $items[] = [
        'ID' => $slotId,
        'MASTER' => (string)($masterNames[(int)$slot['MASTER_ID']] ?? '—'),
        'STARTS_AT' => $slot['STARTS_AT']->format('d.m.Y H:i'),
        'ENDS_AT' => $slot['ENDS_AT']->format('H:i'),
        'STATUS' => $taken ? 'Занят' : ($slot['IS_CLOSED'] === 'Y' ? 'Закрыт' : 'Свободен'),
        'IS_CLOSED' => $slot['IS_CLOSED'],
    ];
}

$list->AddHeaders([
    ['id' => 'ID', 'content' => 'ID', 'default' => true],
    ['id' => 'MASTER', 'content' => 'Мастер', 'default' => true],
    ['id' => 'STARTS_AT', 'content' => 'Начало', 'default' => true],
    ['id' => 'ENDS_AT', 'content' => 'Окончание', 'default' => true],
    ['id' => 'STATUS', 'content' => 'Статус', 'default' => true],
]);
$dbResult = new CDBResult();
$dbResult->InitFromArray($items);
$adminResult = new CAdminResult($dbResult, $tableId);
while ($item = $adminResult->Fetch())
{
    $row = $list->AddRow((string)$item['ID'], $item);
    if ($right >= 'W')
    {
        $nextAction = $item['IS_CLOSED'] === 'Y' ? 'open' : 'close';
        $row->AddActions([[
            'TEXT' => $nextAction === 'open' ? 'Открыть слот' : 'Закрыть слот',
            'ACTION' => "window.location='/local/modules/bxmax.booking/admin/slots.php?lang=" . LANGUAGE_ID . '&action=' . $nextAction . '&ID=' . (int)$item['ID'] . '&' . bitrix_sessid_get() . "';",
        ]]);
    }
}
$list->CheckListMode();
$APPLICATION->SetTitle('Расписание «Лак&Точка»');
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
$filter = new CAdminFilter($tableId . '_filter', ['Мастер', 'Дата']);
$filter->Begin();
?>
<tr>
    <td>Мастер:</td>
    <td><select name="filter_master_id"><option value="0">Все</option><?php foreach ($masters as $master): ?><option value="<?= (int)$master['ID'] ?>"<?= $filterMasterId === (int)$master['ID'] ? ' selected' : '' ?>><?= htmlspecialcharsbx($master['NAME']) ?></option><?php endforeach; ?></select></td>
</tr>
<tr><td>Дата:</td><td><?php echo CalendarDate('filter_date', htmlspecialcharsbx($filterDate), 'find_form', '10'); ?></td></tr>
<?php
$filter->Buttons(['table_id' => $tableId, 'url' => $APPLICATION->GetCurPage(), 'form' => 'find_form']);
$filter->End();
$list->DisplayList();
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
