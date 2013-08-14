<?php

namespace M6Web\Bundle\StatsdBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('m6_statsd');

        $this->addServersSection($rootNode);
        $this->addClientsSection($rootNode);

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
                                    ->ifTrue(function($v) {
                                        return substr($v, 0, 6) != 'udp://';
                                    })
                                    ->thenInvalid("Le paramètres address des serveurs statsd doit commencer par 'udp://'")
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
                                        ->scalarNode('timing')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
