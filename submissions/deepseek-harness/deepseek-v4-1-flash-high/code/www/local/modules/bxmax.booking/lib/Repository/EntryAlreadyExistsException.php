<?php

namespace Bxmax\Booking\Repository;

/**
 * Thrown when the database refuses a second booking for the same slot.
 */
final class EntryAlreadyExistsException extends \RuntimeException
{
}
