<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Tests\Publisher;

use PhpAmqpLib\Message\AMQPMessage;
use Wakeapp\Bundle\RabbitQueueBundle\Client\RabbitMqClient;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Hydrator\JsonHydrator;
use Wakeapp\Bundle\RabbitQueueBundle\Publisher\FifoPublisher;
use Wakeapp\Bundle\RabbitQueueBundle\Tests\TestCase\AbstractTestCase;

class FifoPublisherTest extends AbstractTestCase
{
    public const QUEUE_TYPE = QueueTypeEnum::FIFO;

    public function testPublish(): void
    {
        $definition = $this->createDefinitionMock(self::TEST_QUEUE_NAME, self::TEST_EXCHANGE, self::QUEUE_TYPE);
        $hydratorRegistry = $this->createHydratorRegistryMock();

        $client = $this->createMock(RabbitMqClient::class);
        $client->expects(self::once())
            ->method('publish')
            ->with(self::isInstanceOf(AMQPMessage::class), '', self::TEST_QUEUE_NAME)
        ;

        $publisher = new FifoPublisher($client, $hydratorRegistry, JsonHydrator::KEY);

        $publisher->publish($definition, self::TEST_MESSAGE);

        self::assertTrue(true);
    }

    public function testPublishWithRouting(): void
    {
        $definition = $this->createDefinitionMock(self::TEST_QUEUE_NAME, self::TEST_EXCHANGE, self::QUEUE_TYPE);
        $hydratorRegistry = $this->createHydratorRegistryMock();

        $client = $this->createMock(RabbitMqClient::class);
        $client->expects(self::once())
            ->method('publish')
            ->with(self::isInstanceOf(AMQPMessage::class), '', self::TEST_ROUTING)
        ;

        $publisher = new FifoPublisher($client, $hydratorRegistry, JsonHydrator::KEY);

        $publisher->publish($definition, self::TEST_MESSAGE, [], self::TEST_ROUTING);

        self::assertTrue(true);
    }
}
