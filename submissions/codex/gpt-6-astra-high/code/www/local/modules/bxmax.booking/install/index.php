<?php declare(strict_types=1);
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
final class bxmax_booking extends CModule
{
 public $MODULE_ID='bxmax.booking';
 public $MODULE_GROUP_RIGHTS='Y';
 public $PARTNER_NAME='BXMax';
 public $PARTNER_URI='https://bxmax.ru';
 public function __construct() {
  include __DIR__.'/version.php';
  $this->MODULE_VERSION=$arModuleVersion['VERSION']; $this->MODULE_VERSION_DATE=$arModuleVersion['VERSION_DATE'];
  $this->MODULE_NAME=Loc::getMessage('BXMAX_BOOKING_NAME'); $this->MODULE_DESCRIPTION=Loc::getMessage('BXMAX_BOOKING_DESCRIPTION');
 }
 public function GetModuleRightList(): array { return ['reference_id'=>['D','R','W'],'reference'=>['Нет доступа','Просмотр','Управление записью']]; }
 public function DoInstall(): void {
  global $USER;
  if (!$USER->IsAdmin()) throw new \RuntimeException('Нет доступа');
  ModuleManager::registerModule($this->MODULE_ID);
  Loader::includeModule($this->MODULE_ID);
  \Bxmax\Booking\Install\Installer::install();
 }
 public function DoUninstall(): void {
  global $USER;
  if (!$USER->IsAdmin()) throw new \RuntimeException('Нет доступа');
  Loader::includeModule($this->MODULE_ID);
  \Bxmax\Booking\Install\Installer::uninstall();
  ModuleManager::unRegisterModule($this->MODULE_ID);
 }
}
