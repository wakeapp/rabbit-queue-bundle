<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\DefinitionNotFoundException;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\ConsumerRegistry;

use function count;
use function ksort;
use function sprintf;

class ConsumerListCommand extends Command
{
    protected static $defaultName = 'rabbit:consumer:list';

    private ConsumerRegistry $consumerRegistry;

    public function dependencyInjection(ConsumerRegistry $consumerRegistry): void
    {
        $this->consumerRegistry = $consumerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Shows all registered consumers')
            ->setHelp('This command allows you to view list of the all consumers in the system')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws DefinitionNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $consumerList = $this->consumerRegistry->getConsumerList();

        ksort($consumerList);

        $consumerCount = count($consumerList);

        if ($consumerCount === 0) {
            $output->writeln('<comment>You have not yet any registered consumer</comment>');

            return self::SUCCESS;
        }

        $consoleStyle = new SymfonyStyle($input, $output);

        $table = new Table($output);
        $table->setHeaders(['Queue Name', 'Batch Size']);

        foreach ($consumerList as $consumer) {
            $batchSize = $consumer->getBatchSize();
            $table->addRow([$consumer->getBindQueueName(), $batchSize]);
        }

        $consoleStyle->text(sprintf('Total consumers count: <comment>%s</comment>', $consumerCount));

        $table->render();

        return self::SUCCESS;
    }
}
