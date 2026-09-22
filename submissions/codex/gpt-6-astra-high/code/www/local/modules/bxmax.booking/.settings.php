<?php declare(strict_types=1);
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Repository\BookingRepository;
use Bxmax\Booking\Service\BookingService;
use Bxmax\Booking\Service\ScheduleService;
use Bxmax\Booking\Service\CatalogService;
return [
 'controllers'=>['value'=>['defaultNamespace'=>'\\Bxmax\\Booking\\Controller','namespaces'=>['\\Bxmax\\Booking\\Controller\\Api'=>'api']], 'readonly'=>true],
 'loggers'=>['value'=>['bxmax.booking'=>['constructor'=>static fn()=>new \Bitrix\Main\Diag\EventLogger('bxmax.booking','BOOKING_ERROR')]],'readonly'=>true],
 'services'=>['value'=>[
 'bxmax.booking.logger'=>['constructor'=>static function(){
  $config=\Bitrix\Main\Config\Configuration::getInstance('bxmax.booking')->get('loggers');
  return ($config['bxmax.booking']['constructor'])();
 }],
 CatalogRepository::class=>['className'=>CatalogRepository::class],
 BookingRepository::class=>['className'=>BookingRepository::class],
 BookingService::class=>['className'=>BookingService::class,'constructorParams'=>static fn()=>[\Bitrix\Main\DI\ServiceLocator::getInstance()->get(BookingRepository::class),\Bitrix\Main\DI\ServiceLocator::getInstance()->get(CatalogRepository::class),\Bitrix\Main\DI\ServiceLocator::getInstance()->get('bxmax.booking.logger')]],
 ScheduleService::class=>['className'=>ScheduleService::class,'constructorParams'=>static fn()=>[\Bitrix\Main\DI\ServiceLocator::getInstance()->get(CatalogRepository::class),\Bitrix\Main\DI\ServiceLocator::getInstance()->get(BookingRepository::class)]],
 CatalogService::class=>['className'=>CatalogService::class,'constructorParams'=>static fn()=>[\Bitrix\Main\DI\ServiceLocator::getInstance()->get(CatalogRepository::class)]],
 ],'readonly'=>true],
];
