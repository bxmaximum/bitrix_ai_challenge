<?php

use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

// Все классы в lib автоматически подгружаются Битриксом

if (Loader::includeModule('barista.telegramnotifier')) {
    $eventManager = EventManager::getInstance();
    
    // Подписываемся на событие записи в журнал
    $eventManager->addEventHandler(
        'main',
        'OnEventLogAdd',
        ['Barista\\TelegramNotifier\\EventHandler', 'onEventLogAdd']
    );
} 