<?php declare(strict_types=1);
namespace Bxmax\Booking\Service;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\Config\Option;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\BookingRepository;
final class BookingService
{
 public function __construct(private readonly BookingRepository $bookings,private readonly CatalogRepository $catalog,private readonly \Psr\Log\LoggerInterface $logger) {}
 public function create(int $slotId,int $serviceId,string $name,string $phone,mixed $consent): Result
 {
  $r=new Result();
  if (!in_array($consent,[true,1,'1','true','Y','on'],true)) return $r->addError(new Error('Нужно согласие на обработку персональных данных','CONSENT_REQUIRED'));
  $name=trim($name); $phone=trim($phone); $digits=preg_replace('/\D/','',$phone);
  $service=$this->catalog->find('services',$serviceId);
  if (mb_strlen($name)<2||mb_strlen($name)>100||!preg_match('/^[\p{L}\p{M}\s\-\x{2019}\x{0027}]+$/u',$name)||!preg_match('/^[+\d()\s-]{10,30}$/',$phone)||strlen($digits)<10||strlen($digits)>15||!$service) return $r->addError(new Error('Проверьте имя, телефон и выбранную услугу','VALIDATION'));
  $this->bookings->begin();
  try {
   $slot=$this->bookings->lockSlot($slotId);
   if (!$slot||!$this->catalog->find('masters',(int)$slot['MASTER_ID'])) { $this->bookings->rollback(); return $r->addError(new Error('Слот не найден','SLOT_NOT_FOUND')); }
   if ($slot['CLOSED']==='Y'||$slot['STARTS_AT']->getTimestamp()<=time()||$this->bookings->entryForSlot($slotId)) { $this->bookings->rollback(); return $r->addError(new Error('Это время уже занято. Выберите другое.','SLOT_TAKEN')); }
   $add=$this->bookings->add(['SLOT_ID'=>$slotId,'SERVICE_ID'=>$serviceId,'NAME'=>$name,'PHONE'=>'+'.$digits,'CONSENT_AT'=>new DateTime()]);
   if (!$add->isSuccess()) throw new \RuntimeException(implode('; ',$add->getErrorMessages()));
   $master=$this->catalog->find('masters',(int)$slot['MASTER_ID']);
   $mail=Event::send(['EVENT_NAME'=>'BXMAX_BOOKING_NEW','LID'=>Option::get('bxmax.booking','site_id','s1'),'C_FIELDS'=>[
    'BOOKING_ID'=>$add->getId(),'NAME'=>$name,'PHONE'=>'+'.$digits,'SERVICE'=>$service['NAME'],'MASTER'=>$master['NAME'],
    'STARTS_AT'=>(clone $slot['STARTS_AT'])->setTimeZone(new \DateTimeZone('Europe/Saratov'))->format('d.m.Y H:i').' (Саратов, UTC+4)',
    'EMAIL_TO'=>Option::get('bxmax.booking','admin_email',Option::get('main','email_from','admin@example.com')),
   ]]);
   if (!$mail->isSuccess()) throw new \RuntimeException('Cannot enqueue notification');
   $this->bookings->commit();
   return $r->setData(['bookingId'=>(int)$add->getId()]);
  } catch (\Throwable $e) {
   $this->bookings->rollback();
   if ($this->bookings->entryForSlot($slotId)) return $r->addError(new Error('Это время уже занято','SLOT_TAKEN'));
   $this->logger->error('Booking transaction failed: {type}; slot {slot}', ['type'=>get_class($e),'slot'=>$slotId]);
   return $r->addError(new Error('Не удалось сохранить запись. Попробуйте ещё раз.','VALIDATION'));
  }
 }
 public function delete(int $id): Result { return $this->bookings->deleteEntry($id); }
 public function setClosed(int $id,bool $closed): Result
 {
  $this->bookings->begin();
  try {
   if (!$this->bookings->lockSlot($id)) { $this->bookings->rollback(); return (new Result())->addError(new Error('Слот не найден','SLOT_NOT_FOUND')); }
   $r=$this->bookings->closeSlot($id,$closed);
   if ($r->isSuccess()) $this->bookings->commit(); else $this->bookings->rollback();
   return $r;
  } catch (\Throwable $e) { $this->bookings->rollback(); throw $e; }
 }
}
