<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Client;

use ErrorException;
use Exception;
use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPOutOfBoundsException;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMqClient
{
    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;

    public function __construct(
        AMQPStreamConnection $connection
    ) {
        $this->connection = $connection;
        $this->channel = $connection->channel();
    }

    public function isConsuming(): bool
    {
        return $this->channel->is_consuming();
    }

    /**
     * @throws AMQPOutOfBoundsException
     * @throws AMQPRuntimeException
     * @throws AMQPTimeoutException
     * @throws ErrorException
     */
    public function wait()
    {
        return $this->channel->wait();
    }

    /**
     * @throws AMQPTimeoutException
     * @throws InvalidArgumentException
     */
    public function consume(string $queueName, string $consumerName, callable $handler): string
    {
        return $this->channel->basic_consume(
            $queueName,
            $consumerName,
            false,
            false,
            false,
            false,
            $handler
        );
    }

    /**
     * @throws AMQPTimeoutException
     */
    public function qos(int $batchSize)
    {
        return $this->channel->basic_qos(null, $batchSize, null);
    }

    public function countNotTakenMessages(string $queueName)
    {
        [$queue, $messageCount, $consumerCount] = $this->channel->queue_declare($queueName, true);

        return $messageCount;
    }

    /**
     * @param string $queueName
     * @param AMQPMessage[] $messageList
     */
    public function rewindList(
        string $queueName,
        array $messageList
    ): void {
        $this->ackList($messageList);

        foreach ($messageList as $message) {
            $this->channel->batch_basic_publish($message, '', $queueName);
        }

        $this->channel->publish_batch();
    }

    /**
     * @param AMQPMessage[] $messageList
     */
    public function ackList(array $messageList): void
    {
        foreach ($messageList as $message) {
            $deliveryTag = $message->getDeliveryTag();

            $channel = $message->getChannel();
            $channel = $channel ?: $this->channel;

            $channel->basic_ack($deliveryTag);
        }
    }

    /**
     * @param AMQPMessage[] $messageList
     * @param bool $multiple
     * @param bool $requeue
     */
    public function nackList(array $messageList, bool $requeue = true): void
    {
        foreach ($messageList as $message) {
            $deliveryTag = $message->getDeliveryTag();

            $channel = $message->getChannel();
            $channel = $channel ?: $this->channel;

            $channel->basic_nack($deliveryTag, false, $requeue);
        }
    }

    /**
     * @param AMQPMessage[] $messageList
     * @param bool $requeue
     */
    public function rejectList(array $messageList, bool $requeue = true): void
    {
        foreach ($messageList as $message) {
            $deliveryTag = $message->getDeliveryTag();

            $channel = $message->getChannel();
            $channel = $channel ?: $this->channel;

            $channel->basic_reject($deliveryTag, $requeue);
        }
    }

    /**
     * @throws Exception
     */
    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
