<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Command;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Wire\AMQPTable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wakeapp\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\ExchangeEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\RouteStructureException;

class UpdateDefinitionCommand extends Command
{
    protected static $defaultName = 'rabbit:definition:update';

    private AMQPStreamConnection $connection;

    /**
     * @var DefinitionInterface[]
     */
    private iterable $definitionList;

    /**
     * @required
     */
    public function dependencyInjection(
        AMQPStreamConnection $connection
    ): void {
        $this->connection = $connection;
    }

    /**
     * @param DefinitionInterface[]|iterable $definitionList
     */
    public function setDefinitionList(iterable $definitionList): void
    {
        $this->definitionList = $definitionList;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Run migration')
            ->setHelp('This command allows you to update schema of queues')
        ;
    }

    /**
     * {@inheritDoc}
     *
     * @throws RouteStructureException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $routersToInit = [];
        $initializedRouters = [];

        foreach ($this->definitionList as $definition) {
            if ($definition->getQueueType() & QueueTypeEnum::ROUTER > 0) {
                if (method_exists($definition, 'dependsOn') && !empty($definition->dependsOn())) {
                    $routersToInit[$definition::getQueueName()] = $definition;
                } else {
                    $definition->init($this->connection);
                    $initializedRouters[] = $definition::getQueueName();
                }
            }
        }

        $successLoop = true;
        while ($successLoop && !empty($routersToInit)) {
            $successLoop = false;

            foreach ($routersToInit as $router) {
                if (empty(array_diff($router->dependsOn(), $initializedRouters))) {
                    $successLoop = true;
                    $router->init($this->connection);
                    $initializedRouters[] = $router::getQueueName();
                }
            }
        }

        if (!$successLoop) {
            throw new RouteStructureException('Router definitions have cyclic dependencies');
        }


        foreach ($this->definitionList as $definition) {
            if ($definition->getQueueType() & QueueTypeEnum::ROUTER === 0) {
                continue;
            }

            $definition->init($this->connection);

            $this->bindRetryExchange($definition);
        }

        return 0;
    }

    private function bindRetryExchange(DefinitionInterface $definition): void
    {
        $queueName = $definition::getQueueName();
        $channel = $this->connection->channel();
        $retryExchange = $queueName . ExchangeEnum::RETRY_EXCHANGE;

        $channel->exchange_declare(
            $retryExchange,
            'x-delayed-message',
            false,
            true,
            false,
            false,
            false,
            new AMQPTable(['x-delayed-type' => AMQPExchangeType::DIRECT])
        );

        $channel->queue_bind($queueName, $retryExchange, $queueName);
    }
}
