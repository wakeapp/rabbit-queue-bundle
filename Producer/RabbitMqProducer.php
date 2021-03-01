<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Producer;

use Wakeapp\Bundle\RabbitQueueBundle\Exception\DefinitionNotFoundException;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\HydratorNotFoundException;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\RabbitQueueException;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\DefinitionRegistry;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\HydratorRegistry;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\PublisherRegistry;

class RabbitMqProducer implements RabbitMqProducerInterface
{
    private DefinitionRegistry $definitionRegistry;
    private HydratorRegistry $hydratorRegistry;
    private PublisherRegistry $publisherRegistry;
    private string $hydratorName;

    public function __construct(
        DefinitionRegistry $definitionRegistry,
        HydratorRegistry $hydratorRegistry,
        PublisherRegistry $publisherRegistry,
        string $hydratorName
    ) {
        $this->definitionRegistry = $definitionRegistry;
        $this->hydratorRegistry = $hydratorRegistry;
        $this->hydratorName = $hydratorName;
        $this->publisherRegistry = $publisherRegistry;
    }

    /**
     * @throws RabbitQueueException
     * @throws DefinitionNotFoundException
     * @throws HydratorNotFoundException
     */
    public function put(string $queueName, $data, array $options = []): void
    {
        $dataString = $this->hydratorRegistry->getHydrator($this->hydratorName)->dehydrate($data);

        $definition = $this->definitionRegistry->getDefinition($queueName);
        $queueType = $definition->getQueueType();

        $publisher = $this->publisherRegistry->getPublisher($queueType);

        $publisher->publish($definition, $dataString, $options);
    }
}
