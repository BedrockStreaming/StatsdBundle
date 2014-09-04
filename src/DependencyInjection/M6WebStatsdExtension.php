<?php

namespace M6Web\Bundle\StatsdBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class M6WebStatsdExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);
        $servers       = isset($config['servers']) ? $config['servers'] : array();
        $clients       = isset($config['clients']) ? $config['clients'] : array();

        $serviceId  = 'm6.data_collector.statsd';
        $definition = new Definition('M6Web\Bundle\StatsdBundle\DataCollector\StatsdDataCollector');

        $definition->setScope(ContainerInterface::SCOPE_CONTAINER);
        $definition->addTag('data_collector', array(
            'template' => 'M6WebStatsdBundle:Collector:statsd',
            'id'       => 'statsd'
        ));

        $definition->addTag('kernel.event_listener', array(
            'event'  => 'kernel.response',
            'method' => 'onKernelResponse'
        ));

        foreach ($clients as $alias => $clientConfig) {
            $serviceName = $this->loadClient($container, $alias, $clientConfig, $servers, $config['base_collectors']);
            $definition->addMethodCall('addStatsdClient', array($serviceName, new Reference($serviceName)));
        }
        $container->setDefinition($serviceId, $definition);

        if ($config['console_events']) {
            $container
                ->register(
                    'm6.listener.statsd.console',
                    'M6Web\Bundle\StatsdBundle\Listener\ConsoleListener'
                )
                ->addTag(
                    'kernel.event_listener',
                    ['event' => 'console.command', 'method' => 'onCommand']
                )
                ->addTag(
                    'kernel.event_listener',
                    ['event' => 'console.exception', 'method' => 'onException']
                )
                ->addTag(
                    'kernel.event_listener',
                    ['event' => 'console.terminate', 'method' => 'onTerminate']
                )
                ->addMethodCall('setEventDispatcher', [new Reference('event_dispatcher')]);
        }
    }

    /**
     * Load a client configuration as a service in the container. A client can use multiple servers
     *
     * @param ContainerInterface $container  The container
     * @param string             $alias      Alias of the client
     * @param array              $config     Base config of the client
     * @param array              $servers    List of available servers as describe in the config file
     * @param boolean            $baseEvents Register base events
     *
     * @return string the service name
     */
    protected function loadClient($container, $alias, array $config, array $servers, $baseEvents)
    {
        $usedServers = array();
        $events      = $config['events'];

        if ($config['servers'][0] == 'all') {
            // use all servers
            foreach ($servers as $server) {
                $usedServers[] = array(
                    'address' => $server['address'],
                    'port'    => $server['port']
                );
            }
        } else {
            // configure only declared servers
            foreach ($config['servers'] as $serverAlias) {
                if (!isset($servers[$serverAlias])) {
                    $message = 'M6WebStatsd client ' . $alias .
                        ' used server ' . $serverAlias .
                        ' which is not defined in the servers section';
                    throw new InvalidConfigurationException($message);
                } else {
                    $serverConfig = $servers[$serverAlias];
                    $usedServers[] = array(
                        'address' => $serverConfig['address'],
                        'port'    => $serverConfig['port']
                    );
                }
            }
        }
        // Add the statsd client configured
        $serviceId  = ($alias == 'default') ? 'm6_statsd' : 'm6_statsd.'.$alias;
        $definition = new Definition('M6Web\Bundle\StatsdBundle\Client\Client');
        $definition->setScope(ContainerInterface::SCOPE_CONTAINER);
        $definition->addArgument($usedServers);

        foreach ($events as $eventName => $eventConfig) {
            $definition->addTag('kernel.event_listener', array('event' => $eventName, 'method' => 'handleEvent'));
            $definition->addMethodCall('addEventToListen', array($eventName, $eventConfig));
        }

        $container->setDefinition($serviceId, $definition);

        // Add the statsd client listener
        $serviceListenerId = $serviceId.'.listener';
        $definition = new Definition('M6Web\Bundle\StatsdBundle\Statsd\Listener');
        $definition->addArgument(new Reference($serviceId));
        $definition->addArgument(new Reference('event_dispatcher'));
        $definition->addTag('kernel.event_listener', array(
            'event'    => 'kernel.terminate',
            'method'   => 'onKernelTerminate',
            'priority' => -100
        ));

        if ($baseEvents) {
            $definition->addTag('kernel.event_listener', array(
                'event' => 'kernel.terminate',
                'method' => 'onKernelTerminateEvents',
                'priority' => 0
            ));
            $definition->addTag('kernel.event_listener', array(
                'event' => 'kernel.exception',
                'method' => 'onKernelException',
                'priority' => 0
            ));
        }
        $container->setDefinition($serviceListenerId, $definition);

        return $serviceId;
    }

    /**
     * select an alias for the extension
     *
     * trick allowing bypassing the Bundle::getContainerExtension check on getAlias
     * not very clean, to investigate
     *
     * @return string
     */
    public function getAlias()
    {
        return 'm6_statsd';
    }
}
