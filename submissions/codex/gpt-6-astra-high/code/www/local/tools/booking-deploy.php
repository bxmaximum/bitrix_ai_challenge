<?php declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
$_SERVER['DOCUMENT_ROOT']=dirname(__DIR__,2);
define('NO_KEEP_STATISTIC',true); define('NOT_CHECK_PERMISSIONS',true);
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
if (!\Bitrix\Main\Loader::includeModule('sprint.migration')) throw new RuntimeException('sprint.migration missing');
$USER->Authorize(1);
$module=CModule::CreateModuleObject('bxmax.booking');
if (($argv[1]??'')==='uninstall') $module->DoUninstall(); else $module->DoInstall();
echo "OK\n";
