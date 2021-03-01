<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Publisher;

use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueHeaderOptionEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueOptionEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\RabbitQueueException;

use function is_string;
use function is_int;
use function sprintf;

class DeduplicateDelayPublisher extends AbstractPublisher
{
    public const QUEUE_TYPE = QueueTypeEnum::FIFO | QueueTypeEnum::DELAY | QueueTypeEnum::DEDUPLICATE;

    protected function prepareOptions(DefinitionInterface $definition, array $options): array
    {
        if (!is_string($options[QueueOptionEnum::KEY]) || !is_int($options[QueueOptionEnum::DELAY])) {
            $message = sprintf(
                'Element for queue "%s" must be with options %s/%s. See %s',
                $definition::getQueueName(),
                QueueOptionEnum::KEY,
                QueueOptionEnum::DELAY,
                QueueOptionEnum::class
            );

            throw new RabbitQueueException($message);
        }

        $amqpTableOption[QueueHeaderOptionEnum::X_DEDUPLICATION_HEADER] = $options[QueueOptionEnum::KEY];
        $amqpTableOption[QueueHeaderOptionEnum::X_DELAY] = $options[QueueOptionEnum::DELAY] * 1000;
        $amqpTableOption[QueueHeaderOptionEnum::X_CACHE_TTL] = $options[QueueOptionEnum::DELAY] * 1000;
        
        return $amqpTableOption;
    }

    public static function getQueueType(): string
    {
        return (string) self::QUEUE_TYPE;
    }
}
