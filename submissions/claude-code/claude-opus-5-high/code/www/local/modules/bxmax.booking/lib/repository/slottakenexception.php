<?php

declare(strict_types=1);

namespace Bxmax\Booking\Repository;

use Bitrix\Main\SystemException;

/**
 * Слот уже занят: вставку отклонила база (уникальный индекс по SLOT_ID).
 */
final class SlotTakenException extends SystemException
{
}
