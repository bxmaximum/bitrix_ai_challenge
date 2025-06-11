<?php
use Bitrix\Main\Application;

class DeliveryLog_20240609181000 extends \Sprint\Migration\Version
{
    protected $description = 'Создание таблицы логов доставки уведомлений barista.criticaleventstelegram';

    public function up()
    {
        $connection = Application::getConnection();
        $connection->queryExecute('CREATE TABLE IF NOT EXISTS b_barista_cet_delivery_log (
            ID int(11) NOT NULL AUTO_INCREMENT,
            UF_XML_ID varchar(255) NOT NULL,
            EVENT_HASH varchar(64) NOT NULL,
            SENT_AT datetime NOT NULL,
            RESULT varchar(16) NOT NULL,
            RAW text,
            PRIMARY KEY (ID),
            UNIQUE KEY UF_XML_ID (UF_XML_ID)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
    }

    public function down()
    {
        $connection = Application::getConnection();
        $connection->queryExecute('DROP TABLE IF EXISTS b_barista_cet_delivery_log;');
    }
} 