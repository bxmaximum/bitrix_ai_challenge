<?php

declare(strict_types=1);

namespace Bxmax\Booking\Dto;

final readonly class CreateBookingRequest
{
    private const TRUE_VALUES = ['1', 'y', 'yes', 'true', 'on'];

    public function __construct(
        public int $slotId,
        public int $serviceId,
        public string $name,
        public string $phone,
        public bool $consent,
    ) {}

    /**
     * Строит запрос из «сырых» значений транспорта (строки формы или JSON-скаляры).
     */
    public static function fromRaw(mixed $slotId, mixed $serviceId, mixed $name, mixed $phone, mixed $consent): self
    {
        return new self(
            slotId: (int)$slotId,
            serviceId: (int)$serviceId,
            name: is_scalar($name) ? (string)$name : '',
            phone: is_scalar($phone) ? (string)$phone : '',
            consent: is_scalar($consent) && in_array(mb_strtolower(trim((string)$consent)), self::TRUE_VALUES, true),
        );
    }
}
