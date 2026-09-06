<?php

declare(strict_types=1);

namespace Bxmax\Booking\Infrastructure\Repository;

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;
use Bxmax\Booking\Domain\Repository\CatalogRepositoryInterface;
use CIBlockElement;

final class IblockCatalogRepository implements CatalogRepositoryInterface
{
    public function masterExists(int $masterId): bool
    {
        return $this->elementExists('masters', $masterId);
    }

    public function serviceExists(int $serviceId): bool
    {
        return $this->elementExists('services', $serviceId);
    }

    public function getServices(): array
    {
        return $this->loadElements('services', [
            'ID', 'NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'PROPERTY_PRICE', 'PROPERTY_DURATION',
        ], static function (array $item): array {
            return [
                'id' => (int)$item['ID'],
                'name' => (string)$item['NAME'],
                'previewText' => (string)($item['PREVIEW_TEXT'] ?? ''),
                'picture' => self::pictureSrc((int)($item['PREVIEW_PICTURE'] ?? 0)),
                'price' => (int)($item['PROPERTY_PRICE_VALUE'] ?? 0),
                'duration' => (int)($item['PROPERTY_DURATION_VALUE'] ?? 60),
            ];
        });
    }

    public function getMasters(): array
    {
        return $this->loadElements('masters', [
            'ID', 'NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'PROPERTY_SPECIALIZATION',
        ], static function (array $item): array {
            return [
                'id' => (int)$item['ID'],
                'name' => (string)$item['NAME'],
                'previewText' => (string)($item['PREVIEW_TEXT'] ?? ''),
                'picture' => self::pictureSrc((int)($item['PREVIEW_PICTURE'] ?? 0)),
                'specialization' => (string)($item['PROPERTY_SPECIALIZATION_VALUE'] ?? ''),
            ];
        });
    }

    public function getServiceName(int $serviceId): string
    {
        return $this->elementName('services', $serviceId);
    }

    public function getMasterName(int $masterId): string
    {
        return $this->elementName('masters', $masterId);
    }

    private function elementExists(string $code, int $id): bool
    {
        $iblockId = $this->iblockId($code);
        if ($iblockId <= 0 || $id <= 0) {
            return false;
        }

        $row = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'ID' => $id, 'ACTIVE' => 'Y'],
            false,
            ['nTopCount' => 1],
            ['ID']
        )->Fetch();

        return is_array($row);
    }

    private function elementName(string $code, int $id): string
    {
        $iblockId = $this->iblockId($code);
        if ($iblockId <= 0 || $id <= 0) {
            return '';
        }

        $row = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'ID' => $id],
            false,
            ['nTopCount' => 1],
            ['ID', 'NAME']
        )->Fetch();

        return is_array($row) ? (string)$row['NAME'] : '';
    }

    /**
     * @param list<string> $select
     * @param callable(array): array $map
     * @return list<array<string, mixed>>
     */
    private function loadElements(string $code, array $select, callable $map): array
    {
        $iblockId = $this->iblockId($code);
        if ($iblockId <= 0) {
            return [];
        }

        $result = [];
        $res = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'],
            false,
            false,
            $select
        );
        while ($item = $res->GetNext()) {
            $result[] = $map($item);
        }

        return $result;
    }

    private function iblockId(string $code): int
    {
        if (!Loader::includeModule('iblock')) {
            return 0;
        }

        $row = IblockTable::getRow([
            'filter' => ['=CODE' => $code],
            'select' => ['ID'],
        ]);

        return (int)($row['ID'] ?? 0);
    }

    private static function pictureSrc(int $fileId): string
    {
        if ($fileId <= 0) {
            return '';
        }

        $path = \CFile::GetPath($fileId);

        return is_string($path) ? $path : '';
    }
}
