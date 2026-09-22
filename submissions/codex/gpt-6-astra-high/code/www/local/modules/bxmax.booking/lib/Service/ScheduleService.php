<?php declare(strict_types=1);
namespace Bxmax\Booking\Service;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\BookingRepository;
use Bxmax\Booking\Model\SlotTable;
final class ScheduleService
{
 public const TIMEZONE='Europe/Saratov';
 public function __construct(private readonly CatalogRepository $catalog,private readonly BookingRepository $bookings) {}
 public function list(int $masterId,string $weekStart): Result
 {
  $r=new Result();
  if (!$this->catalog->find('masters',$masterId)) return $r->addError(new Error('Мастер не найден','MASTER_NOT_FOUND'));
  $from=\DateTimeImmutable::createFromFormat('!Y-m-d',$weekStart,new \DateTimeZone(self::TIMEZONE));
  if (!$from||$from->format('Y-m-d')!==$weekStart||$from->format('N')!=='1') return $r->addError(new Error('Укажите понедельник в формате YYYY-MM-DD','VALIDATION'));
  return $r->setData(['slots'=>$this->bookings->week($masterId,DateTime::createFromPhp(\DateTime::createFromImmutable($from))->setDefaultTimeZone(),DateTime::createFromPhp(\DateTime::createFromImmutable($from->modify('+7 days')))->setDefaultTimeZone())]);
 }
 public function generate(): int
 {
  $count=0;
  $today=new \DateTimeImmutable('today',new \DateTimeZone(self::TIMEZONE));
  foreach ($this->catalog->all('masters') as $master) for($day=0;$day<14;$day++) for($hour=10;$hour<20;$hour++) {
   $start=$today->modify("+$day days")->setTime($hour,0);
   $at=DateTime::createFromPhp(\DateTime::createFromImmutable($start))->setDefaultTimeZone();
   if (SlotTable::getCount(['=MASTER_ID'=>(int)$master['ID'],'=STARTS_AT'=>$at])) continue;
   try {
    $r=SlotTable::add(['MASTER_ID'=>(int)$master['ID'],'STARTS_AT'=>$at,'ENDS_AT'=>DateTime::createFromPhp(\DateTime::createFromImmutable($start->modify('+1 hour')))]);
    if (!$r->isSuccess()) throw new \RuntimeException(implode('; ',$r->getErrorMessages()));
    $count++;
   } catch (\Bitrix\Main\DB\SqlQueryException $e) {
    if (!SlotTable::getCount(['=MASTER_ID'=>(int)$master['ID'],'=STARTS_AT'=>$at])) throw $e;
   }
  }
  return $count;
 }
 public static function agent(): string
 {
  \Bitrix\Main\DI\ServiceLocator::getInstance()->get(self::class)->generate();
  return '\\Bxmax\\Booking\\Service\\ScheduleService::agent();';
 }
}
