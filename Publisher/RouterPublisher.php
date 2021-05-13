<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Publisher;

use Wakeapp\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueHeaderOptionEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueOptionEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\RabbitQueueException;

class RouterPublisher extends AbstractPublisher
{
    public const QUEUE_TYPE = QueueTypeEnum::ROUTER;

    protected function prepareOptions(DefinitionInterface $definition, array $options): array
    {
        $amqpTableOption = [];

        if (isset($options[QueueOptionEnum::KEY])) {
            $amqpTableOption[QueueHeaderOptionEnum::X_DEDUPLICATION_HEADER] = $options[QueueOptionEnum::KEY];
        }

        if (isset($options[QueueOptionEnum::DELAY])) {
            $amqpTableOption[QueueHeaderOptionEnum::X_DELAY] = $options[QueueOptionEnum::DELAY] * 1000;
            $amqpTableOption[QueueHeaderOptionEnum::X_CACHE_TTL] = $options[QueueOptionEnum::DELAY] * 1000;
        }

        return array_merge($amqpTableOption, $options);
    }

    public static function getQueueType(): string
    {
        return (string) self::QUEUE_TYPE;
    }
}
