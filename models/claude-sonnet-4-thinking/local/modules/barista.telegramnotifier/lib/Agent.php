<?php

namespace Barista\TelegramNotifier;

use Bitrix\Main\Loader;
use Barista\TelegramNotifier\Services\ConfigService;
use Barista\TelegramNotifier\Services\LogService;

class Agent
{
    public static function processQueue(): string
    {
        if (!Loader::includeModule('barista.telegramnotifier')) {
            return '';
        }

        if (!ConfigService::isModuleEnabled()) {
            return '\\Barista\\TelegramNotifier\\Agent::processQueue();';
        }

        try {
            $processed = EventHandler::processQueue();
            
            if ($processed > 0) {
                LogService::log("Агент обработал очередь", ['processed' => $processed]);
            }
        } catch (\Throwable $e) {
            LogService::error("Ошибка в агенте обработки очереди", [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }

        return '\\Barista\\TelegramNotifier\\Agent::processQueue();';
    }

    public static function cleanup(): string
    {
        if (!Loader::includeModule('barista.telegramnotifier')) {
            return '';
        }

        try {
            $result = EventHandler::cleanupOldData();
            LogService::log("Агент выполнил очистку", $result);
        } catch (\Throwable $e) {
            LogService::error("Ошибка в агенте очистки", [
                'exception' => get_class($e),
                'message' => $e->getMessage()
            ]);
        }

        return '\\Barista\\TelegramNotifier\\Agent::cleanup();';
    }

    public static function install(): void
    {
        \CAgent::AddAgent(
            '\\Barista\\TelegramNotifier\\Agent::processQueue();',
            'barista.telegramnotifier',
            'N',
            60,
            '',
            'Y',
            '',
            30,
            false,
            false
        );

        \CAgent::AddAgent(
            '\\Barista\\TelegramNotifier\\Agent::cleanup();',
            'barista.telegramnotifier',
            'N',
            86400,
            '',
            'Y',
            '',
            30,
            false,
            false
        );
    }

    public static function uninstall(): void
    {
        \CAgent::RemoveModuleAgents('barista.telegramnotifier');
    }
} 