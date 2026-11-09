<?php

declare(strict_types=1);

namespace Bxmax\Booking\Repository;

/**
 * База отклонила заявку: на слот уже есть запись (уникальный индекс по SLOT_ID).
 */
final class SlotOccupiedException extends \RuntimeException
{
}
