<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Consumer;

use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueEnum;

class ExampleConsumer extends AbstractConsumer
{
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
        return QueueEnum::EXAMPLE_DEDUPLICATE_DELAY;
    }

    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return QueueEnum::EXAMPLE_DEDUPLICATE_DELAY;
    }
}
