<?php

/**
 * Список заявок на онлайн-запись (админка Битрикса).
 *
 * Фильтр по мастеру и дате слота, удаление заявки с освобождением слота.
 */

use Bitrix\Main\Context;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Entity\EntryTable;
use Bxmax\Booking\Entity\SlotTable;
use Bxmax\Booking\Repository\EntryRepository;
use Bxmax\Booking\Repository\MasterRepository;
use Bxmax\Booking\Repository\ServiceRepository;

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
	$APPLICATION->AuthForm('Доступ к онлайн-записи закрыт');
}

$canWrite = $APPLICATION->GetGroupRight($moduleId) === 'W';
$request = Context::getCurrent()->getRequest();
$locator = ServiceLocator::getInstance();

$sTableID = 'bxmax_booking_entries';
$oSort = new CAdminSorting($sTableID, 'ID', 'DESC');
$lAdmin = new CAdminList($sTableID, $oSort);

$findMaster = (int)$request->get('find_master');
$findDate = (string)$request->get('find_date');
$findClient = trim((string)$request->get('find_client'));

$masterRepository = $locator->get(MasterRepository::class);
$serviceRepository = $locator->get(ServiceRepository::class);
$entryRepository = $locator->get(EntryRepository::class);

$masters = $masterRepository->getAll();
$services = $serviceRepository->getAll();

$filter = [];
if ($findMaster > 0)
{
	$filter['=MASTER_ID'] = $findMaster;
}
if ($findClient !== '')
{
	$filter['%NAME'] = $findClient;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $findDate))
{
	$fromTs = mktime(0, 0, 0, (int)substr($findDate, 5, 2), (int)substr($findDate, 8, 2), (int)substr($findDate, 0, 4));
	$slotIds = [];
	$slots = SlotTable::getList([
		'select' => ['ID'],
		'filter' => [
			'>=STARTS_AT' => DateTime::createFromTimestamp($fromTs),
			'<STARTS_AT' => DateTime::createFromTimestamp($fromTs + 86400),
		],
	]);
	while ($slot = $slots->fetch())
	{
		$slotIds[] = (int)$slot['ID'];
	}

	$filter['@SLOT_ID'] = $slotIds ?: [0];
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
			$all = EntryTable::getList(['select' => ['ID'], 'filter' => $filter]);
			while ($row = $all->fetch())
			{
				$actionIds[] = (int)$row['ID'];
			}
		}

		foreach ($actionIds as $entryId)
		{
			$entryId = (int)$entryId;
			if ($entryId <= 0)
			{
				continue;
			}

			try
			{
				if ($action === 'delete')
				{
					// удаление заявки освобождает слот: уникальный индекс снимается вместе со строкой
					$entryRepository->delete($entryId);
				}
			}
			catch (Throwable $exception)
			{
				$lAdmin->AddGroupError('Не удалось обработать заявку №' . $entryId . ': ' . $exception->getMessage(), $entryId);
			}
		}
	}
}

$APPLICATION->SetTitle('Заявки на онлайн-запись');

/* ---------------------------------------------------------------- выборка */

$sortBy = mb_strtoupper((string)$oSort->getField());
if (!EntryTable::getEntity()->hasField($sortBy))
{
	$sortBy = 'ID';
}

$sortOrder = mb_strtoupper((string)$oSort->getOrder()) === 'ASC' ? 'ASC' : 'DESC';

$rsData = EntryTable::getList([
	'select' => ['ID', 'SLOT_ID', 'MASTER_ID', 'SERVICE_ID', 'NAME', 'PHONE', 'CONSENT_AT', 'CREATED_AT'],
	'filter' => $filter,
	'order' => [$sortBy => $sortOrder],
	'count_total' => true,
]);

$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();

/* ------------------------------------------------------------------ шапка */

$lAdmin->AddHeaders([
	['id' => 'ID', 'content' => 'ID', 'sort' => 'ID', 'default' => true, 'align' => 'right'],
	['id' => 'MASTER', 'content' => 'Мастер', 'default' => true],
	['id' => 'SLOT', 'content' => 'Дата и время слота', 'default' => true],
	['id' => 'SERVICE', 'content' => 'Услуга', 'default' => true],
	['id' => 'NAME', 'content' => 'Имя', 'sort' => 'NAME', 'default' => true],
	['id' => 'PHONE', 'content' => 'Телефон', 'sort' => 'PHONE', 'default' => true],
	['id' => 'CONSENT_AT', 'content' => 'Согласие получено', 'sort' => 'CONSENT_AT', 'default' => true],
	['id' => 'CREATED_AT', 'content' => 'Создана', 'sort' => 'CREATED_AT', 'default' => false],
]);

