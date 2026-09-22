<?php declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$_SERVER['DOCUMENT_ROOT']=dirname(__DIR__,4); define('NO_KEEP_STATISTIC',true);define('NOT_CHECK_PERMISSIONS',true);
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('bxmax.booking');
use Bxmax\Booking\Model\SlotTable;
use Bxmax\Booking\Model\EntryTable;
use Bxmax\Booking\Service\BookingService;
use Bxmax\Booking\Service\ScheduleService;
use Bxmax\Booking\Service\CatalogService;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Type\DateTime;
function check(bool $ok,string $name):void{if(!$ok)throw new RuntimeException('FAIL '.$name);echo 'PASS '.$name.PHP_EOL;}
$sl=ServiceLocator::getInstance();$booking=$sl->get(BookingService::class);$schedule=$sl->get(ScheduleService::class);$catalog=$sl->get(CatalogService::class)->content();
check(count($catalog['SERVICES'])===8&&count($catalog['MASTERS'])===3,'8 services, 3 masters');
check(SlotTable::getCount()===420,'420 slots, 14 days x 10 hours x 3 masters');
check($schedule->generate()===0,'schedule rerun is idempotent');
$slot=SlotTable::getList(['filter'=>['>STARTS_AT'=>(new DateTime())->add('+1 day')],'order'=>['ID'=>'ASC'],'limit'=>1])->fetch();$id=(int)$slot['ID'];$service=(int)$catalog['SERVICES'][0]['ID'];
check($booking->setClosed($id,true)->isSuccess(),'admin closes slot');
$r=$booking->create($id,$service,'Проверка Закрытого','+79000000002',true);check(!$r->isSuccess()&&$r->getErrors()[0]->getCode()==='SLOT_TAKEN','closed slot rejects booking');
check($booking->setClosed($id,false)->isSuccess(),'admin reopens slot');
$r=$booking->create($id,$service,'Проверка Удаления','+79000000003',true);check($r->isSuccess(),'reopened slot accepts booking');$bid=(int)$r->getData()['bookingId'];
$row=EntryTable::getByPrimary($bid)->fetch();check($row['CONSENT_AT'] instanceof DateTime,'consent timestamp persisted');
// Bypass the service on purpose: UNIQUE(SLOT_ID) must still reject a second row.
$duplicateRejected=false;
try{$dupe=EntryTable::add(['SLOT_ID'=>$id,'SERVICE_ID'=>$service,'NAME'=>'Дубликат','PHONE'=>'+79000000004','CONSENT_AT'=>new DateTime()]);$duplicateRejected=!$dupe->isSuccess();}catch(\Bitrix\Main\DB\SqlQueryException){$duplicateRejected=true;}
check($duplicateRejected,'database UNIQUE rejects direct duplicate insert');
check($booking->delete($bid)->isSuccess(),'admin deletes booking');check(EntryTable::getCount(['=SLOT_ID'=>$id])===0,'delete releases slot');
foreach(EntryTable::getList(['filter'=>['@NAME'=>['Контракт Тест','Проверка Удаления','Тест Интерфейса']]]) as $entry)$booking->delete((int)$entry['ID']);
$events=\Bitrix\Main\Mail\Internal\EventTable::getList(['filter'=>['=EVENT_NAME'=>'BXMAX_BOOKING_NEW'],'order'=>['ID'=>'DESC']])->fetchAll();check(count($events)>0,'Bitrix mail events exist');
foreach($events as $e) echo 'MAIL '.$e['ID'].' status='.$e['SUCCESS_EXEC'].PHP_EOL;
echo 'DONE'.PHP_EOL;
