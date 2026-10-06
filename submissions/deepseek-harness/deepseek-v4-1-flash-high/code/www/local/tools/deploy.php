<?php

/**
 * Развёртывание лендинга «Лак&Точка» из консоли.
 *
 * Запуск:
 *   php local/tools/deploy.php            # развернуть, если ещё не развёрнуто
 *   php local/tools/deploy.php --force    # перезапустить развёртывание целиком
 *
 * Те же действия выполняет local/php_interface/init.php при первом запросе к
 * сайту, поэтому скрипт нужен только для ручного перезапуска или отладки.
 */

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);
$_SERVER['HTTP_HOST'] ??= 'localhost';
$_SERVER['SERVER_NAME'] ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/';
$_SERVER['SCRIPT_NAME'] ??= '/local/tools/deploy.php';
$_SERVER['REQUEST_METHOD'] ??= 'GET';

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$force = in_array('--force', $argv ?? [], true);

if ($force)
{
	Bitrix\Main\Config\Option::set('bxmax.booking', BXMAX_DEPLOY_OPTION, '');
}

bxmaxDeployRun();

echo 'Развёртывание выполнено (revision ' . BXMAX_DEPLOY_REVISION . ').' . PHP_EOL;
