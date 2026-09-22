<?php declare(strict_types=1);
namespace Sprint\Migration;
final class Version20260922160000 extends Version
{
 protected $description='Лак&Точка: установка bxmax.booking, контент и расписание';
 public function up() {
  $module=\CModule::CreateModuleObject('bxmax.booking');
  if (!$module) throw new \RuntimeException('Файлы bxmax.booking не найдены');
  $module->DoInstall();
 }
 public function down() { \CModule::CreateModuleObject('bxmax.booking')->DoUninstall(); }
}
