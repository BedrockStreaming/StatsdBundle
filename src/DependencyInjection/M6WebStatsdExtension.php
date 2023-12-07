<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class M6WebStatsdExtension extends Extension
{
    /**
     * @return void
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $servers = isset($config['servers']) ? $config['servers'] : [];
        $clients = isset($config['clients']) ? $config['clients'] : [];

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $clientServiceNames = [];
        foreach ($clients as $alias => $clientConfig) {
            // load client in the container
            $clientServiceNames[] = $this->loadClient(
                $container,
                $alias,
                $clientConfig,
                $servers,
                $config['base_collectors']
            );
        }
        if ($container->getParameter('kernel.debug')) {
            $definition = new Definition('M6Web\Bundle\StatsdBundle\DataCollector\StatsdDataCollector');
            $definition->setPublic(true);
            $definition->addTag(
                'data_collector',
                [
                    'template' => '@M6WebStatsd/Collector/statsd.html.twig',
                    'id' => 'statsd',
                ]
            );

            $definition->addTag(
                'kernel.event_listener',
                [
                    'event' => 'kernel.response',
                    'method' => 'onKernelResponse',
                ]
            );

            foreach ($clientServiceNames as $serviceName) {
                $definition->addMethodCall('addStatsdClient', [$serviceName, new Reference($serviceName)]);
            }

            $container->setDefinition('m6.data_collector.statsd', $definition);
        }

        // Listner of console events
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
     * @param ContainerBuilder $container  The container
     * @param string           $alias      Alias of the client
     * @param array            $config     Base config of the client
     * @param array            $servers    List of available servers as describe in the config file
     * @param bool             $baseEvents Register base events
     *
     * @return string the service name
     *
     * @throws InvalidConfigurationException
     */
    protected function loadClient($container, $alias, array $config, array $servers, $baseEvents)
    {
        $usedServers = [];
        $events = $config['events'];
        $matchedServers = [];

        if ($config['servers'][0] == 'all') {
            // Use all servers
            $matchedServers = array_keys($servers);
        } else {
            // Use only declared servers
            foreach ($config['servers'] as $serverAlias) {
                // Named server
                if (array_key_exists($serverAlias, $servers)) {
                    $matchedServers[] = $serverAlias;
                    continue;
                }

                // Search matchning server config name
                $found = false;
                foreach (array_keys($servers) as $key) {
                    if (fnmatch($serverAlias, $key)) {
                        $matchedServers[] = $key;
                        $found = true;
                    }
                }

                // No server found
                if (!$found) {
                    throw new InvalidConfigurationException(sprintf(
                        'M6WebStatsd client %s used server %s which is not defined in the servers section',
                        $alias,
                        $serverAlias
                    ));
                }
            }
        }

        // Matched server congurations
        foreach ($matchedServers as $serverAlias) {
            $usedServers[] = [
                'address' => $servers[$serverAlias]['address'],
                'port' => $servers[$serverAlias]['port'],
            ];
        }

        // Add the statsd client configured
        $serviceId = ($alias == 'default') ? 'm6_statsd' : 'm6_statsd.'.$alias;
        $definition = new Definition('M6Web\Bundle\StatsdBundle\Client\Client');
        $definition->setPublic(true);
        $definition->addArgument($usedServers);
        $definition->addArgument(new Reference(
            $container->has('statsdbundle.formatter.'.$config['message_formatter']) ?
                'statsdbundle.formatter.'.$config['message_formatter'] :
                $config['message_formatter']
        ));

        if (isset($config['to_send_limit'])) {
            $definition->addMethodCall('setToSendLimit', [$config['to_send_limit']]);
        }

        foreach ($events as $eventName => $eventConfig) {
            $definition->addTag('kernel.event_listener', ['event' => $eventName, 'method' => 'handleEvent']);
            $definition->addMethodCall('addEventToListen', [$eventName, $eventConfig]);
        }

        $definition->addMethodCall('setPropertyAccessor', [new Reference('property_accessor_statsdbundle')]);

        $container->setDefinition($serviceId, $definition);

        // Add the statsd client listener
        $serviceListenerId = $serviceId.'.listener';
        $definition = new Definition('M6Web\Bundle\StatsdBundle\Statsd\Listener');
        $definition->setPublic(true);
        $definition->addArgument(new Reference($serviceId));
        $definition->addArgument(new Reference('event_dispatcher'));
        $definition->addTag('kernel.event_listener', [
            'event' => 'kernel.terminate',
            'method' => 'onKernelTerminate',
            'priority' => -100,
        ]);
        $definition->addTag('kernel.event_listener', [
            'event' => 'console.terminate',
            'method' => 'onConsoleTerminate',
            'priority' => -100,
        ]);

        if ($baseEvents) {
            $definition->addTag('kernel.event_listener', [
                'event' => 'kernel.terminate',
                'method' => 'dispatchBaseEvents',
                'priority' => 0,
            ]);
            $definition->addTag('kernel.event_listener', [
                'event' => 'kernel.exception',
                'method' => 'onKernelException',
                'priority' => 0,
            ]);
        }
        $container->setDefinition($serviceListenerId, $definition);

        return $serviceId;
    }

    /**
     * select an alias for the extension
     *
     * trick allowing bypassing the Bundle::getContainerExtension check on getAlias
     * not very clean, to investigate
     */
    public function getAlias(): string
    {
        return 'm6_statsd';
    }
}
