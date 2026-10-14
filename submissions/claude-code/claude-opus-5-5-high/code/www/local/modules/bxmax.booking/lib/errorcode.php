<?php

declare(strict_types=1);

namespace Bxmax\Booking;

/**
 * Коды ошибок публичного API (контракт).
 */
final class ErrorCode
{
    public const MASTER_NOT_FOUND = 'MASTER_NOT_FOUND';
    public const SLOT_NOT_FOUND = 'SLOT_NOT_FOUND';
    public const SLOT_TAKEN = 'SLOT_TAKEN';
    public const VALIDATION = 'VALIDATION';
    public const CONSENT_REQUIRED = 'CONSENT_REQUIRED';
    public const ENTRY_NOT_FOUND = 'ENTRY_NOT_FOUND';
    public const INTERNAL = 'INTERNAL';
}
