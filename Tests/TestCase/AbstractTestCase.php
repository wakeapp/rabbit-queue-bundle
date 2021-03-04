<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Tests\TestCase;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\TestCase;
use Wakeapp\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use Wakeapp\Bundle\RabbitQueueBundle\Hydrator\JsonHydrator;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\HydratorRegistry;

class AbstractTestCase extends TestCase
{
    protected const TEST_MESSAGE = '{"test": "test"}';
    protected const TEST_EXCHANGE = 'test_exchange';
    protected const TEST_QUEUE_NAME = 'test_queue';

    public function createDefinitionMock(string $queueName, string $entryPointName, int $queueType): DefinitionInterface
    {
        return new class ($queueName, $entryPointName, $queueType) implements DefinitionInterface {
            private string $entryPointName;
            private int $queueType;
            private static string $queueName;

            public function __construct(string $queueName, string $entryPointName, int $queueType)
            {
                $this->entryPointName = $entryPointName;
                $this->queueType = $queueType;
                self::$queueName = $queueName;
            }

            public function init(AMQPStreamConnection $connection)
            {
            }

            public function getEntryPointName(): string
            {
                return $this->entryPointName;
            }

            public function getQueueType(): int
            {
                return $this->queueType;
            }

            public static function getQueueName(): string
            {
                return self::$queueName;
            }
        };
    }

    protected function createHydratorRegistryMock(): HydratorRegistry
    {
        $hydratorRegistry = $this->createMock(HydratorRegistry::class);
        $hydratorRegistry
            ->method('getHydrator')
            ->with(JsonHydrator::KEY)
            ->willReturn(new JsonHydrator())
        ;

        return $hydratorRegistry;
    }
}
