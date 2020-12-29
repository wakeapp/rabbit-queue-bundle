<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Definition;

use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Wire\AMQPTable;

class ExampleDefinition implements DefinitionInterface
{
    public const QUEUE_NAME = QueueEnum::EXAMPLE;
    public const ENTRY_POINT = self::QUEUE_NAME . '@exchange_deduplication';

    private const SECOND_POINT = self::QUEUE_NAME . '@exchange_delay';
    private const THIRD_POINT = self::QUEUE_NAME;

    /**
     * {@inheritDoc}
     */
    public function init(AMQPStreamConnection $connection): void
    {
        $channel = $connection->channel();

        $channel->exchange_declare(
            self::ENTRY_POINT,
            'x-message-deduplication',
            false,
            true,
            false,
            false,
            false,
            new AMQPTable(['x-cache-size' => 1_000_000_000])
        );

        $channel->exchange_declare(
            self::SECOND_POINT,
            'x-delayed-message',
            false,
            true,
            false,
            false,
            false,
            new AMQPTable(['x-delayed-type' => AMQPExchangeType::DIRECT])
        );

        $channel->queue_declare(
            self::THIRD_POINT,
            false,
            true,
            false,
            false
        );

        $channel->exchange_bind(self::SECOND_POINT, self::ENTRY_POINT);
        $channel->queue_bind(self::THIRD_POINT, self::SECOND_POINT);
    }

    /**
     * {@inheritDoc}
     */
    public function getEntryPointName(): string
    {
        return self::ENTRY_POINT;
    }

    /**
     * {@inheritDoc}
     */
    public function getQueueType(): int
    {
        return QueueTypeEnum::FIFO | QueueTypeEnum::DEDUPLICATE | QueueTypeEnum::DELAY;
    }

    /**
     * {@inheritDoc}
     */
    public static function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }
}