/* --------------------------------------------------------------- данные строк */

$rows = [];
$slotIds = [];
while ($row = $rsData->NavNext(true, 'f_'))
{
	$rows[] = $row;
	$slotIds[] = (int)$row['SLOT_ID'];
}

$slotTimes = [];
if ($slotIds)
{
	$slots = SlotTable::getList([
		'select' => ['ID', 'STARTS_AT', 'ENDS_AT'],
		'filter' => ['@ID' => $slotIds],
	]);
	while ($slot = $slots->fetch())
	{
		$slotTimes[(int)$slot['ID']] = $slot;
	}
}

foreach ($rows as $row)
{
	$entryId = (int)$row['ID'];
	$masterName = $masters[(int)$row['MASTER_ID']]['name'] ?? '— мастер удалён —';
	$serviceName = $services[(int)$row['SERVICE_ID']]['name'] ?? '— услуга удалена —';

	$slot = $slotTimes[(int)$row['SLOT_ID']] ?? null;
	$slotLabel = '— слот удалён —';
	if ($slot !== null)
	{
		$slotLabel = $slot['STARTS_AT']->toString() . ' — ' . $slot['ENDS_AT']->format('H:i');
	}

	$listRow = $lAdmin->AddRow($entryId, $row);

	$listRow->AddViewField('ID', (string)$entryId);
	$listRow->AddViewField('MASTER', htmlspecialcharsbx($masterName));
	$listRow->AddViewField('SLOT', htmlspecialcharsbx($slotLabel));
	$listRow->AddViewField('SERVICE', htmlspecialcharsbx($serviceName));
	$listRow->AddViewField('NAME', htmlspecialcharsbx((string)$row['NAME']));
	$listRow->AddViewField('PHONE', '<a href="tel:' . htmlspecialcharsbx(preg_replace('/[^0-9+]/', '', (string)$row['PHONE'])) . '">' . htmlspecialcharsbx((string)$row['PHONE']) . '</a>');
	$listRow->AddViewField('CONSENT_AT', $row['CONSENT_AT'] ? $row['CONSENT_AT']->toString() : '—');
	$listRow->AddViewField('CREATED_AT', $row['CREATED_AT'] ? $row['CREATED_AT']->toString() : '—');

	$rowActions = [];
	if ($canWrite)
	{
		$rowActions[] = [
			'ICON' => 'delete',
			'TEXT' => 'Удалить заявку',
			'ACTION' => 'if(confirm(\'Удалить заявку №' . $entryId . '? Слот снова станет свободным.\')) ' . $lAdmin->ActionDoGroup($entryId, 'delete'),
		];
	}

	$listRow->AddActions($rowActions);
}

$lAdmin->AddFooter([
	['title' => 'Всего заявок', 'value' => $rsData->NavRecordCount],
]);

$lAdmin->AddGroupActionTable($canWrite ? ['delete' => true] : []);
$lAdmin->AddAdminContextMenu([], false);
$lAdmin->CheckListMode();

$filterRows = ['find_master' => 'Мастер', 'find_date' => 'Дата слота', 'find_client' => 'Имя клиента'];
$oFilter = new CAdminFilter($sTableID . '_filter', $filterRows);

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
		<td>Дата слота:</td>
		<td><input type="date" name="find_date" value="<?= htmlspecialcharsbx($findDate) ?>"></td>
	</tr>
	<tr>
		<td>Имя клиента:</td>
		<td><input type="text" name="find_client" size="30" value="<?= htmlspecialcharsbx($findClient) ?>"></td>
	</tr>
	<?php
	$oFilter->Buttons(['table_id' => $sTableID, 'url' => $APPLICATION->GetCurPage(), 'form' => 'find_form']);
	$oFilter->End();
	?>
</form>
<?php
$lAdmin->DisplayList();

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
