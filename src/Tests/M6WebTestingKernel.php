<?php

namespace M6Web\Bundle\StatsdBundle\Tests;

use M6Web\Bundle\StatsdBundle\DependencyInjection\M6WebStatsdExtension as BaseM6WebStatsdExtension;
use M6Web\Bundle\StatsdBundle\M6WebStatsdBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Kernel;

class M6WebTestingKernel extends Kernel
{
    private $m6WebStatsdConfig;

    public function __construct(array $m6WebStatsdConfig = [])
    {
        $this->m6WebStatsdConfig = $m6WebStatsdConfig;

        parent::__construct('test', true);
    }

    public function registerBundles()
    {
        return [
            new M6WebStatsdBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function(ContainerBuilder $container) {
            $container->register('event_dispatcher', new EventDispatcher());
            $container->registerExtension(new BaseM6WebStatsdExtension());
            $container->loadFromExtension('m6_statsd');
        });
    }
}
