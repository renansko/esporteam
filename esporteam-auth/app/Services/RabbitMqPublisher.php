<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMqPublisher
{
    private ?AMQPStreamConnection $connection = null;
    private ?\PhpAmqpLib\Channel\AMQPChannel $channel = null;

    public function publish(string $exchange, string $routingKey, array $payload): void
    {
        $this->ensureConnection();

        $this->channel->exchange_declare($exchange, 'topic', false, true, false);

        $message = new AMQPMessage(json_encode($payload), [
            'delivery_mode'  => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'content_type'   => 'application/json',
        ]);

        $this->channel->basic_publish($message, $exchange, $routingKey);
    }

    private function ensureConnection(): void
    {
        if ($this->connection && $this->connection->isConnected()) {
            return;
        }

        $config = config('rabbitmq');

        $this->connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost'],
        );

        $this->channel = $this->connection->channel();
    }

    public function __destruct()
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (\Throwable) {
        }
    }
}
