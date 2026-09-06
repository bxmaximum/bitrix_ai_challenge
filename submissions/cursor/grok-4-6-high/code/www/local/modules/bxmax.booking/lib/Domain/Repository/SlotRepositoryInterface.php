<?php

declare(strict_types=1);

namespace Bxmax\Booking\Domain\Repository;

interface SlotRepositoryInterface
{
    public function getById(int $id): ?array;

    public function existsForMaster(int $masterId): bool;

    /**
     * @return list<array<string, mixed>>
     */
    public function listForMasterWeek(int $masterId, \DateTimeInterface $weekStart, \DateTimeInterface $weekEnd): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function listAdmin(?int $masterId, ?\DateTimeInterface $dateFrom, ?\DateTimeInterface $dateTo, string $orderBy, string $orderDir, int $limit, int $offset): array;

    public function countAdmin(?int $masterId, ?\DateTimeInterface $dateFrom, ?\DateTimeInterface $dateTo): int;

    public function setClosed(int $id, bool $closed): bool;

    /**
     * @param list<array{MASTER_ID: int, STARTS_AT: \Bitrix\Main\Type\DateTime, ENDS_AT: \Bitrix\Main\Type\DateTime}> $rows
     */
    public function addMany(array $rows): void;
}
