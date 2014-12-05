<?php
namespace M6Web\Bundle\StatsdBundle\DependencyInjection\tests\units;

use mageekguy\atoum;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use M6Web\Bundle\StatsdBundle\DependencyInjection\M6WebStatsdExtension as BaseM6WebStatsdExtension;
use Symfony\Component\EventDispatcher\EventDispatcher;


class M6WebStatsdExtension extends atoum\test
{
    /**
     * @var BaseM6WebStatsdExtension
     */
    protected  $extension;

    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     *
     */
    protected function initContainer()
    {
        $this->extension = new BaseM6WebStatsdExtension();

        $this->container = new ContainerBuilder();
        $this->container->register('event_dispatcher', new EventDispatcher());
        $this->container->registerExtension($this->extension);

    }

    /**
     * @param ContainerBuilder $container
     * @param                  $resource
     */
    protected function loadConfiguration(ContainerBuilder $container, $resource)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../Fixtures/'));
        $loader->load($resource.'.yml');
    }

    public function testBasicConfiguration()
    {
        $this->initContainer();
        $this->loadConfiguration($this->container, 'basic_config');
        $this->container->setParameter('kernel.debug', true);
        $this->container->compile();

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
        $this->initContainer();
        $this->loadConfiguration($this->container, 'basic_config');
        $this->container->setParameter('kernel.debug', false);
        $this->container->compile();

        $this->assert
            ->boolean($this->container->has('m6.data_collector.statsd'))
                ->isIdenticalTo(false);
    }

}