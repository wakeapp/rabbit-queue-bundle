<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Command;

use PhpAmqpLib\Exception\AMQPTimeoutException;
use Wakeapp\Bundle\RabbitQueueBundle\Client\RabbitMqClient;
use Wakeapp\Bundle\RabbitQueueBundle\Consumer\ConsumerInterface;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\ConsumerNotFoundException;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\ConsumerSilentException;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\ReleasePartialException;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\RewindDelayPartialException;
use Wakeapp\Bundle\RabbitQueueBundle\Exception\RewindPartialException;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\ConsumerRegistry;
use Exception;
use JsonException;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\DefinitionRegistry;

use const JSON_THROW_ON_ERROR;

use function array_map;
use function count;
use function explode;
use function in_array;
use function ini_get;
use function pcntl_signal;
use function json_encode;

class ConsumerRunCommand extends Command
{
    private const IDLE_TIMEOUT = 0;
    private const TIMEOUT_WAIT = 3;

    protected static $defaultName = 'rabbit:consumer:run';

    private ConsumerRegistry $consumerRegistry;
    private RabbitMqClient $client;
    private DefinitionRegistry $definitionRegistry;
    private ?LoggerInterface $logger;

    public function dependencyInjection(
        ConsumerRegistry $consumerRegistry,
        RabbitMqClient $client,
        DefinitionRegistry $definitionRegistry,
        ?LoggerInterface $logger = null
    ): void {
        $this->consumerRegistry = $consumerRegistry;
        $this->client = $client;
        $this->definitionRegistry = $definitionRegistry;
        $this->logger = $logger ?? new NullLogger();
    }

    public function stopConsumer(int $signal, $signalInfo): void
    {
        try {
            $signalInfo = json_encode($signalInfo, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $signalInfo = null;
        }

        $this->logger->info('Consumer has been stopped forcibly with signal: {signal}. Context: {context}', [
            'signal' => $signal,
            'context' => $signalInfo,
        ]);

        exit();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Run consumer')
            ->setHelp('This command allows you to run any consumer by his name')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the consumer')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ConsumerNotFoundException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        declare(ticks=1);

        $disableFunctionList = explode(',', ini_get('disable_functions'));
        $disableFunctionList = array_map('trim', $disableFunctionList);

        if (in_array('pcntl_signal', $disableFunctionList, true)) {
            throw new RuntimeException('error occurred: function `pcntl_signal` disabled');
        }

        pcntl_signal(SIGTERM, [$this, 'stopConsumer']);
        pcntl_signal(SIGINT, [$this, 'stopConsumer']);
        pcntl_signal(SIGHUP, [$this, 'stopConsumer']);

        $name = $input->getArgument('name');
        $consumer = $this->consumerRegistry->getConsumer($name);
        $queueName = $consumer->getBindQueueName();
        $batchSize = $consumer->getBatchSize();

        $messageList = [];

        $this->client->qos($batchSize);
        $this->client->consume($queueName, $name, function (AMQPMessage $message) use (&$messageList) {
            $messageList[$message->getDeliveryTag()] = $message;
        });

        while ($this->client->isConsuming()) {
            if (count($messageList) === $batchSize) {
                $this->batchConsume($consumer, $messageList);
            }

            $timeout = empty($messageList) ? self::IDLE_TIMEOUT : self::TIMEOUT_WAIT;

            try {
                $this->client->wait($timeout);
            } catch (AMQPTimeoutException $e) {
                if (!empty($messageList)) {
                    $this->batchConsume($consumer, $messageList);
                }
            }
        }

        return self::SUCCESS;
    }

    public function batchConsume(ConsumerInterface $consumer, array &$messageList): void
    {
        try {
            $consumer->process($messageList);
            $this->client->ackList($messageList);

            $consumer->incrementProcessedTasksCounter();

            $maxProcessedTasksCount = $consumer->getMaxProcessedTasksCount();

            if ($maxProcessedTasksCount > 0 && $maxProcessedTasksCount <= $consumer->getProcessedTasksCounter()) {
                $consumer->stopPropagation();
            }
        } catch (RewindPartialException $exception) {
            $rewindMessageList = $exception->getRewindMessageList();
            $ackMessageList = $this->getDiffMessageList($messageList, $rewindMessageList);

            $this->client->rewindList($consumer->getBindQueueName(), $rewindMessageList);
            $this->client->ackList($ackMessageList);
        } catch (RewindDelayPartialException $exception) {
            $definition = $this->definitionRegistry->getDefinition($consumer->getBindQueueName());

            $rewindMessageList = $exception->getRewindMessageList();
            $ackMessageList = $this->getDiffMessageList($messageList, $rewindMessageList);

            $this->client->rewindList($definition::getQueueName(), $rewindMessageList, $exception->getDelay());
            $this->client->ackList($ackMessageList);
        } catch (ConsumerSilentException $exception) {
            $this->client->nackList($messageList);
        } catch (ReleasePartialException $exception) {
            $releaseMessageList = $exception->getReleaseMessageList();
            $ackMessageList = $this->getDiffMessageList($messageList, $releaseMessageList);

            $this->client->nackList($releaseMessageList);
            $this->client->ackList($ackMessageList);
        } catch (Exception $exception) {
            $this->client->nackList($messageList);

            $this->logger->warning('Error process queue: {errorMessage}', [
                'errorMessage' => $exception->getMessage(),
            ]);

            throw $exception;
        } finally {
            $messageList = [];

            if ($consumer->isPropagationStopped()) {
                $this->logger->info('Consumer has been propagation stopped forcibly');

                exit(0);
            }
        }
    }

    /**
     * @param AMQPMessage[] $messageList
     * @param AMQPMessage[] $secondMessageList
     *
     * @return AMQPMessage[]
     */
    private function getDiffMessageList(array $messageList, array $secondMessageList): array
    {
        return array_udiff($messageList, $secondMessageList, static function (AMQPMessage $a, AMQPMessage $b) {
            return $a->getDeliveryTag() <=> $b->getDeliveryTag();
        });
    }
}
