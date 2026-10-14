<?php

declare(strict_types=1);

namespace Bxmax\Booking\Dto;

/**
 * Мастер из инфоблока masters.
 */
final class MasterItem
{
    /**
     * @param int[] $serviceIds Услуги, которые делает мастер; пустой список — делает все.
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $specialization,
        public readonly array $serviceIds,
        public readonly int $photoId,
    )
    {
    }

    public function canPerform(int $serviceId): bool
    {
        return $this->serviceIds === [] || in_array($serviceId, $this->serviceIds, true);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'specialization' => $this->specialization,
            'serviceIds' => $this->serviceIds,
        ];
    }
}
