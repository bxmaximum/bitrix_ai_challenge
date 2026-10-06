<?php

/**
 * Список слотов расписания (админка Битрикса).
 *
 * Фильтр по мастеру и дате, закрытие и открытие слота: закрытый слот
 * недоступен для записи и показывается на лендинге как занятый.
 */

use Bitrix\Main\Context;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Entity\EntryTable;
use Bxmax\Booking\Entity\SlotTable;
use Bxmax\Booking\Repository\MasterRepository;
use Bxmax\Booking\Repository\ServiceRepository;
use Bxmax\Booking\Repository\SlotRepository;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

/** @var CMain $APPLICATION */

$moduleId = 'bxmax.booking';

if (!Loader::includeModule($moduleId))
{
	require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
	echo 'Модуль ' . htmlspecialcharsbx($moduleId) . ' не установлен.';
	require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';

	return;
}

if ($APPLICATION->GetGroupRight($moduleId) === 'D')
{
	$APPLICATION->AuthForm('Доступ к расписанию закрыт');
}

$canWrite = $APPLICATION->GetGroupRight($moduleId) === 'W';
$request = Context::getCurrent()->getRequest();
$locator = ServiceLocator::getInstance();

$sTableID = 'bxmax_booking_slots';
$oSort = new CAdminSorting($sTableID, 'STARTS_AT', 'ASC');
$lAdmin = new CAdminList($sTableID, $oSort);

$findMaster = (int)$request->get('find_master');
$findDate = trim((string)$request->get('find_date'));
$findStatus = trim((string)$request->get('find_status'));

if ($findDate === '' && $request->get('set_filter') === null)
{
	$findDate = date('Y-m-d');
}

$slotRepository = $locator->get(SlotRepository::class);
$masterRepository = $locator->get(MasterRepository::class);
$serviceRepository = $locator->get(ServiceRepository::class);

$masters = $masterRepository->getAll();
$services = $serviceRepository->getAll();

$filter = [];
if ($findMaster > 0)
{
	$filter['=MASTER_ID'] = $findMaster;
}

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $findDate))
{
	$fromTs = mktime(0, 0, 0, (int)substr($findDate, 5, 2), (int)substr($findDate, 8, 2), (int)substr($findDate, 0, 4));
	$filter['>=STARTS_AT'] = DateTime::createFromTimestamp($fromTs);
	$filter['<STARTS_AT'] = DateTime::createFromTimestamp($fromTs + 86400);
}

if ($findStatus === 'closed')
{
	$filter['=CLOSED'] = SlotTable::CLOSED_YES;
}
elseif ($findStatus === 'open')
{
	$filter['=CLOSED'] = SlotTable::CLOSED_NO;
}

/* ------------------------------------------------------------------ действия */

if ($canWrite)
{
	$actionIds = $lAdmin->GroupAction();

	if (!empty($actionIds))
	{
		$action = (string)$request->get('action_button');

		if ($lAdmin->IsGroupActionToAll())
		{
			$actionIds = [];
			$all = SlotTable::getList(['select' => ['ID'], 'filter' => $filter]);
			while ($row = $all->fetch())
			{
				$actionIds[] = (int)$row['ID'];
			}
		}

		foreach ($actionIds as $slotId)
		{
			$slotId = (int)$slotId;
			if ($slotId <= 0)
			{
				continue;
			}

			try
			{
				if ($action === 'close')
				{
					$slotRepository->setClosed($slotId, true);
				}
				elseif ($action === 'open')
				{
					$slotRepository->setClosed($slotId, false);
				}
			}
			catch (Throwable $exception)
			{
				$lAdmin->AddGroupError('Не удалось изменить слот №' . $slotId . ': ' . $exception->getMessage(), $slotId);
			}
		}
	}
}

$APPLICATION->SetTitle('Расписание слотов');

/* ---------------------------------------------------------------- выборка */

$sortBy = mb_strtoupper((string)$oSort->getField());
if (!SlotTable::getEntity()->hasField($sortBy))
{
	$sortBy = 'STARTS_AT';
}

$sortOrder = mb_strtoupper((string)$oSort->getOrder()) === 'DESC' ? 'DESC' : 'ASC';

$rsData = SlotTable::getList([
	'select' => ['ID', 'MASTER_ID', 'STARTS_AT', 'ENDS_AT', 'CLOSED'],
	'filter' => $filter,
	'order' => [$sortBy => $sortOrder, 'ID' => 'ASC'],
	'count_total' => true,
]);

$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();

