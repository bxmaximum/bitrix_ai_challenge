<?php

declare(strict_types=1);

namespace Bxmax\Booking\Infrastructure\Repository;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

final class ContentRepository
{
    private const MODULE_ID = 'bxmax.booking';

    public function getServices(): array
    {
        return $this->getItems((int)Option::get(self::MODULE_ID, 'services_iblock_id', 0), ['PRICE', 'DURATION']);
    }

    public function getMasters(): array
    {
        return $this->getItems((int)Option::get(self::MODULE_ID, 'masters_iblock_id', 0), ['SPECIALTY', 'EXPERIENCE']);
    }

    public function serviceExists(int $id): bool
    {
        return $this->elementExists($id, (int)Option::get(self::MODULE_ID, 'services_iblock_id', 0));
    }

    public function masterExists(int $id): bool
    {
        return $this->elementExists($id, (int)Option::get(self::MODULE_ID, 'masters_iblock_id', 0));
    }

    public function getService(int $id): ?array
    {
        foreach ($this->getServices() as $service)
        {
            if ((int)$service['ID'] === $id)
            {
                return $service;
            }
        }

        return null;
    }

    public function getMaster(int $id): ?array
    {
        foreach ($this->getMasters() as $master)
        {
            if ((int)$master['ID'] === $id)
            {
                return $master;
            }
        }

        return null;
    }

    private function elementExists(int $id, int $iblockId): bool
    {
        if ($id <= 0 || $iblockId <= 0 || !Loader::includeModule('iblock'))
        {
            return false;
        }

        return (bool)\CIBlockElement::GetList([], [
            '=ID' => $id,
            '=IBLOCK_ID' => $iblockId,
            '=ACTIVE' => 'Y',
        ], false, ['nTopCount' => 1], ['ID'])->Fetch();
    }

    private function getItems(int $iblockId, array $propertyCodes): array
    {
        if ($iblockId <= 0 || !Loader::includeModule('iblock'))
        {
            return [];
        }

        $items = [];
        $result = \CIBlockElement::GetList(
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            ['=IBLOCK_ID' => $iblockId, '=ACTIVE' => 'Y'],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'PREVIEW_TEXT', 'PREVIEW_PICTURE'],
        );

        while ($element = $result->GetNextElement())
        {
            $fields = $element->GetFields();
            $properties = $element->GetProperties();
            $item = [
                'ID' => (int)$fields['ID'],
                'NAME' => (string)$fields['NAME'],
                'CODE' => (string)$fields['CODE'],
                'DESCRIPTION' => (string)$fields['PREVIEW_TEXT'],
                'IMAGE' => $fields['PREVIEW_PICTURE'] ? (string)\CFile::GetPath((int)$fields['PREVIEW_PICTURE']) : '',
            ];

            foreach ($propertyCodes as $code)
            {
                $item[$code] = (string)($properties[$code]['VALUE'] ?? '');
            }
            $items[] = $item;
        }

        return $items;
    }
}
