<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Consumer;

use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueEnum;

class ExampleFifoConsumer extends AbstractConsumer
{
    public const DEFAULT_BATCH_SIZE = 100;

    /**
     * {@inheritDoc}
     */
    public function process(array $messageList): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getBindQueueName(): string
    {
        return QueueEnum::EXAMPLE_FIFO;
    }

    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return QueueEnum::EXAMPLE_FIFO;
    }
}
