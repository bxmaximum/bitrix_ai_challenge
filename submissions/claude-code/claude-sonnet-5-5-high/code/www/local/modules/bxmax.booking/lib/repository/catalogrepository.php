<?php

declare(strict_types=1);

namespace Bxmax\Booking\Repository;

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\DataManager;

/**
 * Чтение услуг и мастеров из инфоблоков `services` и `masters`
 * (скомпилированные ORM-классы по API_CODE).
 */
final class CatalogRepository
{
    public const IBLOCK_SERVICES = 'services';
    public const IBLOCK_MASTERS = 'masters';

    /**
     * @return list<array{id: int, name: string, price: int, duration: int}>
     */
    public function getServices(): array
    {
        $class = $this->dataClass(self::IBLOCK_SERVICES);
        if ($class === null)
        {
            return [];
        }

        $rows = $class::query()
            ->setSelect(['ID', 'NAME', 'PRICE_VALUE' => 'PRICE.VALUE', 'DURATION_VALUE' => 'DURATION.VALUE'])
            ->where('ACTIVE', 'Y')
            ->setOrder(['SORT' => 'ASC', 'ID' => 'ASC'])
            ->fetchAll();

        return array_map(static fn (array $row): array => [
            'id' => (int)$row['ID'],
            'name' => (string)$row['NAME'],
            'price' => (int)round((float)($row['PRICE_VALUE'] ?? 0)),
            'duration' => (int)round((float)($row['DURATION_VALUE'] ?? 0)),
        ], $rows);
    }

    /**
     * @return list<array{id: int, name: string, specialization: string}>
     */
    public function getMasters(): array
    {
        $class = $this->dataClass(self::IBLOCK_MASTERS);
        if ($class === null)
        {
            return [];
        }

        $rows = $class::query()
            ->setSelect(['ID', 'NAME', 'SPECIALIZATION_VALUE' => 'SPECIALIZATION.VALUE'])
            ->where('ACTIVE', 'Y')
            ->setOrder(['SORT' => 'ASC', 'ID' => 'ASC'])
            ->fetchAll();

        return array_map(static fn (array $row): array => [
            'id' => (int)$row['ID'],
            'name' => (string)$row['NAME'],
            'specialization' => (string)($row['SPECIALIZATION_VALUE'] ?? ''),
        ], $rows);
    }

    /**
     * @return list<int>
     */
    public function getMasterIds(): array
    {
        return array_column($this->getMasters(), 'id');
    }

    public function findMaster(int $id): ?array
    {
        foreach ($this->getMasters() as $master)
        {
            if ($master['id'] === $id)
            {
                return $master;
            }
        }

        return null;
    }

    public function findService(int $id): ?array
    {
        foreach ($this->getServices() as $service)
        {
            if ($service['id'] === $id)
            {
                return $service;
            }
        }

        return null;
    }

    /**
     * @return class-string<DataManager>|null
     */
    private function dataClass(string $apiCode): ?string
    {
        if (!Loader::includeModule('iblock'))
        {
            return null;
        }

        $entity = IblockTable::compileEntity($apiCode);

        return $entity ? $entity->getDataClass() : null;
    }
}
