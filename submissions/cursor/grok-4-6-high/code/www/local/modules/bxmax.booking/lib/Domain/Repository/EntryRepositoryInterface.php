<?php

declare(strict_types=1);

namespace Bxmax\Booking\Domain\Repository;

interface EntryRepositoryInterface
{
    public function getById(int $id): ?array;

    public function existsForSlot(int $slotId): bool;

    /**
     * @param array{
     *     SLOT_ID: int,
     *     SERVICE_ID: int,
     *     NAME: string,
     *     PHONE: string,
     *     CONSENT_AT: \Bitrix\Main\Type\DateTime
     * } $fields
     * @return array{id: int}|null
     */
    public function createExclusive(array $fields): ?array;

    public function delete(int $id): bool;

    /**
     * @return list<array<string, mixed>>
     */
    public function listAdmin(string $orderBy, string $orderDir, int $limit, int $offset): array;

    public function countAdmin(): int;
}
