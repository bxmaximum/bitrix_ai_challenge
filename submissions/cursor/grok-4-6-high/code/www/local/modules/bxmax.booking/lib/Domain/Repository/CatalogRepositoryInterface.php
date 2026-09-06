<?php

declare(strict_types=1);

namespace Bxmax\Booking\Domain\Repository;

interface CatalogRepositoryInterface
{
    public function masterExists(int $masterId): bool;

    public function serviceExists(int $serviceId): bool;

    /**
     * @return list<array{id: int, name: string, previewText: string, picture: string, price: int, duration: int}>
     */
    public function getServices(): array;

    /**
     * @return list<array{id: int, name: string, previewText: string, picture: string, specialization: string}>
     */
    public function getMasters(): array;

    public function getServiceName(int $serviceId): string;

    public function getMasterName(int $masterId): string;
}
