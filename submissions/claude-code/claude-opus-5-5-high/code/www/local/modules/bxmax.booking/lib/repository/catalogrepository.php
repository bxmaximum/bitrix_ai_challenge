<?php

declare(strict_types=1);

namespace Bxmax\Booking\Repository;

use Bitrix\Iblock\Iblock;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;
use Bxmax\Booking\Dto\MasterItem;
use Bxmax\Booking\Dto\ServiceItem;

/**
 * Чтение справочников услуг и мастеров из инфоблоков (символьные коды services и masters).
 * Инфоблоки создаёт проектная миграция sprint.migration; модуль их только читает.
 */
class CatalogRepository
{
    public const SERVICES_IBLOCK_CODE = 'services';
    public const MASTERS_IBLOCK_CODE = 'masters';

    /** @var array<string, string|null> */
    private array $dataClasses = [];

    /**
     * @return ServiceItem[]
     */
    public function getServices(): array
    {
        return $this->fetchServices(['=ACTIVE' => 'Y']);
    }

    public function getService(int $id): ?ServiceItem
    {
        if ($id <= 0)
        {
            return null;
        }

        return $this->fetchServices(['=ID' => $id, '=ACTIVE' => 'Y'])[0] ?? null;
    }

    /**
     * @return MasterItem[]
     */
    public function getMasters(): array
    {
        return $this->fetchMasters(['=ACTIVE' => 'Y']);
    }

    public function getMaster(int $id): ?MasterItem
    {
        if ($id <= 0)
        {
            return null;
        }

        return $this->fetchMasters(['=ID' => $id, '=ACTIVE' => 'Y'])[0] ?? null;
    }

    /**
     * Имена мастеров по ID, включая неактивных (для админки и писем).
     *
     * @param int[] $ids
     * @return array<int, string>
     */
    public function getMasterNames(array $ids = []): array
    {
        return $this->getNames(self::MASTERS_IBLOCK_CODE, $ids);
    }

    /**
     * @param int[] $ids
     * @return array<int, string>
     */
    public function getServiceNames(array $ids = []): array
    {
        return $this->getNames(self::SERVICES_IBLOCK_CODE, $ids);
    }

    /**
     * ID инфоблоков справочников — для тегированного кеша.
     *
     * @return int[]
     */
    public function getIblockIds(): array
    {
        if (!Loader::includeModule('iblock'))
        {
            return [];
        }

        $rows = IblockTable::getList([
            'select' => ['ID'],
            'filter' => ['@CODE' => [self::SERVICES_IBLOCK_CODE, self::MASTERS_IBLOCK_CODE]],
        ])->fetchAll();

        return array_map('intval', array_column($rows, 'ID'));
    }

    /**
     * @return ServiceItem[]
     */
    private function fetchServices(array $filter): array
    {
        $dataClass = $this->getDataClass(self::SERVICES_IBLOCK_CODE);
        if ($dataClass === null)
        {
            return [];
        }

        $elements = $dataClass::getList([
            'select' => ['ID', 'NAME', 'PREVIEW_TEXT', 'PRICE', 'DURATION', 'CATEGORY'],
            'filter' => $filter,
            'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
        ])->fetchCollection();

        $result = [];
        foreach ($elements as $element)
        {
            $result[] = new ServiceItem(
                (int)$element->getId(),
                (string)$element->getName(),
                (int)$element->getPrice()?->getValue(),
                (int)$element->getDuration()?->getValue(),
                (string)$element->getCategory()?->getValue(),
                (string)$element->getPreviewText(),
            );
        }

        return $result;
    }

    /**
     * @return MasterItem[]
     */
    private function fetchMasters(array $filter): array
    {
        $dataClass = $this->getDataClass(self::MASTERS_IBLOCK_CODE);
        if ($dataClass === null)
        {
            return [];
        }

        $elements = $dataClass::getList([
            'select' => ['ID', 'NAME', 'PREVIEW_PICTURE', 'SPECIALIZATION', 'SERVICES'],
            'filter' => $filter,
            'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
        ])->fetchCollection();

        $result = [];
        foreach ($elements as $element)
        {
            $serviceIds = [];
            foreach ($element->getServices()->getAll() as $value)
            {
                $serviceIds[] = (int)$value->getValue();
            }

            $result[] = new MasterItem(
                (int)$element->getId(),
                (string)$element->getName(),
                (string)$element->getSpecialization()?->getValue(),
                array_values(array_unique(array_filter($serviceIds))),
                (int)$element->getPreviewPicture(),
            );
        }

        return $result;
    }

    /**
     * @param int[] $ids
     * @return array<int, string>
     */
    private function getNames(string $iblockCode, array $ids): array
    {
        $dataClass = $this->getDataClass($iblockCode);
        if ($dataClass === null)
        {
            return [];
        }

        $ids = array_values(array_filter(array_map('intval', $ids)));
        $filter = $ids ? ['@ID' => $ids] : [];

        $names = [];
        $rows = $dataClass::getList([
            'select' => ['ID', 'NAME'],
            'filter' => $filter,
            'order' => ['SORT' => 'ASC', 'NAME' => 'ASC'],
        ]);
        while ($row = $rows->fetch())
        {
            $names[(int)$row['ID']] = (string)$row['NAME'];
        }

        return $names;
    }

    /**
     * @return class-string<\Bitrix\Iblock\ORM\CommonElementTable>|null
     */
    private function getDataClass(string $iblockCode): ?string
    {
        if (array_key_exists($iblockCode, $this->dataClasses))
        {
            return $this->dataClasses[$iblockCode];
        }

        $this->dataClasses[$iblockCode] = null;
        if (!Loader::includeModule('iblock'))
        {
            return null;
        }

        $iblock = IblockTable::getList([
            'select' => ['ID', 'API_CODE'],
            'filter' => ['=CODE' => $iblockCode],
            'limit' => 1,
            'cache' => ['ttl' => 3600],
        ])->fetch();

        if ($iblock && (string)$iblock['API_CODE'] !== '')
        {
            $this->dataClasses[$iblockCode] = Iblock::wakeUp((int)$iblock['ID'])->getEntityDataClass();
        }

        return $this->dataClasses[$iblockCode];
    }
}
