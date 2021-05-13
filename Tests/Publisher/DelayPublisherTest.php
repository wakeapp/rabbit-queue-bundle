<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Tests\Publisher;

use PhpAmqpLib\Message\AMQPMessage;
use Wakeapp\Bundle\RabbitQueueBundle\Client\RabbitMqClient;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\RabbitQueueException;
use Wakeapp\Bundle\RabbitQueueBundle\Hydrator\JsonHydrator;
use Wakeapp\Bundle\RabbitQueueBundle\Publisher\DelayPublisher;
use Wakeapp\Bundle\RabbitQueueBundle\Tests\TestCase\AbstractTestCase;

class DelayPublisherTest extends AbstractTestCase
{
    public const TEST_OPTIONS = ['delay' => 10];
    public const QUEUE_TYPE = QueueTypeEnum::FIFO | QueueTypeEnum::DELAY;

    public function testPublish(): void
    {
        $definition = $this->createDefinitionMock(self::TEST_QUEUE_NAME, self::TEST_EXCHANGE, self::QUEUE_TYPE);
        $hydratorRegistry = $this->createHydratorRegistryMock();

        $client = $this->createMock(RabbitMqClient::class);
        $client->expects(self::once())
            ->method('publish')
            ->with(self::isInstanceOf(AMQPMessage::class), self::TEST_EXCHANGE, '')
        ;

        $publisher = new DelayPublisher($client, $hydratorRegistry, JsonHydrator::KEY);

        $publisher->publish($definition, self::TEST_MESSAGE, self::TEST_OPTIONS);

        self::assertTrue(true);
    }

    public function testPublishWithRouting(): void
    {
        $definition = $this->createDefinitionMock(self::TEST_QUEUE_NAME, self::TEST_EXCHANGE, self::QUEUE_TYPE);
        $hydratorRegistry = $this->createHydratorRegistryMock();

        $client = $this->createMock(RabbitMqClient::class);
        $client->expects(self::once())
            ->method('publish')
            ->with(self::isInstanceOf(AMQPMessage::class), self::TEST_EXCHANGE, self::TEST_ROUTING)
        ;

        $publisher = new DelayPublisher($client, $hydratorRegistry, JsonHydrator::KEY);

        $publisher->publish($definition, self::TEST_MESSAGE, self::TEST_OPTIONS, self::TEST_ROUTING);

        self::assertTrue(true);
    }

    /**
     * @dataProvider invalidOptionsProvider
     */
    public function testPublishInvalidOptions(array $options): void
    {
        $this->expectException(RabbitQueueException::class);

        $definition = $this->createDefinitionMock(self::TEST_QUEUE_NAME, self::TEST_EXCHANGE, self::QUEUE_TYPE);
        $hydratorRegistry = $this->createHydratorRegistryMock();
        $client = $this->createMock(RabbitMqClient::class);

        $publisher = new DelayPublisher($client, $hydratorRegistry, JsonHydrator::KEY);

        $publisher->publish($definition, self::TEST_MESSAGE, $options);
    }

    public function invalidOptionsProvider(): array
    {
        return [
            'empty options'  => [[]],
            'invalid delay option' => [['delay' => '1']],
        ];
    }
}
