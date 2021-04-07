<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Consumer;

use Wakeapp\Bundle\RabbitQueueBundle\Exception\HydratorNotFoundException;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\HydratorRegistry;
use PhpAmqpLib\Message\AMQPMessage;

abstract class AbstractConsumer implements ConsumerInterface
{
    public const DEFAULT_BATCH_SIZE = 1;
    public const DEFAULT_MAX_PROCESSED_TASKS_COUNT = 1000;

    private HydratorRegistry $hydratorRegistry;

    public function __construct(HydratorRegistry $hydratorRegistry)
    {
        $this->hydratorRegistry = $hydratorRegistry;
    }

    private bool $propagationStopped = false;
    private int $taskCounter = 0;

    public function getBatchSize(): int
    {
        return static::DEFAULT_BATCH_SIZE;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function incrementProcessedTasksCounter(): void
    {
        $this->taskCounter++;
    }

    public function getProcessedTasksCounter(): int
    {
        return $this->taskCounter;
    }

    public function getMaxProcessedTasksCount(): int
    {
        return static::DEFAULT_MAX_PROCESSED_TASKS_COUNT;
    }

    /**
     * @throws HydratorNotFoundException
     */
    protected function decodeMessageBody(AMQPMessage $message)
    {
        $body = $message->getBody();
        $contentType = $message->get('content_type');

        $hydrator = $this->hydratorRegistry->getHydrator($contentType);

        return $hydrator->hydrate($body);
    }

    /**
     * {@inheritDoc}
     */
    abstract public function process(array $messageList);

    /**
     * {@inheritDoc}
     */
    abstract public function getBindQueueName(): string;

    /**
     * {@inheritDoc}
     */
    abstract public static function getName(): string;
}
