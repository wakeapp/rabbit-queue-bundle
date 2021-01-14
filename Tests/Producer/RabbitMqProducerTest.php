<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Tests\Producer;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\TestCase;
use Wakeapp\Bundle\RabbitQueueBundle\Definition\ExampleDefinition;
use Wakeapp\Bundle\RabbitQueueBundle\Definition\ExampleFifoDefinition;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\RabbitQueueException;
use Wakeapp\Bundle\RabbitQueueBundle\Hydrator\JsonHydrator;
use Wakeapp\Bundle\RabbitQueueBundle\Producer\RabbitMqProducer;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\DefinitionRegistry;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\HydratorRegistry;

class RabbitMqProducerTest extends TestCase
{
    private const TEST_MESSAGE = ['message' => 'test'];

    private RabbitMqProducer $producer;

    protected function setUp(): void
    {
        $connection = $this->createMock(AMQPStreamConnection::class);
        $connection
            ->method('channel')
            ->willReturn($this->createMock(AMQPChannel::class))
        ;

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

        $this->producer = new RabbitMqProducer($connection, $definitionRegistry, $hydratorRegistry, JsonHydrator::KEY);
    }

    public function testPutToDeduplicateDelayQueue(): void
    {
        $options = [
            'key' => 'unique_key',
            'delay' => 10000,
        ];

        $this->producer->put(QueueEnum::EXAMPLE_DEDUPLICATE_DELAY, self::TEST_MESSAGE, $options);

        self::assertTrue(true);
    }

    /**
     * @dataProvider invalidOptionsProvider
     */
    public function testPutToDeduplicateDelayQueueWithoutOptions(array $options): void
    {
        $this->expectException(RabbitQueueException::class);

        $this->producer->put(QueueEnum::EXAMPLE_DEDUPLICATE_DELAY, self::TEST_MESSAGE, $options);
    }

    public function testPutToFifoTest(): void
    {
        $this->producer->put(QueueEnum::EXAMPLE_FIFO, self::TEST_MESSAGE);

        self::assertTrue(true);
    }

    public function invalidOptionsProvider(): array
    {
        return [
            'empty options'  => [[]],
            'only key option' => [['key' => 'unique_key']],
            'only delay option' => [['delay' => 1]],
            'invalid string delay option' => [['delay' => 'test', 'key' => 'unique_key']],
        ];
    }
}
