<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Wakeapp\Bundle\RabbitQueueBundle\Client\RabbitMqClient;
use Wakeapp\Bundle\RabbitQueueBundle\Command\ConsumerRunCommand;
use Wakeapp\Bundle\RabbitQueueBundle\Registry\ConsumerRegistry;

class ConsumerRunCommandTest extends TestCase
{
    private Application $application;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->application = new Application();

        $command = new ConsumerRunCommand();
        $command->dependencyInjection(
            $this->createMock(ConsumerRegistry::class),
            $this->createMock(RabbitMqClient::class)
        );

        $this->application->add($command);
    }

    public function testExecute(): void
    {
        $command = $this->application->find('rabbit:consume:run');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['name' => 'example']);

        $statusCode = $commandTester->getStatusCode();

        self::assertSame(0, $statusCode, $commandTester->getDisplay());
    }

    public function testExecuteFailWithoutNameParameter(): void
    {
        $this->expectException(RuntimeException::class);

        $command = $this->application->find('rabbit:consume:run');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
    }
}
