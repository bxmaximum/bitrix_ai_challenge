<?php

// OMUT_COMPOSER_AUTOLOAD — portable Composer vendor (site root)
$omutAutoloadCandidates = array(
    dirname(__DIR__, 3) . '/vendor/autoload.php', // layout B: www/local/php_interface
    dirname(__DIR__, 2) . '/vendor/autoload.php', // legacy flat
);
foreach ($omutAutoloadCandidates as $omutAutoload) {
    if (is_file($omutAutoload)) {
        require_once $omutAutoload;
        break;
    }
}

// BXMAX_LANDING_DEPLOY — лендинг студии «Лак&Точка».
// Приводит свежую копию площадки в рабочее состояние кодом: инфоблоки
// services/masters с наполнением, модуль bxmax.booking, расписание слотов
// на 14 дней вперёд и шаблон сайта. Повторные запросы ничего не делают —
// версия развёртывания лежит в опции bxmax.booking/deploy_revision.
require_once __DIR__ . '/bxmax_deploy.php';
bxmaxDeployMaybeRun();
