<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Publisher;

use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueHeaderOptionEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueOptionEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\RabbitQueueException;

use function is_int;
use function sprintf;

class DelayPublisher extends AbstractPublisher
{
    public const QUEUE_TYPE = QueueTypeEnum::FIFO | QueueTypeEnum::DELAY;

    protected function prepareOptions(DefinitionInterface $definition, array $options): array
    {
        $delay = $options[QueueOptionEnum::DELAY] ?? null;

        if (!is_int($delay)) {
            $message = sprintf(
                'Element for queue "%s" must be with option %s. See %s',
                $definition::getQueueName(),
                QueueOptionEnum::DELAY,
                QueueOptionEnum::class
            );

            throw new RabbitQueueException($message);
        }

        $amqpTableOption[QueueHeaderOptionEnum::X_DELAY] = $delay * 1000;

        return $amqpTableOption;
    }

    public static function getQueueType(): string
    {
        return (string) self::QUEUE_TYPE;
    }
}
