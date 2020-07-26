<?php

namespace M6Web\Bundle\StatsdBundle\DependencyInjection;

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
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('m6_statsd');
        $rootNode = $treeBuilder->getRootNode();

        $this->addServersSection($rootNode);
        $this->addClientsSection($rootNode);
        $this->addDefaultEventSection($rootNode);

        return $treeBuilder;
    }

    private function addServersSection($rootNode)
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

    private function addClientsSection($rootNode)
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
                            ->integerNode('to_send_limit')->min(1)->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * addDefaultEventSection
     *
     * @param mixed $rootNode
     */
    private function addDefaultEventSection($rootNode)
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
