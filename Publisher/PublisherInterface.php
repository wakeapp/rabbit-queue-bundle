<?php

namespace Wakeapp\Bundle\RabbitQueueBundle\Publisher;

use Wakeapp\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;

interface PublisherInterface
{
    public const TAG = 'wakeapp_rabbit_queue.publisher';

    public function publish(DefinitionInterface $definition, string $dataString, array $options = []): void;

    public static function getQueueType(): string;
}
