<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Tests\Publisher;

use PHPUnit\Framework\TestCase;
use Wakeapp\Bundle\RabbitQueueBundle\Client\RabbitMqClient;
use Wakeapp\Bundle\RabbitQueueBundle\Definition\ExampleDefinition;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\RabbitQueueException;
use Wakeapp\Bundle\RabbitQueueBundle\Hydrator\JsonHydrator;
use Wakeapp\Bundle\RabbitQueueBundle\Publisher\DeduplicateDelayPublisher;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\HydratorRegistry;

class DeduplicateDelayPublisherTest extends TestCase
{
    public const TEST_MESSAGE = '{"test": "test"}';
    public const TEST_OPTIONS = ['delay' => 10, 'key' => 'unique_key'];

    private DeduplicateDelayPublisher $publisher;

    protected function setUp(): void
    {
        $client = $this->createMock(RabbitMqClient::class);
        $hydratorRegistry = $this->createMock(HydratorRegistry::class);
        $hydratorRegistry
            ->method('getHydrator')
            ->with(JsonHydrator::KEY)
            ->willReturn(new JsonHydrator())
        ;

        $this->publisher = new DeduplicateDelayPublisher($client, $hydratorRegistry, JsonHydrator::KEY);

        parent::setUp();
    }

    public function testPublish(): void
    {
        $definition = new ExampleDefinition();
        $this->publisher->publish($definition, self::TEST_MESSAGE, self::TEST_OPTIONS);

        self::assertTrue(true);
    }

    /**
     * @dataProvider invalidOptionsProvider
     */
    public function testPublishInvalidOptions(array $options): void
    {
        $this->expectException(RabbitQueueException::class);
        $definition = new ExampleDefinition();

        $this->publisher->publish($definition, self::TEST_MESSAGE, $options);
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
