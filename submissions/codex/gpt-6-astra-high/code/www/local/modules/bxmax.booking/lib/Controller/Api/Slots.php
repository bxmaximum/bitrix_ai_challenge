<?php declare(strict_types=1);
namespace Bxmax\Booking\Controller\Api;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\DisablePrefilters;
use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\HttpMethod;
use Bxmax\Booking\Service\ScheduleService;
final class Slots extends Controller
{
 #[DisablePrefilters([ActionFilter\Authentication::class,ActionFilter\Csrf::class])]
 #[HttpMethod(['GET'])]
 public function listAction(ScheduleService $schedule,int $masterId=0,string $weekStart=''): ?array
 {
  $result=$schedule->list($masterId,$weekStart);
  if (!$result->isSuccess()) { $this->addErrors($result->getErrors()); return null; }
  return $result->getData();
 }
}
