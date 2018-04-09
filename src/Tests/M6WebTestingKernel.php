<?php

namespace M6Web\Bundle\StatsdBundle\Tests;

use M6Web\Bundle\StatsdBundle\M6WebStatsdBundle;
use Symfony\Component\HttpKernel\Kernel;

class M6WebTestingKernel extends Kernel
{
    public function registerBundles()
    {
        return [
            new M6WebStatsdBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        // TODO: Implement registerContainerConfiguration() method.
    }

}