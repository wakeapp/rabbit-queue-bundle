<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Tests\Publisher;

use PHPUnit\Framework\TestCase;
use Wakeapp\Bundle\RabbitQueueBundle\Client\RabbitMqClient;
use Wakeapp\Bundle\RabbitQueueBundle\Definition\ExampleFifoDefinition;
use Wakeapp\Bundle\RabbitQueueBundle\Hydrator\JsonHydrator;
use Wakeapp\Bundle\RabbitQueueBundle\Publisher\FifoPublisher;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\HydratorRegistry;

class FifoPublisherTest extends TestCase
{
    public const TEST_MESSAGE = '{"test": "test"}';

    private FifoPublisher $publisher;

    protected function setUp(): void
    {
        $client = $this->createMock(RabbitMqClient::class);
        $hydratorRegistry = $this->createMock(HydratorRegistry::class);
        $hydratorRegistry
            ->method('getHydrator')
            ->with(JsonHydrator::KEY)
            ->willReturn(new JsonHydrator())
        ;

        $this->publisher = new FifoPublisher($client, $hydratorRegistry, JsonHydrator::KEY);

        parent::setUp();
    }

    public function testPublish(): void
    {
        $definition = new ExampleFifoDefinition();
        $this->publisher->publish($definition, self::TEST_MESSAGE);

        self::assertTrue(true);
    }
}
