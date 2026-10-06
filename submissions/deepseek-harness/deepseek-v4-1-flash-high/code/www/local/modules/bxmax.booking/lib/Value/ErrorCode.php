<?php

namespace Bxmax\Booking\Value;

/**
 * Error codes of the public booking API. They are part of the contract.
 */
final class ErrorCode
{
	public const MASTER_NOT_FOUND = 'MASTER_NOT_FOUND';
	public const SLOT_NOT_FOUND = 'SLOT_NOT_FOUND';
	public const SLOT_TAKEN = 'SLOT_TAKEN';
	public const VALIDATION = 'VALIDATION';
	public const CONSENT_REQUIRED = 'CONSENT_REQUIRED';

	private function __construct()
	{
	}
}
