<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Publisher;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Wakeapp\Bundle\RabbitQueueBundle\Client\RabbitMqClient;
use Wakeapp\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\HydratorRegistry;

abstract class AbstractPublisher implements PublisherInterface
{
    public const QUEUE_TYPE = QueueTypeEnum::FIFO;
    private const DEFAULT_NAME = '';

    protected HydratorRegistry $hydratorRegistry;
    protected string $hydratorName;
    protected RabbitMqClient $client;

    public function __construct(RabbitMqClient $client, HydratorRegistry $hydratorRegistry, string $hydratorName)
    {
        $this->hydratorRegistry = $hydratorRegistry;
        $this->hydratorName = $hydratorName;
        $this->client = $client;
    }

    abstract protected function prepareOptions(DefinitionInterface $definition, array $options): array;

    public function publish(DefinitionInterface $definition, string $dataString, array $options = []): void
    {
        $exchangeName = $this->getDefinitionExchangeName($definition);
        $queueName = $this->getDefinitionQueueName($definition);

        $message = new AMQPMessage($dataString, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'content_type' => $this->hydratorRegistry->getHydrator($this->hydratorName)::getKey(),
        ]);

        $amqpTableOptions = $this->prepareOptions($definition, $options);

        if (!empty($amqpTableOptions)) {
            $message->set('application_headers', new AMQPTable($amqpTableOptions));
        }

        $this->client->publish($message, $exchangeName, $queueName);
    }

    abstract public static function getQueueType(): string;

    protected function getDefinitionExchangeName(DefinitionInterface $definition): string
    {
        if ($definition->getQueueType() === (QueueTypeEnum::FIFO | QueueTypeEnum::DEDUPLICATE)) {
            return self::DEFAULT_NAME;
        }

        return $definition->getQueueType() === QueueTypeEnum::FIFO
            ? self::DEFAULT_NAME
            : $definition->getEntryPointName()
        ;
    }

    protected function getDefinitionQueueName(DefinitionInterface $definition): string
    {
        if ($definition->getQueueType() === (QueueTypeEnum::FIFO | QueueTypeEnum::DEDUPLICATE)) {
            return $definition::getQueueName();
        }

        return $definition->getQueueType() === QueueTypeEnum::FIFO
            ? $definition::getQueueName()
            : self::DEFAULT_NAME
        ;
    }
}
