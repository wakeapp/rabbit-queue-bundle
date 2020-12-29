<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\DependencyInjection;

use Wakeapp\Bundle\RabbitQueueBundle\Consumer\ConsumerInterface;
use Wakeapp\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use Wakeapp\Bundle\RabbitQueueBundle\Hydrator\HydratorInterface;
use Exception;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use function array_key_first;
use function count;

class WakeappRabbitQueueExtension extends Extension
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('wakeapp_rabbit_queue.hydrator_name', $config['hydrator_name']);
        $this->setConnectionParams($container, $config['connections']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $container
            ->registerForAutoconfiguration(ConsumerInterface::class)
            ->addTag(ConsumerInterface::TAG)
        ;

        $container
            ->registerForAutoconfiguration(DefinitionInterface::class)
            ->addTag(DefinitionInterface::TAG)
        ;

        $container
            ->registerForAutoconfiguration(HydratorInterface::class)
            ->addTag(HydratorInterface::TAG)
        ;
    }

    private function setConnectionParams(ContainerBuilder $container, array $connections): void
    {
        if (count($connections) > 1) {
            $message = 'wakeapp_rabbit_queue.connections parameter support only first connection.';

            $exception = new InvalidConfigurationException($message);
            $exception->setPath('wakeapp_rabbit_queue.connections');

            throw $exception;
        }

        $connection = $connections[array_key_first($connections)];

        $container->setParameter('wakeapp_rabbit_queue.connection.host', $connection['host']);
        $container->setParameter('wakeapp_rabbit_queue.connection.port', $connection['port']);
        $container->setParameter('wakeapp_rabbit_queue.connection.username', $connection['username']);
        $container->setParameter('wakeapp_rabbit_queue.connection.password', $connection['password']);
        $container->setParameter('wakeapp_rabbit_queue.connection.vhost', $connection['vhost']);
    }
}
