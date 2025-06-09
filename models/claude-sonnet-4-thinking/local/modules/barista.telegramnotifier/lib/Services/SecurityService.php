<?php

namespace Barista\TelegramNotifier\Services;

use Bitrix\Main\Config\Option;

class SecurityService
{
    private const MODULE_ID = 'barista.telegramnotifier';
    private const CIPHER = 'AES-256-CBC';
    
    public static function encrypt(string $data): string
    {
        if (empty($data)) {
            return '';
        }
        
        $key = static::getEncryptionKey();
        $iv = random_bytes(16);
        
        $encrypted = openssl_encrypt($data, static::CIPHER, $key, 0, $iv);
        if ($encrypted === false) {
            throw new \Exception('Ошибка шифрования данных');
        }
        
        return base64_encode($iv . $encrypted);
    }
    
    public static function decrypt(string $encryptedData): string
    {
        if (empty($encryptedData)) {
            return '';
        }
        
        try {
            $data = base64_decode($encryptedData);
            if ($data === false) {
                return '';
            }
            
            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);
            
            if (strlen($iv) !== 16) {
                return '';
            }
            
            $key = static::getEncryptionKey();
            $decrypted = openssl_decrypt($encrypted, static::CIPHER, $key, 0, $iv);
            
            return $decrypted !== false ? $decrypted : '';
        } catch (\Throwable $e) {
            return '';
        }
    }
    
    private static function getEncryptionKey(): string
    {
        $key = Option::get(static::MODULE_ID, 'encryption_key', '');
        
        if (empty($key)) {
            $key = static::generateEncryptionKey();
            Option::set(static::MODULE_ID, 'encryption_key', $key);
        }
        
        return $key;
    }
    
    private static function generateEncryptionKey(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    public static function validateInput(array $data, array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = 'Поле обязательно для заполнения';
                continue;
            }
            
            if (!empty($value) && isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'string':
                        if (!is_string($value)) {
                            $errors[$field] = 'Поле должно быть строкой';
                        } elseif (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                            $errors[$field] = 'Длина поля не должна превышать ' . $rule['max_length'] . ' символов';
                        } elseif (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                            $errors[$field] = 'Длина поля должна быть не менее ' . $rule['min_length'] . ' символов';
                        }
                        break;
                        
                    case 'integer':
                        if (!is_numeric($value) || (int)$value != $value) {
                            $errors[$field] = 'Поле должно быть целым числом';
                        } elseif (isset($rule['min']) && (int)$value < $rule['min']) {
                            $errors[$field] = 'Значение должно быть не менее ' . $rule['min'];
                        } elseif (isset($rule['max']) && (int)$value > $rule['max']) {
                            $errors[$field] = 'Значение должно быть не более ' . $rule['max'];
                        }
                        break;
                        
                    case 'array':
                        if (!is_array($value)) {
                            $errors[$field] = 'Поле должно быть массивом';
                        }
                        break;
                        
                    case 'regex':
                        if (is_string($value) && isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                            $errors[$field] = $rule['message'] ?? 'Неверный формат поля';
                        }
                        break;
                }
            }
            
            if (!empty($value) && isset($rule['custom'])) {
                $customError = call_user_func($rule['custom'], $value);
                if ($customError !== true) {
                    $errors[$field] = $customError;
                }
            }
        }
        
        return $errors;
    }
    
    public static function sanitizeInput(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function sanitizeArray(array $input): array
    {
        $sanitized = [];
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = static::sanitizeInput($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = static::sanitizeArray($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
    
    public static function generateHash(array $data): string
    {
        ksort($data);
        return hash('sha256', serialize($data));
    }
    
    public static function verifyHash(array $data, string $hash): bool
    {
        return hash_equals($hash, static::generateHash($data));
    }
    
    public static function isValidTelegramBotToken(string $token): bool
    {
        return preg_match('/^\d+:[A-Za-z0-9_-]{35}$/', $token);
    }
    
    public static function isValidTelegramChatId(string $chatId): bool
    {
        return preg_match('/^-?\d+$/', trim($chatId));
    }
    
    public static function maskSensitiveData(string $data, int $visibleChars = 4): string
    {
        if (strlen($data) <= $visibleChars * 2) {
            return str_repeat('*', strlen($data));
        }
        
        $start = substr($data, 0, $visibleChars);
        $end = substr($data, -$visibleChars);
        $middle = str_repeat('*', strlen($data) - $visibleChars * 2);
        
        return $start . $middle . $end;
    }
} 