<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Command;

use Wakeapp\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->definitionList as $definition) {
            $definition->init($this->connection);
        }

        return self::SUCCESS;
    }
}
