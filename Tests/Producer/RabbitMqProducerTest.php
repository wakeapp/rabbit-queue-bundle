<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Tests\Producer;

use PHPUnit\Framework\TestCase;
use Wakeapp\Bundle\RabbitQueueBundle\Definition\ExampleDefinition;
use Wakeapp\Bundle\RabbitQueueBundle\Definition\ExampleFifoDefinition;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Hydrator\JsonHydrator;
use Wakeapp\Bundle\RabbitQueueBundle\Producer\RabbitMqProducer;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\DefinitionRegistry;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\HydratorRegistry;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\PublisherRegistry;

class RabbitMqProducerTest extends TestCase
{
    private const TEST_MESSAGE = ['message' => 'test'];

    private RabbitMqProducer $producer;

    protected function setUp(): void
    {
        $publisherRegistry = $this->createMock(PublisherRegistry::class);

        $definitionRegistry = $this->createMock(DefinitionRegistry::class);
        $definitionRegistry
            ->method('getDefinition')
            ->willReturnMap([
                [QueueEnum::EXAMPLE_DEDUPLICATE_DELAY, new ExampleDefinition()],
                [QueueEnum::EXAMPLE_FIFO, new ExampleFifoDefinition()],
            ])
        ;

        $hydratorRegistry = $this->createMock(HydratorRegistry::class);
        $hydratorRegistry
            ->method('getHydrator')
            ->with(JsonHydrator::KEY)
            ->willReturn(new JsonHydrator())
        ;

        $this->producer = new RabbitMqProducer($definitionRegistry, $hydratorRegistry, $publisherRegistry, JsonHydrator::KEY);
    }

    public function testPut(): void
    {
        $this->producer->put(QueueEnum::EXAMPLE_FIFO, self::TEST_MESSAGE);

        self::assertTrue(true);
    }
}
