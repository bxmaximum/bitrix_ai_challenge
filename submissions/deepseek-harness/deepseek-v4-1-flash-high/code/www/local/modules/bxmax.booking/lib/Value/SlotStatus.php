<?php

namespace Bxmax\Booking\Value;

/**
 * Public slot status as defined by the booking API contract.
 */
final class SlotStatus
{
	public const FREE = 'free';
	public const TAKEN = 'taken';

	private function __construct()
	{
	}
}
