<?php declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$_SERVER['DOCUMENT_ROOT']=dirname(__DIR__,4);define('NO_KEEP_STATISTIC',true);define('NOT_CHECK_PERMISSIONS',true);
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('bxmax.booking');
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/interface/admin_lib.php';
$group=new CGroup();$id=(int)$group->Add(['ACTIVE'=>'Y','NAME'=>'BXMax temporary ACL test','STRING_ID'=>'bxmax_booking_acl_test']);
if(!$id)throw new RuntimeException('Cannot create test group');
try {
 foreach(['D','R','W'] as $right){
  CMain::SetGroupRight('bxmax.booking',$id,$right);
  $USER->SetUserGroupArray([$id]);
  if($APPLICATION->GetGroupRight('bxmax.booking')!==$right)throw new RuntimeException('Incorrect right '.$right);
  $menu=include $_SERVER['DOCUMENT_ROOT'].'/local/modules/bxmax.booking/admin/menu.php';
  if(($right==='D')!==($menu===false))throw new RuntimeException('Menu ACL failed');
  if($right!=='D'){
   $view=\Bxmax\Booking\Admin\Lists::prepare('slots');
   if($view['write']!==($right==='W'))throw new RuntimeException('Write ACL failed');
  }
  echo 'PASS '.$right.' rights and menu'.PHP_EOL;
 }
}finally{
 CMain::DelGroupRight('bxmax.booking',[$id]);CGroup::Delete($id);$USER->SetUserGroupArray([2]);
}
