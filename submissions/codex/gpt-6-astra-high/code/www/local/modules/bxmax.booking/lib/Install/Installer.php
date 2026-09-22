<?php declare(strict_types=1);
namespace Bxmax\Booking\Install;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;
use Bxmax\Booking\Model\SlotTable;
use Bxmax\Booking\Model\EntryTable;
use Bxmax\Booking\Service\ScheduleService;
final class Installer
{
 public static function install(): void
 {
  if (!Loader::includeModule('iblock')||!Loader::includeModule('sprint.migration')) throw new \RuntimeException('Нужны модули iblock и sprint.migration');
  $db=Application::getConnection();
  foreach ([SlotTable::class,EntryTable::class] as $table) if (!$db->isTableExists($table::getTableName())) $table::getEntity()->createDbTable();
  if (!$db->isIndexExists(SlotTable::getTableName(),['MASTER_ID','STARTS_AT'])) $db->createIndex(SlotTable::getTableName(),'ux_bxmax_master_start',['MASTER_ID','STARTS_AT'],null,'UNIQUE');
  if (!$db->isIndexExists(EntryTable::getTableName(),['SLOT_ID'])) $db->createIndex(EntryTable::getTableName(),'ux_bxmax_entry_slot',['SLOT_ID'],null,'UNIQUE');
  // Transactions and row locks are mandatory for booking/closing serialization.
  $db->queryExecute('ALTER TABLE bxmax_booking_slot ENGINE=InnoDB');
  $db->queryExecute('ALTER TABLE bxmax_booking_entry ENGINE=InnoDB');
  $site=\Bitrix\Main\SiteTable::getList(['filter'=>['=ACTIVE'=>'Y'],'order'=>['DEF'=>'DESC','SORT'=>'ASC'],'limit'=>1])->fetch();
  if (!$site) throw new \RuntimeException('Нет активного сайта');
  Option::set('bxmax.booking','site_id',$site['LID']);
  if (!Option::get('bxmax.booking','admin_email','')) Option::set('bxmax.booking','admin_email',$site['EMAIL'] ?: Option::get('main','email_from','admin@example.com'));
  self::content($site['LID']);
  self::mail($site['LID']);
  $root=Application::getDocumentRoot(); $module=dirname(__DIR__,2);
  CopyDirFiles($module.'/install/components',$root.'/local/components',true,true);
  CopyDirFiles($module.'/install/admin',$root.'/local/admin',true,true);
  $backup=$module.'/install/home-backup.php';
  if (!is_file($backup)&&is_file($root.'/index.php')) copy($root.'/index.php',$backup);
  copy($module.'/install/home.php',$root.'/index.php');
  \CAgent::RemoveModuleAgents('bxmax.booking');
  \CAgent::AddAgent('\\Bxmax\\Booking\\Service\\ScheduleService::agent();','bxmax.booking','N',86400,'','Y');
  ServiceLocator::getInstance()->get(ScheduleService::class)->generate();
 }
 private static function content(string $site): void
 {
  $h=(new \Sprint\Migration\HelperManager())->Iblock();
  $h->saveIblockType(['ID'=>'bxmax_booking','SECTIONS'=>'N','LANG'=>['ru'=>['NAME'=>'Лак&Точка','ELEMENT_NAME'=>'Элементы'],'en'=>['NAME'=>'Booking']]]);
  $services=$h->saveIblock(['IBLOCK_TYPE_ID'=>'bxmax_booking','CODE'=>'services','API_CODE'=>'BxmaxServices','NAME'=>'Услуги','ACTIVE'=>'Y','LID'=>[$site],'VERSION'=>2,'GROUP_ID'=>[1=>'X',2=>'R']]);
  $masters=$h->saveIblock(['IBLOCK_TYPE_ID'=>'bxmax_booking','CODE'=>'masters','API_CODE'=>'BxmaxMasters','NAME'=>'Мастера','ACTIVE'=>'Y','LID'=>[$site],'VERSION'=>2,'GROUP_ID'=>[1=>'X',2=>'R']]);
  $h->saveProperty($services,['NAME'=>'Цена, ₽','CODE'=>'PRICE','PROPERTY_TYPE'=>'N','ACTIVE'=>'Y']);
  $h->saveProperty($masters,['NAME'=>'Специализация','CODE'=>'SPECIALTY','PROPERTY_TYPE'=>'S','ACTIVE'=>'Y']);
  $h->saveProperty($masters,['NAME'=>'Локальная фотография','CODE'=>'PHOTO','PROPERTY_TYPE'=>'S','ACTIVE'=>'Y']);
  $items=[
   ['clean','Маникюр без покрытия',1400,'Аккуратная форма, бережная обработка кутикулы и капля масла. Когда хочется просто ухоженные руки.'],
   ['color','Маникюр с гель-лаком',2400,'Тонкое ровное покрытие и ваш любимый оттенок. Снятие нашего покрытия уже включено.'],
   ['french','Маникюр с френчем',2800,'Мягкий квадрат или миндаль, молочная база и та самая тонкая улыбка. Классика с вашим характером.'],
   ['japanese','Японский маникюр',1900,'Деликатная полировка пастой и пудрой. Естественный блеск без цветного покрытия.'],
   ['pedicure','Педикюр без покрытия',2500,'Обработка стоп, аккуратные ногти и лёгкость в каждом шаге. В завершение — крем и небольшой массаж.'],
   ['pedicure-color','Педикюр с гель-лаком',3200,'Полный уход за стопами и стойкое однотонное покрытие. Красиво и в открытых босоножках, и просто для себя.'],
   ['spa','SPA-уход для рук',1200,'Мягкий пилинг, тёплая маска и неспешный массаж. Час, чтобы выдохнуть и никуда не торопиться.'],
   ['classic','Маникюр с обычным лаком',1800,'Любимый цвет без лампы: подготовим ногти, нанесём лак и бережно высушим покрытие.'],
  ];
  foreach ($items as $i=>$v) $h->saveElement($services,['CODE'=>$v[0],'NAME'=>$v[1],'SORT'=>($i+1)*10,'ACTIVE'=>'Y','PREVIEW_TEXT'=>$v[3],'PREVIEW_TEXT_TYPE'=>'text'],['PRICE'=>$v[2]]);
  foreach ([['anna','Анна','Тонкое покрытие · френч','Любит чистые линии и умеет подобрать нюд, который не хочется менять. В профессии 6 лет.'],['maria','Мария','Педикюр · бережный уход','Внимательна к каждой детали. За спокойствием и аккуратным педикюром — к Марии. В профессии 5 лет.'],['elena','Елена','Японский маникюр · SPA','Верит, что естественные ногти тоже могут быть украшением. В профессии 4 года.']] as $i=>$v) $h->saveElement($masters,['CODE'=>$v[0],'NAME'=>$v[1],'SORT'=>($i+1)*10,'ACTIVE'=>'Y','PREVIEW_TEXT'=>$v[3]],['SPECIALTY'=>$v[2],'PHOTO'=>'/local/modules/bxmax.booking/assets/images/master-'.($i+1).'.jpg']);
 }
 private static function mail(string $site): void
 {
  if (!\CEventType::GetList(['TYPE_ID'=>'BXMAX_BOOKING_NEW','LID'=>'ru'])->Fetch()) (new \CEventType())->Add(['LID'=>'ru','EVENT_NAME'=>'BXMAX_BOOKING_NEW','NAME'=>'Новая запись в Лак&Точка','DESCRIPTION'=>"#BOOKING_ID# — номер\n#NAME# — имя\n#PHONE# — телефон\n#MASTER# — мастер\n#SERVICE# — услуга\n#STARTS_AT# — время\n#EMAIL_TO# — администратор"]);
  if (!\CEventMessage::GetList('id','asc',['TYPE_ID'=>'BXMAX_BOOKING_NEW'])->Fetch()) {
   $id=(new \CEventMessage())->Add(['ACTIVE'=>'Y','EVENT_NAME'=>'BXMAX_BOOKING_NEW','LID'=>[$site],'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#','EMAIL_TO'=>'#EMAIL_TO#','SUBJECT'=>'Лак&Точка: запись №#BOOKING_ID#','BODY_TYPE'=>'text','MESSAGE'=>"Новая запись №#BOOKING_ID#\n\nМастер: #MASTER#\nВремя: #STARTS_AT#\nУслуга: #SERVICE#\nИмя: #NAME#\nТелефон: #PHONE#\n\nСогласие на обработку данных получено. Управление: /local/admin/bxmax_booking_entries.php"]);
   if (!$id) throw new \RuntimeException('Не удалось создать почтовый шаблон');
  }
 }
 public static function uninstall(): void
 {
  \CAgent::RemoveModuleAgents('bxmax.booking');
  $messages=\CEventMessage::GetList('id','asc',['TYPE_ID'=>'BXMAX_BOOKING_NEW']);
  while ($message=$messages->Fetch()) \CEventMessage::Delete($message['ID']);
  \CEventType::Delete('BXMAX_BOOKING_NEW');
  foreach (\Bitrix\Main\Mail\Internal\EventTable::getList(['select'=>['ID'],'filter'=>['=EVENT_NAME'=>'BXMAX_BOOKING_NEW']]) as $event) \Bitrix\Main\Mail\Internal\EventTable::delete((int)$event['ID']);
  $db=Application::getConnection();
  foreach ([EntryTable::class,SlotTable::class] as $table) if ($db->isTableExists($table::getTableName())) $db->dropTable($table::getTableName());
  if (Loader::includeModule('iblock')) \CIBlockType::Delete('bxmax_booking');
  $root=Application::getDocumentRoot(); $module=dirname(__DIR__,2);
  DeleteDirFilesEx('/local/components/bxmax/booking');
  foreach (['entries','slots'] as $page) { $path=$root.'/local/admin/bxmax_booking_'.$page.'.php'; if (is_file($path)) unlink($path); }
  if (is_file($module.'/install/home-backup.php')) { copy($module.'/install/home-backup.php',$root.'/index.php'); unlink($module.'/install/home-backup.php'); }
  Option::delete('bxmax.booking');
  \CMain::DelGroupRight('bxmax.booking');
 }
}
