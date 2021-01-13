<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Producer;

use Wakeapp\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueOptionEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\DefinitionNotFoundException;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\HydratorNotFoundException;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\RabbitQueueException;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\DefinitionRegistry;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\HydratorRegistry;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

use function is_int;
use function is_string;
use function sprintf;

class RabbitMqProducer implements RabbitMqProducerInterface
{
    private AMQPStreamConnection $connection;
    private DefinitionRegistry $definitionRegistry;
    private HydratorRegistry $hydratorRegistry;
    private string $hydratorName;

    public function __construct(
        AMQPStreamConnection $connection,
        DefinitionRegistry $definitionRegistry,
        HydratorRegistry $hydratorRegistry,
        string $hydratorName
    ) {
        $this->connection = $connection;
        $this->definitionRegistry = $definitionRegistry;
        $this->hydratorRegistry = $hydratorRegistry;
        $this->hydratorName = $hydratorName;
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

        $isDeduplicateType = QueueTypeEnum::DEDUPLICATE === (QueueTypeEnum::DEDUPLICATE & $queueType);
        $isDelayType = QueueTypeEnum::DELAY === (QueueTypeEnum::DELAY & $queueType);
        $isFifoType = QueueTypeEnum::FIFO === (QueueTypeEnum::FIFO & $queueType);

        if ($isFifoType && $isDeduplicateType && $isDelayType) {
            $this->putToDeduplicateDelay($definition, $dataString, $options);

            return;
        }

        if ($isFifoType) {
            $this->putToFifo($definition, $dataString);

            return;
        }

        throw new RabbitQueueException('Not support queue type.');
    }

    /**
     * @throws HydratorNotFoundException
     */
    protected function putToFifo(DefinitionInterface $definition, string $dataString): void
    {
        $queueName = $definition->getEntryPointName();

        $msg = new AMQPMessage($dataString, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'content_type' => $this->hydratorRegistry->getHydrator($this->hydratorName)::getKey(),
        ]);

        $channel = $this->connection->channel();

        $channel->basic_publish($msg, '', $queueName);
    }

    /**
     * @throws RabbitQueueException
     * @throws HydratorNotFoundException
     */
    protected function putToDeduplicateDelay(DefinitionInterface $definition, string $dataString, array $options): void
    {
        if (empty($options[QueueOptionEnum::KEY]) || !is_string($options[QueueOptionEnum::KEY])) {
            $message = sprintf(
                'Element for queue "%s" must have option "%s" with type string. See %s',
                $definition::getQueueName(),
                QueueOptionEnum::KEY,
                QueueOptionEnum::class
            );

            throw new RabbitQueueException($message);
        }

        if (empty($options[QueueOptionEnum::DELAY]) || !is_int($options[QueueOptionEnum::DELAY])) {
            $message = sprintf(
                'Element for queue "%s" must have option "%s" with type int. See %s',
                $definition::getQueueName(),
                QueueOptionEnum::DELAY,
                QueueOptionEnum::class
            );

            throw new RabbitQueueException($message);
        }

        $exchangeName = $definition->getEntryPointName();

        $amqpTableOption['x-deduplication-header'] = $options[QueueOptionEnum::KEY];
        $amqpTableOption['x-delay'] = $options[QueueOptionEnum::DELAY] * 1000;
        $amqpTableOption['x-cache-ttl'] = $options[QueueOptionEnum::DELAY] * 1000;

        $message = new AMQPMessage($dataString, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'content_type' => $this->hydratorRegistry->getHydrator($this->hydratorName)::getKey(),
        ]);
        $message->set('application_headers', new AMQPTable($amqpTableOption));

        $channel = $this->connection->channel();

        $channel->basic_publish($message, $exchangeName);
    }
}
