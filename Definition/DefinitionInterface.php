<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Definition;

use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use PhpAmqpLib\Connection\AMQPStreamConnection;

interface DefinitionInterface
{
    public const TAG = 'wakeapp_rabbit_queue.definition';

    /**
     * Declare definition to Rabbit MQ.
     * If definition is already exist, it will skip.
     */
    public function init(AMQPStreamConnection $connection);

    /**
     * Get queue name or exchange name which is a entry point for to handle message
     */
    public function getEntryPointName(): string;

    /**
     * Get queue type.
     * Allow combine types.
     * ex. QueueTypeEnum::FIFO | QueueTypeEnum::DEDUPLICATE | QueueTypeEnum::DELAY
     *
     * @see QueueTypeEnum
     */
    public function getQueueType(): int;

    /**
     * Queue name which is a storage for messages
     */
    public static function getQueueName(): string;
}
