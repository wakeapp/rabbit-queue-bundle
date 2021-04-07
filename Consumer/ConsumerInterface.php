<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Consumer;

use PhpAmqpLib\Message\AMQPMessage;

interface ConsumerInterface
{
    public const TAG = 'wakeapp_rabbit_queue.consumer';

    public function getBatchSize(): int;

    public function isPropagationStopped(): bool;

    public function stopPropagation(): void;

    public function incrementProcessedTasksCounter(): void;

    public function getProcessedTasksCounter(): int;

    public function getMaxProcessedTasksCount(): int;

    /**
     * Handle messages
     *
     * @param AMQPMessage[] $messageList
     */
    public function process(array $messageList);

    /**
     * Get queue name which have bind with this consumer.
     */
    public function getBindQueueName(): string;

    /**
     * Get consumer name.
     * It will be needed when you will be running the consumer.
     */
    public static function getName(): string;
}
