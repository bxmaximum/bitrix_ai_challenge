<?php

// OMUT_COMPOSER_AUTOLOAD — portable Composer vendor (site root)
$omutAutoloadCandidates = array(
    dirname(__DIR__, 3) . '/vendor/autoload.php', // layout B: www/local/php_interface
    dirname(__DIR__, 2) . '/vendor/autoload.php', // legacy flat
);
foreach ($omutAutoloadCandidates as $omutAutoload) {
    if (is_file($omutAutoload)) {
        require_once $omutAutoload;
        break;
    }
}

/**
 * Id инфоблока по символьному коду (для параметров стандартных компонентов).
 * Кэшируется на час средствами ORM.
 */
function lt_iblock_id(string $code): int
{
    if (!\Bitrix\Main\Loader::includeModule('iblock'))
    {
        return 0;
    }

    $row = \Bitrix\Iblock\IblockTable::getList([
        'select' => ['ID'],
        'filter' => ['=CODE' => $code],
        'limit' => 1,
        'cache' => ['ttl' => 3600],
    ])->fetch();

    return (int)($row['ID'] ?? 0);
}
