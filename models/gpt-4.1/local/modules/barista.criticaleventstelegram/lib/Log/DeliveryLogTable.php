<?php
namespace Barista\CriticalEventsTelegram\Log;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Localization\Loc;

class DeliveryLogTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_barista_cet_delivery_log';
    }

    public static function getMap(): array
    {
        return [
            'ID' => [
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
            ],
            'UF_XML_ID' => [
                'data_type' => 'string',
                'required' => true,
            ],
            'EVENT_HASH' => [
                'data_type' => 'string',
                'required' => true,
            ],
            'SENT_AT' => [
                'data_type' => 'datetime',
                'required' => true,
            ],
            'RESULT' => [
                'data_type' => 'string',
                'required' => true,
            ],
            'RAW' => [
                'data_type' => 'text',
            ],
        ];
    }
} 