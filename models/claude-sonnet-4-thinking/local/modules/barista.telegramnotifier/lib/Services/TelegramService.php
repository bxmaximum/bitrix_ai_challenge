<?php

namespace Barista\TelegramNotifier\Services;

class TelegramService
{
    private const API_URL = 'https://api.telegram.org/bot';
    private const TIMEOUT = 30;
    private const MAX_MESSAGE_LENGTH = 4096;
    
    private string $botToken;
    
    public function __construct(string $botToken)
    {
        $this->botToken = $botToken;
    }
    
    public function sendMessage(string $chatId, string $message, bool $parseMode = true): array
    {
        if (strlen($message) > static::MAX_MESSAGE_LENGTH) {
            $message = mb_substr($message, 0, static::MAX_MESSAGE_LENGTH - 3) . '...';
        }
        
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
        ];
        
        if ($parseMode) {
            $data['parse_mode'] = 'Markdown';
        }
        
        return $this->makeApiCall('sendMessage', $data);
    }
    
    public function getMe(): array
    {
        return $this->makeApiCall('getMe');
    }
    
    public function getChat(string $chatId): array
    {
        return $this->makeApiCall('getChat', ['chat_id' => $chatId]);
    }
    
    public function testConnection(): bool
    {
        $result = $this->getMe();
        return $result['success'] ?? false;
    }
    
    private function makeApiCall(string $method, array $data = []): array
    {
        $url = static::API_URL . $this->botToken . '/' . $method;
        
        $postData = json_encode($data);
        if ($postData === false) {
            return [
                'success' => false,
                'error' => 'Ошибка кодирования JSON'
            ];
        }
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($postData)
                ],
                'content' => $postData,
                'timeout' => static::TIMEOUT,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            $error = error_get_last();
            return [
                'success' => false,
                'error' => 'HTTP ошибка: ' . ($error['message'] ?? 'Неизвестная ошибка')
            ];
        }
        
        $decodedResponse = json_decode($response, true);
        if ($decodedResponse === null) {
            return [
                'success' => false,
                'error' => 'Ошибка декодирования ответа'
            ];
        }
        
        if (!isset($decodedResponse['ok']) || !$decodedResponse['ok']) {
            return [
                'success' => false,
                'error' => $decodedResponse['description'] ?? 'API ошибка',
                'error_code' => $decodedResponse['error_code'] ?? 0
            ];
        }
        
        return [
            'success' => true,
            'data' => $decodedResponse['result'] ?? []
        ];
    }
    
    public static function createFromConfig(): ?self
    {
        $token = ConfigService::getBotToken();
        if (empty($token)) {
            return null;
        }
        
        return new self($token);
    }
    
    public function sendToMultipleChats(array $chatIds, string $message): array
    {
        $results = [];
        
        foreach ($chatIds as $chatId) {
            $chatId = trim($chatId);
            if (empty($chatId)) {
                continue;
            }
            
            $result = $this->sendMessage($chatId, $message);
            $results[$chatId] = $result;
            
            if (!$result['success']) {
                LogService::error('Ошибка отправки в чат ' . $chatId, $result);
            }
            
            usleep(100000);
        }
        
        return $results;
    }
    
    public function validateChatAccess(string $chatId): array
    {
        $result = $this->getChat($chatId);
        
        if (!$result['success']) {
            return [
                'valid' => false,
                'error' => $result['error'] ?? 'Неизвестная ошибка'
            ];
        }
        
        $chat = $result['data'] ?? [];
        
        if (isset($chat['type']) && in_array($chat['type'], ['group', 'supergroup', 'channel'])) {
            return [
                'valid' => true,
                'type' => $chat['type'],
                'title' => $chat['title'] ?? 'Без названия'
            ];
        } elseif (isset($chat['type']) && $chat['type'] === 'private') {
            return [
                'valid' => true,
                'type' => 'private',
                'title' => trim(($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? ''))
            ];
        }
        
        return [
            'valid' => false,
            'error' => 'Неподдерживаемый тип чата'
        ];
    }
    
    public function formatForTelegram(string $text): string
    {
        $text = str_replace(['*', '_', '`', '['], ['\\*', '\\_', '\\`', '\\['], $text);
        
        return $text;
    }
    
    public function splitLongMessage(string $message, int $maxLength = null): array
    {
        $maxLength = $maxLength ?? static::MAX_MESSAGE_LENGTH;
        
        if (mb_strlen($message) <= $maxLength) {
            return [$message];
        }
        
        $parts = [];
        $words = explode(' ', $message);
        $currentPart = '';
        
        foreach ($words as $word) {
            $testPart = empty($currentPart) ? $word : $currentPart . ' ' . $word;
            
            if (mb_strlen($testPart) > $maxLength) {
                if (!empty($currentPart)) {
                    $parts[] = $currentPart;
                    $currentPart = $word;
                } else {
                    $parts[] = mb_substr($word, 0, $maxLength);
                    $currentPart = mb_substr($word, $maxLength);
                }
            } else {
                $currentPart = $testPart;
            }
        }
        
        if (!empty($currentPart)) {
            $parts[] = $currentPart;
        }
        
        return $parts;
    }
}