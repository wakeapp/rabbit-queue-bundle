<?php

declare(strict_types=1);

namespace Wakeapp\Bundle\RabbitQueueBundle\DependencyInjection;

use Wakeapp\Bundle\RabbitQueueBundle\Hydrator\JsonHydrator;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wakeapp_rabbit_queue');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('hydrator_name')->defaultValue(JsonHydrator::KEY)->end()
                ->arrayNode('connections')
                ->isRequired()
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                            ->integerNode('port')->defaultValue(5672)->end()
                            ->scalarNode('username')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('vhost')->defaultValue('/')->end()
                            ->scalarNode('connection_timeout')->defaultValue(3)->end()
                            ->scalarNode('read_write_timeout')->defaultValue(3)->end()
                            ->scalarNode('heartbeat')->defaultValue(0)->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('consumer')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('idle_timeout')
                            ->defaultValue(0)
                        ->end()
                        ->integerNode('wait_timeout')
                            ->defaultValue(3)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
