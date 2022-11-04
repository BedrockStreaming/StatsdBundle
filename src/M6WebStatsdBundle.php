<?php

namespace M6Web\Bundle\StatsdBundle;

use M6Web\Bundle\StatsdBundle\DependencyInjection\M6WebStatsdExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * M6WebStatsdBundle
 */
class M6WebStatsdBundle extends Bundle
{
    /**
     * trick allowing bypassing the Bundle::getContainerExtension check on getAlias
     * not very clean, to investigate
     *
     * @return M6WebStatsdExtension|ExtensionInterface
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new M6WebStatsdExtension();
        }

        return $this->extension;
    }
}
