<?php

declare(strict_types=1);

namespace Vendor\Favorites\Service;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Vendor\Favorites\Config\ModuleConfig;

/**
 * Проверка существования товара в выбранном инфоблоке каталога (D7 ORM).
 */
final class CatalogProductValidator
{
    public function existsInCatalog(int $productId): bool
    {
        if ($productId <= 0) {
            return false;
        }

        $iblockId = ModuleConfig::getCatalogIblockId();
        if ($iblockId <= 0) {
            return false;
        }

        if (!Loader::includeModule('iblock')) {
            return false;
        }

        $row = ElementTable::getRow([
            'filter' => [
                '=ID' => $productId,
                '=IBLOCK_ID' => $iblockId,
                '=ACTIVE' => 'Y',
            ],
            'select' => ['ID'],
        ]);

        return $row !== null;
    }
}
