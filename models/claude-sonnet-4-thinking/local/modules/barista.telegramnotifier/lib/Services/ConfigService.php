<?php

namespace Barista\TelegramNotifier\Services;

use Bitrix\Main\Config\Option;

class ConfigService
{
    private const MODULE_ID = 'barista.telegramnotifier';
    
    public static function isModuleEnabled(): bool
    {
        return Option::get(static::MODULE_ID, 'enabled', 'N') === 'Y';
    }
    
    public static function getBotToken(): string
    {
        $token = Option::get(static::MODULE_ID, 'bot_token', '');
        if (!empty($token)) {
            return SecurityService::decrypt($token);
        }
        return '';
    }
    
    public static function setBotToken(string $token): void
    {
        Option::set(static::MODULE_ID, 'bot_token', SecurityService::encrypt($token));
    }
    
    public static function getChatIds(): array
    {
        $chatIds = Option::get(static::MODULE_ID, 'chat_ids', '');
        if (empty($chatIds)) {
            return [];
        }
        
        $ids = explode(',', $chatIds);
        return array_map('trim', $ids);
    }
    
    public static function setChatIds(array $chatIds): void
    {
        Option::set(static::MODULE_ID, 'chat_ids', implode(',', $chatIds));
    }
    
    public static function getCriticalEventTypes(): array
    {
        $types = Option::get(static::MODULE_ID, 'critical_event_types', '');
        if (empty($types)) {
            return [];
        }
        
        return explode(',', $types);
    }
    
    public static function setCriticalEventTypes(array $types): void
    {
        Option::set(static::MODULE_ID, 'critical_event_types', implode(',', $types));
    }
    
    public static function getCriticalKeywords(): array
    {
        $keywords = Option::get(static::MODULE_ID, 'critical_keywords', '');
        if (empty($keywords)) {
            return [];
        }
        
        return explode(',', $keywords);
    }
    
    public static function setCriticalKeywords(array $keywords): void
    {
        Option::set(static::MODULE_ID, 'critical_keywords', implode(',', $keywords));
    }
    
    public static function getAntiSpamInterval(): int
    {
        return (int)Option::get(static::MODULE_ID, 'antispam_interval', '300');
    }
    
    public static function setAntiSpamInterval(int $seconds): void
    {
        Option::set(static::MODULE_ID, 'antispam_interval', (string)$seconds);
    }
    
    public static function getSilenceMode(): bool
    {
        return Option::get(static::MODULE_ID, 'silence_mode', 'N') === 'Y';
    }
    
    public static function setSilenceMode(bool $enabled): void
    {
        Option::set(static::MODULE_ID, 'silence_mode', $enabled ? 'Y' : 'N');
    }
    
    public static function getSilenceDuration(): int
    {
        return (int)Option::get(static::MODULE_ID, 'silence_duration', '3600');
    }
    
    public static function setSilenceDuration(int $seconds): void
    {
        Option::set(static::MODULE_ID, 'silence_duration', (string)$seconds);
    }
    
    public static function getMaxRetries(): int
    {
        return (int)Option::get(static::MODULE_ID, 'max_retries', '5');
    }
    
    public static function setMaxRetries(int $retries): void
    {
        Option::set(static::MODULE_ID, 'max_retries', (string)$retries);
    }
    
    public static function getQueueProcessingLimit(): int
    {
        return (int)Option::get(static::MODULE_ID, 'queue_limit', '10');
    }
    
    public static function setQueueProcessingLimit(int $limit): void
    {
        Option::set(static::MODULE_ID, 'queue_limit', (string)$limit);
    }
    
    public static function isLoggingEnabled(): bool
    {
        return Option::get(static::MODULE_ID, 'logging_enabled', 'Y') === 'Y';
    }
    
    public static function setLoggingEnabled(bool $enabled): void
    {
        Option::set(static::MODULE_ID, 'logging_enabled', $enabled ? 'Y' : 'N');
    }
    
    public static function getLogLevel(): string
    {
        return Option::get(static::MODULE_ID, 'log_level', 'INFO');
    }
    
    public static function setLogLevel(string $level): void
    {
        Option::set(static::MODULE_ID, 'log_level', $level);
    }
    
    public static function enable(): void
    {
        Option::set(static::MODULE_ID, 'enabled', 'Y');
    }
    
    public static function disable(): void
    {
        Option::set(static::MODULE_ID, 'enabled', 'N');
    }
    
    public static function getAllSettings(): array
    {
        return [
            'enabled' => static::isModuleEnabled(),
            'bot_token' => static::getBotToken(),
            'chat_ids' => static::getChatIds(),
            'critical_event_types' => static::getCriticalEventTypes(),
            'critical_keywords' => static::getCriticalKeywords(),
            'antispam_interval' => static::getAntiSpamInterval(),
            'silence_mode' => static::getSilenceMode(),
            'silence_duration' => static::getSilenceDuration(),
            'max_retries' => static::getMaxRetries(),
            'queue_limit' => static::getQueueProcessingLimit(),
            'logging_enabled' => static::isLoggingEnabled(),
            'log_level' => static::getLogLevel(),
        ];
    }
    
    public static function validateSettings(array $settings): array
    {
        $errors = [];
        
        if (empty($settings['bot_token'])) {
            $errors[] = 'Токен бота обязателен';
        } elseif (!preg_match('/^\d+:[A-Za-z0-9_-]+$/', $settings['bot_token'])) {
            $errors[] = 'Неверный формат токена бота';
        }
        
        if (empty($settings['chat_ids'])) {
            $errors[] = 'Укажите хотя бы один Chat ID';
        } else {
            foreach ($settings['chat_ids'] as $chatId) {
                if (!preg_match('/^-?\d+$/', trim($chatId))) {
                    $errors[] = 'Неверный формат Chat ID: ' . $chatId;
                }
            }
        }
        
        if (isset($settings['antispam_interval']) && ($settings['antispam_interval'] < 60 || $settings['antispam_interval'] > 3600)) {
            $errors[] = 'Интервал антиспама должен быть от 60 до 3600 секунд';
        }
        
        if (isset($settings['max_retries']) && ($settings['max_retries'] < 1 || $settings['max_retries'] > 10)) {
            $errors[] = 'Количество повторов должно быть от 1 до 10';
        }
        
        return $errors;
    }
} 