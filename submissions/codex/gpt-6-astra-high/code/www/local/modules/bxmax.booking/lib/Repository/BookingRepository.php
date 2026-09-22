<?php declare(strict_types=1);
namespace Bxmax\Booking\Repository;
use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Model\SlotTable;
use Bxmax\Booking\Model\EntryTable;
final class BookingRepository
{
 public function begin(): void { Application::getConnection()->startTransaction(); }
 public function commit(): void { Application::getConnection()->commitTransaction(); }
 public function rollback(): void { Application::getConnection()->rollbackTransaction(); }
 public function lockSlot(int $id): ?array
 {
  // ORM does not expose FOR UPDATE; lock by cast primary key, read typed fields via ORM.
  Application::getConnection()->query('SELECT ID FROM bxmax_booking_slot WHERE ID = '.(int)$id.' FOR UPDATE');
  return SlotTable::getByPrimary($id)->fetch() ?: null;
 }
 public function entryForSlot(int $id): ?array { return EntryTable::getList(['filter'=>['=SLOT_ID'=>$id],'limit'=>1])->fetch() ?: null; }
 public function add(array $fields): \Bitrix\Main\ORM\Data\AddResult { return EntryTable::add($fields); }
 public function week(int $master,DateTime $from,DateTime $until): array
 {
  $rows=SlotTable::getList(['filter'=>['=MASTER_ID'=>$master,'>=STARTS_AT'=>$from,'<STARTS_AT'=>$until],'order'=>['STARTS_AT'=>'ASC']])->fetchAll();
  $ids=array_column($rows,'ID');
  $taken=$ids ? array_column(EntryTable::getList(['select'=>['SLOT_ID'],'filter'=>['@SLOT_ID'=>$ids]])->fetchAll(),'SLOT_ID') : [];
  $occupied=array_fill_keys($taken,true);
  return array_map(static fn(array $s): array=>[
   'id'=>(int)$s['ID'],
   'startsAt'=>(clone $s['STARTS_AT'])->setTimeZone(new \DateTimeZone('Europe/Saratov'))->format('c'),
   'endsAt'=>(clone $s['ENDS_AT'])->setTimeZone(new \DateTimeZone('Europe/Saratov'))->format('c'),
   'status'=>($s['CLOSED']==='Y'||isset($occupied[$s['ID']])||$s['STARTS_AT']->getTimestamp()<=time())?'taken':'free',
  ],$rows);
 }
 public function deleteEntry(int $id): \Bitrix\Main\Result { return EntryTable::delete($id); }
 public function closeSlot(int $id,bool $closed): \Bitrix\Main\Result { return SlotTable::update($id,['CLOSED'=>$closed?'Y':'N']); }
}