$lAdmin->AddHeaders([
	['id' => 'ID', 'content' => 'ID', 'sort' => 'ID', 'default' => true, 'align' => 'right'],
	['id' => 'MASTER', 'content' => 'Мастер', 'default' => true],
	['id' => 'STARTS_AT', 'content' => 'Дата и начало', 'sort' => 'STARTS_AT', 'default' => true],
	['id' => 'ENDS_AT', 'content' => 'Окончание', 'sort' => 'ENDS_AT', 'default' => true],
	['id' => 'STATUS', 'content' => 'Статус', 'default' => true],
	['id' => 'BOOKING', 'content' => 'Заявка', 'default' => true],
]);

$rows = [];
$slotIds = [];
while ($row = $rsData->NavNext(true, 'f_'))
{
	$rows[] = $row;
	$slotIds[] = (int)$row['ID'];
}

$booked = $slotRepository->mapBookedSlots($slotIds);
$bookingInfo = [];
if ($booked)
{
	$entries = EntryTable::getList([
		'select' => ['ID', 'SLOT_ID', 'SERVICE_ID', 'NAME', 'PHONE'],
		'filter' => ['@ID' => array_values($booked)],
	]);
	while ($entry = $entries->fetch())
	{
		$bookingInfo[(int)$entry['SLOT_ID']] = $entry;
	}
}

foreach ($rows as $row)
{
	$slotId = (int)$row['ID'];
	$isClosed = ($row['CLOSED'] ?? 'N') === 'Y';
	$entry = $bookingInfo[$slotId] ?? null;

	$status = $isClosed ? 'Закрыт администратором' : ($entry ? 'Занят заявкой' : 'Свободен');
	$booking = $entry
		? '№' . (int)$entry['ID'] . ' · ' . htmlspecialcharsbx((string)($services[(int)$entry['SERVICE_ID']]['name'] ?? 'услуга удалена'))
			. ' · ' . htmlspecialcharsbx((string)$entry['NAME']) . ' · ' . htmlspecialcharsbx((string)$entry['PHONE'])
		: '—';

	$listRow = $lAdmin->AddRow($slotId, $row);

	$listRow->AddViewField('ID', (string)$slotId);
	$listRow->AddViewField('MASTER', htmlspecialcharsbx($masters[(int)$row['MASTER_ID']]['name'] ?? '— мастер удалён —'));
	$listRow->AddViewField('STARTS_AT', $row['STARTS_AT']->format('d.m.Y H:i'));
	$listRow->AddViewField('ENDS_AT', $row['ENDS_AT']->format('H:i'));
	$listRow->AddViewField('STATUS', $status);
	$listRow->AddViewField('BOOKING', $booking);

	$rowActions = [];
	if ($canWrite)
	{
		if ($isClosed)
		{
			$rowActions[] = [
				'ICON' => 'edit',
				'TEXT' => 'Открыть слот',
				'ACTION' => $lAdmin->ActionDoGroup($slotId, 'open'),
			];
		}
		else
		{
			$rowActions[] = [
				'ICON' => 'edit',
				'TEXT' => 'Закрыть слот',
				'ACTION' => $lAdmin->ActionDoGroup($slotId, 'close'),
			];
		}
	}

	$listRow->AddActions($rowActions);
}

$lAdmin->AddFooter([
	['title' => 'Всего слотов', 'value' => $rsData->NavRecordCount],
]);

$lAdmin->AddGroupActionTable($canWrite ? ['close' => 'Закрыть выбранные', 'open' => 'Открыть выбранные'] : []);
$lAdmin->AddAdminContextMenu([], false);
$lAdmin->CheckListMode();

$oFilter = new CAdminFilter($sTableID . '_filter', [
	'find_master' => 'Мастер',
	'find_date' => 'Дата',
	'find_status' => 'Статус',
]);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
?>
<form name="find_form" method="get" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>">
	<?php
	$oFilter->Begin();
	?>
	<tr>
		<td>Мастер:</td>
		<td>
			<select name="find_master">
				<option value="0">— все мастера —</option>
				<?php foreach ($masters as $master): ?>
					<option value="<?= (int)$master['id'] ?>" <?= $findMaster === (int)$master['id'] ? 'selected' : '' ?>>
						<?= htmlspecialcharsbx($master['name']) ?>
					</option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr>
		<td>Дата:</td>
		<td><input type="date" name="find_date" value="<?= htmlspecialcharsbx($findDate) ?>"></td>
	</tr>
	<tr>
		<td>Статус:</td>
		<td>
			<select name="find_status">
				<option value="">— любой —</option>
				<option value="open" <?= $findStatus === 'open' ? 'selected' : '' ?>>Открыт</option>
				<option value="closed" <?= $findStatus === 'closed' ? 'selected' : '' ?>>Закрыт</option>
			</select>
		</td>
	</tr>
	<?php
	$oFilter->Buttons(['table_id' => $sTableID, 'url' => $APPLICATION->GetCurPage(), 'form' => 'find_form']);
	$oFilter->End();
	?>
</form>
<?php
$lAdmin->DisplayList();

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
