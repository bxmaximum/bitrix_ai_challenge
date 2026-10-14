<?php

declare(strict_types=1);

namespace Bxmax\Booking\Dto;

/**
 * Услуга из инфоблока services.
 */
final class ServiceItem
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $price,
        public readonly int $duration,
        public readonly string $category,
        public readonly string $description,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'duration' => $this->duration,
            'category' => $this->category,
            'description' => $this->description,
        ];
    }
}
