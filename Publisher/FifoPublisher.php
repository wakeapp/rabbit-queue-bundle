<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Publisher;

use Wakeapp\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;

class FifoPublisher extends AbstractPublisher
{
    public const QUEUE_TYPE = QueueTypeEnum::FIFO;

    protected function prepareOptions(DefinitionInterface $definition, array $options): array
    {
        return $options;
    }

    public static function getQueueType(): string
    {
        return (string) self::QUEUE_TYPE;
    }
}
