<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdBundle\Tests\Units\DependencyInjection;

use M6Web\Bundle\StatsdBundle\DependencyInjection\M6WebStatsdExtension as BaseM6WebStatsdExtension;
use M6Web\Component\Statsd\MessageFormatter\DogStatsDMessageFormatter;
use M6Web\Component\Statsd\MessageFormatter\InfluxDBStatsDMessageFormatter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;

class M6WebStatsdExtension extends \atoum
{
    /** @var ContainerBuilder */
    protected $container;

    protected function initContainer($resource, $debug = false)
    {
        $this->container = new ContainerBuilder();
        $this->container->register('event_dispatcher', EventDispatcher::class);
        $this->container->registerExtension(new BaseM6WebStatsdExtension());
        $this->loadConfiguration($this->container, $resource);
        $this->container->setParameter('kernel.debug', $debug);

        $this->container->setDefinition(
            'my.custom.message_formatter',
            new Definition('\mock\M6Web\Component\Statsd\MessageFormatter\MessageFormatterInterface')
        );

        $this->container->compile();
    }

    protected function loadConfiguration(ContainerBuilder $container, $resource)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../Fixtures/'));
        $loader->load($resource.'.yml');
    }

    public function testBasicConfiguration()
    {
        $this->initContainer('basic_config', true);

        $this->assert
            ->boolean($this->container->has('m6_statsd'))
                ->isIdenticalTo(true)
            ->and()
                ->object($serviceStatsd = $this->container->get('m6_statsd'))
                    ->isInstanceOf('M6Web\Bundle\StatsdBundle\Client\Client');
        // @TODO : check the client more

        // check datacollector
        $this->assert
            ->object($dataCollector = $this->container->get('m6.data_collector.statsd'))
                ->isInstanceOf('M6Web\Bundle\StatsdBundle\DataCollector\StatsdDataCollector');
    }

    public function testBasicConfigurationWithoutKernelDebug()
    {
        $this->initContainer('basic_config');

        $this->assert
            ->boolean($this->container->has('m6.data_collector.statsd'))
                ->isIdenticalTo(false);
    }

    /**
     * @dataProvider messageFormatterConfigDataProvider
     */
    public function testMessageFormatterConfig($service, $expectedFormatter)
    {
        $this->initContainer('message_formatter');

        $this
            ->object($definition = $this
                ->container
                ->getDefinition(sprintf('m6_statsd.%s', $service))
            )
            ->array($arguments = $definition->getArguments())
            ->object($formatterDefinition = $arguments[1])
        ;

        if ($formatterDefinition instanceof Reference) {
            // if a service is referenced a single time it will be inlined as an argument (a Definition object).
            // if a service is referenced multiple times it will not be inlined (a Reference object).
            // normalise to a definition to make assertions easier.
            $formatterDefinition = $this->container->getDefinition((string) $formatterDefinition);
        }

        $this
            ->string($formatterDefinition->getClass())
            ->isEqualTo($expectedFormatter);
    }

    public function messageFormatterConfigDataProvider()
    {
        return [
            ['unspecified', InfluxDBStatsDMessageFormatter::class],
            ['dogstatsd', DogStatsDMessageFormatter::class],
            ['influxdbstatsd', InfluxDBStatsDMessageFormatter::class],
            ['custom_service', '\mock\M6Web\Component\Statsd\MessageFormatter\MessageFormatterInterface'],
        ];
    }

    /**
     * @dataProvider shellPatternConfigDataProvider
     */
    public function testShellPatternConfig($service, $expectedServers)
    {
        $this->initContainer('shell_pattern');

        $this
            ->object($definition = $this
                ->container
                ->getDefinition(sprintf('m6_statsd.%s', $service))
            )
            ->array($arguments = $definition->getArguments())
            ->array($servers = $arguments[0])
        ;

        foreach ($expectedServers as $key => $expectedServer) {
            $this
                ->string($servers[$key]['address'])
                    ->isEqualTo(sprintf('udp://%s', $expectedServer));
        }
    }

    public function shellPatternConfigDataProvider()
    {
        return [
            ['wildcard_foo',     ['foo', 'foobar', 'fooa', 'foob']],
            ['all',              ['foo', 'foobar', 'fooa', 'foob', 'bar', 'barfoo']],
            ['all_bis',          ['bar', 'barfoo', 'foo', 'foobar', 'fooa', 'foob']],
            ['foo_plusonechar',  ['fooa', 'foob']],
            ['foo_ab',           ['fooa', 'foob']],
            ['complex_ab',       ['fooa', 'foob']],
        ];
    }
}
