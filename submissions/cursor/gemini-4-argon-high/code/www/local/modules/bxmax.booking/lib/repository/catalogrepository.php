<?php declare(strict_types=1);

namespace Bxmax\Booking\Repository;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;

final class CatalogRepository
{
    public const SERVICES_IBLOCK_CODE = 'services';
    public const MASTERS_IBLOCK_CODE = 'masters';

    /** @var array<string, int|null> */
    private array $iblockIdCache = [];

    public function getIblockIdByCode(string $code): ?int
    {
        if (array_key_exists($code, $this->iblockIdCache))
        {
            return $this->iblockIdCache[$code];
        }

        if (!Loader::includeModule('iblock'))
        {
            return null;
        }

        $row = IblockTable::query()
            ->setSelect(['ID'])
            ->where('CODE', $code)
            ->setLimit(1)
            ->fetch();

        $this->iblockIdCache[$code] = $row ? (int)$row['ID'] : null;

        return $this->iblockIdCache[$code];
    }

    public function findActiveMasterById(int $masterId): ?array
    {
        if ($masterId <= 0)
        {
            return null;
        }

        $masters = $this->listActiveMasters();
        foreach ($masters as $master)
        {
            if ($master['id'] === $masterId)
            {
                return $master;
            }
        }

        return null;
    }

    public function findActiveServiceById(int $serviceId): ?array
    {
        if ($serviceId <= 0)
        {
            return null;
        }

        $services = $this->listActiveServices();
        foreach ($services as $service)
        {
            if ($service['id'] === $serviceId)
            {
                return $service;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     code: string,
     *     description: string,
     *     specialization: string,
     *     experience: string,
     *     badge: string,
     *     instagram: string,
     *     photoUrl: string
     * }>
     */
    public function listActiveMasters(): array
    {
        $iblockId = $this->getIblockIdByCode(self::MASTERS_IBLOCK_CODE);
        if ($iblockId === null)
        {
            return [];
        }

        $elements = $this->fetchElementsWithProperties($iblockId);
        $result = [];

        foreach ($elements as $el)
        {
            $props = $el['PROPERTIES'];
            $photoUrl = $el['PREVIEW_PICTURE_SRC'] ?: ($props['PHOTO_PATH'] ?? '');

            $result[] = [
                'id' => (int)$el['ID'],
                'name' => (string)$el['NAME'],
                'code' => (string)$el['CODE'],
                'description' => (string)$el['PREVIEW_TEXT'],
                'specialization' => (string)($props['SPECIALIZATION'] ?? 'Топ-мастер ногтевого сервиса'),
                'experience' => (string)($props['EXPERIENCE'] ?? ''),
                'badge' => (string)($props['BADGE'] ?? ''),
                'instagram' => (string)($props['INSTAGRAM'] ?? ''),
                'photoUrl' => (string)$photoUrl,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     code: string,
     *     description: string,
     *     price: int,
     *     priceFormatted: string,
     *     duration: string,
     *     category: string,
     *     photoUrl: string
     * }>
     */
    public function listActiveServices(): array
    {
        $iblockId = $this->getIblockIdByCode(self::SERVICES_IBLOCK_CODE);
        if ($iblockId === null)
        {
            return [];
        }

        $elements = $this->fetchElementsWithProperties($iblockId);
        $result = [];

        foreach ($elements as $el)
        {
            $props = $el['PROPERTIES'];
            $price = (int)($props['PRICE'] ?? 0);
            $photoUrl = $el['PREVIEW_PICTURE_SRC'] ?: ($props['PHOTO_PATH'] ?? '');

            $result[] = [
                'id' => (int)$el['ID'],
                'name' => (string)$el['NAME'],
                'code' => (string)$el['CODE'],
                'description' => (string)$el['PREVIEW_TEXT'],
                'price' => $price,
                'priceFormatted' => number_format($price, 0, '.', ' ') . ' ₽',
                'duration' => (string)($props['DURATION'] ?? '60 мин'),
                'category' => (string)($props['CATEGORY'] ?? 'Маникюр'),
                'photoUrl' => (string)$photoUrl,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    public function getMastersMap(): array
    {
        $map = [];
        foreach ($this->listActiveMasters() as $master)
        {
            $map[$master['id']] = $master['name'];
        }

        return $map;
    }

    /**
     * @return array<int, array{name: string, price: int, priceFormatted: string}>
     */
    public function getServicesMap(): array
    {
        $map = [];
        foreach ($this->listActiveServices() as $service)
        {
            $map[$service['id']] = [
                'name' => $service['name'],
                'price' => $service['price'],
                'priceFormatted' => $service['priceFormatted'],
            ];
        }

        return $map;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchElementsWithProperties(int $iblockId): array
    {
        if (!Loader::includeModule('iblock'))
        {
            return [];
        }

        $rows = ElementTable::query()
            ->setSelect(['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'SORT', 'PREVIEW_TEXT', 'PREVIEW_PICTURE'])
            ->where('IBLOCK_ID', $iblockId)
            ->where('ACTIVE', 'Y')
            ->setOrder(['SORT' => 'ASC', 'ID' => 'ASC'])
            ->fetchAll();

        if (empty($rows))
        {
            return [];
        }

        $propMeta = [];
        $propRows = PropertyTable::query()
            ->setSelect(['ID', 'CODE'])
            ->where('IBLOCK_ID', $iblockId)
            ->where('ACTIVE', 'Y')
            ->fetchAll();
        foreach ($propRows as $p)
        {
            if ($p['CODE'] !== '')
            {
                $propMeta[(int)$p['ID']] = (string)$p['CODE'];
            }
        }

        $items = [];
        foreach ($rows as $row)
        {
            $elementId = (int)$row['ID'];
            $pictureSrc = '';
            if ((int)$row['PREVIEW_PICTURE'] > 0)
            {
                $file = \CFile::GetFileArray((int)$row['PREVIEW_PICTURE']);
                if (is_array($file) && !empty($file['SRC']))
                {
                    $pictureSrc = (string)$file['SRC'];
                }
            }

            // Legacy CIBlockElement::GetProperty used here to read element property values across storage v1/v2 without requiring compiled entity classes
            $props = [];
            $dbProps = \CIBlockElement::GetProperty($iblockId, $elementId, ['sort' => 'asc'], ['ACTIVE' => 'Y']);
            while ($prop = $dbProps->Fetch())
            {
                $code = (string)($prop['CODE'] ?: ($propMeta[(int)$prop['ID']] ?? ''));
                if ($code !== '')
                {
                    $props[$code] = $prop['VALUE'];
                }
            }

            $items[] = [
                'ID' => $elementId,
                'NAME' => (string)$row['NAME'],
                'CODE' => (string)$row['CODE'],
                'SORT' => (int)$row['SORT'],
                'PREVIEW_TEXT' => (string)$row['PREVIEW_TEXT'],
                'PREVIEW_PICTURE_SRC' => $pictureSrc,
                'PROPERTIES' => $props,
            ];
        }

        return $items;
    }
}
