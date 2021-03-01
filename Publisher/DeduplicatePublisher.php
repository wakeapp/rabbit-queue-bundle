<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Publisher;

use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueHeaderOptionEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueOptionEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\RabbitQueueException;

use function sprintf;

class DeduplicatePublisher extends AbstractPublisher
{
    public const QUEUE_TYPE = QueueTypeEnum::FIFO | QueueTypeEnum::DEDUPLICATE;

    /**
     * @throws RabbitQueueException
     */
    protected function prepareOptions(DefinitionInterface $definition, array $options): array
    {
        if (!is_string($options[QueueOptionEnum::KEY])) {
            $message = sprintf(
                'Element for queue "%s" must be with option %s. See %s',
                $definition::getQueueName(),
                QueueOptionEnum::KEY,
                QueueOptionEnum::class
            );

            throw new RabbitQueueException($message);
        }

        $amqpTableOption[QueueHeaderOptionEnum::X_DEDUPLICATION_HEADER] = $options[QueueOptionEnum::KEY];

        return $amqpTableOption;
    }

    public static function getQueueType(): string
    {
        return (string) self::QUEUE_TYPE;
    }
}
