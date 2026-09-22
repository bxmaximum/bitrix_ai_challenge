<?php declare(strict_types=1);
namespace Bxmax\Booking\Admin;
use Bitrix\Main\Context;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Model\SlotTable;
use Bxmax\Booking\Model\EntryTable;
use Bxmax\Booking\Service\CatalogService;
use Bxmax\Booking\Service\BookingService;
final class Lists
{
 public static function prepare(string $kind): array
 {
  global $APPLICATION;
  $isSlots=$kind==='slots'; $id='bxmax_booking_'.$kind;
  $request=Context::getCurrent()->getRequest();
  $write=$APPLICATION->GetGroupRight('bxmax.booking')>='W';
  $sort=new \CAdminSorting($id,'ID','DESC'); $list=new \CAdminList($id,$sort);
  if ($request->isPost() && $request->getPost('operation')) {
   if (!$write||!check_bitrix_sessid()) $list->AddGroupError('Недостаточно прав или сессия истекла');
   else {
    $service=ServiceLocator::getInstance()->get(BookingService::class);
    $ids=$request->getPost('ID'); $ids=is_array($ids)?$ids:[$request->getPost('record_id')];
    foreach(array_slice($ids,0,100) as $recordId) {
     $recordId=(int)$recordId; if ($recordId<1) continue;
     $op=(string)$request->getPost('operation');
     $r=$isSlots&&in_array($op,['close','open'],true) ? $service->setClosed($recordId,$op==='close') : (!$isSlots&&$op==='delete' ? $service->delete($recordId) : null);
     if ($r&&!$r->isSuccess()) $list->AddGroupError(implode('; ',$r->getErrorMessages()));
    }
   }
  }
  $catalog=ServiceLocator::getInstance()->get(CatalogService::class)->content();
  $masters=array_column($catalog['MASTERS'],'NAME','ID'); $services=array_column($catalog['SERVICES'],'NAME','ID');
  $master=(int)$request->getQuery('master'); $date=(string)$request->getQuery('date');
  $filter=[]; $prefix=$isSlots?'':'SLOT.';
  if ($master>0) $filter['='.$prefix.'MASTER_ID']=$master;
  if (preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)) {
   $d=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);
   if ($d&&$d->format('Y-m-d')===$date) { $filter['>='.$prefix.'STARTS_AT']=(new DateTime($date.' 00:00:00','Y-m-d H:i:s',new \DateTimeZone('Europe/Saratov')))->setDefaultTimeZone(); $filter['<'.$prefix.'STARTS_AT']=(new DateTime($d->modify('+1 day')->format('Y-m-d').' 00:00:00','Y-m-d H:i:s',new \DateTimeZone('Europe/Saratov')))->setDefaultTimeZone(); }
  }
  $table=$isSlots?SlotTable::class:EntryTable::class;
  $select=$isSlots?['*']:['*','MASTER_ID'=>'SLOT.MASTER_ID','STARTS_AT'=>'SLOT.STARTS_AT','ENDS_AT'=>'SLOT.ENDS_AT'];
  $headers=[['id'=>'ID','content'=>'ID','default'=>true],['id'=>'MASTER_ID','content'=>'Мастер','default'=>true],['id'=>'STARTS_AT','content'=>'Начало (UTC+4)','default'=>true],['id'=>'ENDS_AT','content'=>'Окончание','default'=>true]];
  foreach ($isSlots?['CLOSED'=>'Состояние']:['SERVICE_ID'=>'Услуга','NAME'=>'Имя','PHONE'=>'Телефон','CONSENT_AT'=>'Согласие получено'] as $key=>$label) $headers[]=['id'=>$key,'content'=>$label,'default'=>true];
  $list->AddHeaders($headers);
  $data=new \CAdminResult($table::getList(['select'=>$select,'filter'=>$filter,'order'=>['ID'=>'DESC']]),$id);
  $data->NavStart(30); $list->NavText($data->GetNavPrint('Записи'));
  while ($item=$data->NavNext(true,'f_')) {
   $row=$list->AddRow($item['ID'],$item);
   foreach (['STARTS_AT','ENDS_AT','CONSENT_AT'] as $field) if (isset($item[$field])) { $dt=$item[$field]; if ($dt instanceof DateTime) $row->AddViewField($field,htmlspecialcharsbx((clone $dt)->setTimeZone(new \DateTimeZone('Europe/Saratov'))->format('d.m.Y H:i:s'))); }
   $row->AddViewField('MASTER_ID',htmlspecialcharsbx($masters[$item['MASTER_ID']]??('#'.$item['MASTER_ID'])));
   if (!$isSlots) $row->AddViewField('SERVICE_ID',htmlspecialcharsbx($services[$item['SERVICE_ID']]??('#'.$item['SERVICE_ID'])));
   else $row->AddViewField('CLOSED',$item['CLOSED']==='Y'?'Закрыт':(EntryTable::getCount(['=SLOT_ID'=>(int)$item['ID']])?'Занят':'Открыт'));
   if ($write) {
    $op=$isSlots?($item['CLOSED']==='Y'?'open':'close'):'delete';
    $label=$isSlots?($op==='open'?'Открыть':'Закрыть'):'Удалить заявку';
    $row->AddActions([['TEXT'=>$label,'ACTION'=>"bookingAdminAction(".(int)$item['ID'].",'".$op."')"]]);
   }
  }
  $list->CheckListMode();
  $APPLICATION->SetTitle($isSlots?'Расписание студии':'Заявки на запись');
  return compact('list','id','masters','master','date','write');
 }
 public static function display(array $view): void
 {
  global $APPLICATION;
  extract($view, EXTR_SKIP);
  echo '<form name="booking_filter" method="get">';
  $f=new \CAdminFilter($id.'_filter',['Мастер','Дата']); $f->Begin();
  echo '<tr><td>Мастер:</td><td><select name="master"><option value="">Все мастера</option>';
  foreach($masters as $key=>$name) echo '<option value="'.(int)$key.'"'.($master===$key?' selected':'').'>'.htmlspecialcharsbx($name).'</option>';
  echo '</select></td></tr><tr><td>Дата:</td><td><input type="date" name="date" value="'.htmlspecialcharsbx($date).'"></td></tr>';
  $f->Buttons(['table_id'=>$id,'url'=>$APPLICATION->GetCurPage(),'form'=>'booking_filter']); $f->End(); echo '</form>';
  $list->DisplayList();
  if ($write) { echo '<form id="booking-admin-action" method="post">'.bitrix_sessid_post().'<input type="hidden" name="record_id"><input type="hidden" name="operation"></form>';
   echo '<script>function bookingAdminAction(id,op){if(op==="delete"&&!confirm("Удалить заявку и освободить слот?"))return;const f=document.getElementById("booking-admin-action");f.elements.record_id.value=id;f.elements.operation.value=op;f.submit();}</script>';
  }

 }
}
