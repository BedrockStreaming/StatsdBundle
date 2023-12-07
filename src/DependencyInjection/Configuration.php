<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see
 * {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('m6_statsd');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $this->addServersSection($rootNode);
        $this->addClientsSection($rootNode);
        $this->addDefaultEventSection($rootNode);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addServersSection($rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('servers')
                    ->useAttributeAsKey('alias', false)
                    ->prototype('array')
                        ->children()
                            ->scalarNode('address')
                                ->isRequired()
                                ->validate()
                                    ->ifTrue(
                                        function ($v) {
                                            return substr($v, 0, 6) != 'udp://';
                                        }
                                    )
                                    ->thenInvalid("address parameter should begin with 'udp://'")
                                ->end()
                            ->end()
                            ->scalarNode('port')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addClientsSection($rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('clients')
                    ->useAttributeAsKey('alias', false)
                    ->prototype('array')
                        ->children()
                            ->arrayNode('servers')
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('events')
                                ->useAttributeAsKey('eventName')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('increment')->end()
                                        ->scalarNode('decrement')->end()
                                        ->scalarNode('count')->end()
                                        ->scalarNode('gauge')->end()
                                        ->scalarNode('set')->end()
                                        ->scalarNode('timing')->end()
                                        ->arrayNode('custom_timing')
                                            ->children()
                                                ->scalarNode('node')->end()
                                                ->scalarNode('method')->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('tags')
                                            ->prototype('scalar')->end()
                                        ->end()
                                        ->booleanNode('immediate_send')->defaultFalse()->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('message_formatter')->defaultValue('influxdbstatsd')->end()
                            ->integerNode('to_send_limit')->min(1)->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * addDefaultEventSection
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addDefaultEventSection($rootNode): void
    {
        $rootNode
            ->children()
                ->booleanNode('base_collectors')
                    ->defaultFalse()
                ->end()
                ->booleanNode('console_events')
                    ->defaultFalse()
                ->end();
    }
}
