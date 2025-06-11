<?php
namespace Barista\CriticalEventsTelegram\Service;

use Bitrix\Main\Config\Option;

if (file_exists($_SERVER['DOCUMENT_ROOT'].'/local/vendor/autoload.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'].'/local/vendor/autoload.php';
}

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class QueueService
{
    private string $host;
    private int $port;
    private string $user;
    private string $pass;
    private string $queueName = 'barista_critical_events';

    public function __construct()
    {
        $moduleId = 'barista.criticaleventstelegram';
        $this->host = Option::get($moduleId, 'rabbitmq_host', 'localhost');
        $this->port = (int)Option::get($moduleId, 'rabbitmq_port', '5672');
        $this->user = Option::get($moduleId, 'rabbitmq_user', 'guest');
        $this->pass = Option::get($moduleId, 'rabbitmq_pass', 'guest');
    }

    public function send(array $event): bool
    {
        $connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->pass);
        $channel = $connection->channel();
        $channel->queue_declare($this->queueName, false, true, false, false);
        $msg = new AMQPMessage(json_encode($event), ['delivery_mode' => 2]);
        $channel->basic_publish($msg, '', $this->queueName);
        $channel->close();
        $connection->close();
        return true;
    }
} 