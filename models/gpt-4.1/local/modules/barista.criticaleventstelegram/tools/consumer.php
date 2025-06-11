<?php
use Barista\CriticalEventsTelegram\Service\TelegramService;

if (file_exists($_SERVER['DOCUMENT_ROOT'].'/local/vendor/autoload.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'].'/local/vendor/autoload.php';
}

$connection = new PhpAmqpLib\Connection\AMQPStreamConnection(
    getenv('RABBITMQ_HOST') ?: 'localhost',
    getenv('RABBITMQ_PORT') ?: 5672,
    getenv('RABBITMQ_USER') ?: 'guest',
    getenv('RABBITMQ_PASS') ?: 'guest'
);
$channel = $connection->channel();
$queueName = 'barista_critical_events';
$channel->queue_declare($queueName, false, true, false, false);

$callback = function ($msg) {
    $event = json_decode($msg->body, true);
    $service = new TelegramService();
    $result = $service->send($event);
    $log = date('c')." ".($result ? 'OK' : 'FAIL')." ".json_encode($event)."\n";
    file_put_contents(__DIR__.'/../logs/telegram.log', $log, FILE_APPEND);
    $msg->ack();
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume($queueName, '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}
$channel->close();
$connection->close(); 