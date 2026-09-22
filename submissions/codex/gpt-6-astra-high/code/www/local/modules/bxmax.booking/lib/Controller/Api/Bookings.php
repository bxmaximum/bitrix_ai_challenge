<?php declare(strict_types=1);
namespace Bxmax\Booking\Controller\Api;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\DisablePrefilters;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\HttpMethod;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bxmax\Booking\Service\BookingService;
final class Bookings extends Controller
{
 #[DisablePrefilters([ActionFilter\Authentication::class])]
 #[HttpMethod(['POST'])]
 #[CloseSession]
 public function createAction(BookingService $bookings,int $slotId=0,int $serviceId=0,string $name='',string $phone=''): ?array
 {
  $result=$bookings->create($slotId,$serviceId,$name,$phone,$this->getRequest()->getPost('consent'));
  if (!$result->isSuccess()) { $this->addErrors($result->getErrors()); return null; }
  return $result->getData();
 }
}
