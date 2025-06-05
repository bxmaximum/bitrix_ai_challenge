<?php

namespace Barista\Telegramlogger\Service;

use Bitrix\Main\Web\HttpClient;

class TelegramService
{
    private const API_BASE_URL = 'https://api.telegram.org/bot';
    private HttpClient $httpClient;

    public function __construct(
        private string $botToken,
        private string $chatId
    ) {
        $this->httpClient = new HttpClient();
    }

    public function sendMessage(string $message): bool
    {
        if (empty($this->botToken) || empty($this->chatId)) {
            return false;
        }

        $url = self::API_BASE_URL . $this->botToken . '/sendMessage';

        $data = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ];

        try {
            $response = $this->httpClient->post($url, $data);
            $result = json_decode($response, true);
            return $result['ok'] === true;
        } catch (\Exception $e) {
            // Log the exception if needed
            return false;
        }
    }
} 