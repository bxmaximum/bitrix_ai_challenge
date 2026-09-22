<?php declare(strict_types=1);
if (!defined('B_PROLOG_INCLUDED')||B_PROLOG_INCLUDED!==true) die();
final class BxmaxBookingComponent extends CBitrixComponent
{
 public function executeComponent(): void {
  if (!\Bitrix\Main\Loader::includeModule('bxmax.booking')) return;
  $this->arResult=\Bitrix\Main\DI\ServiceLocator::getInstance()->get(\Bxmax\Booking\Service\CatalogService::class)->content();
  $this->includeComponentTemplate();
 }
}
