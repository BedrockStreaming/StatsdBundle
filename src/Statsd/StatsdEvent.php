<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdBundle\Statsd;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event for this bundle event dispatching
 */
class StatsdEvent extends GenericEvent implements MonitorableEventInterface
{
    public function getTiming()
    {
        return $this->getSubject();
    }

    public function getValue()
    {
        return $this->getSubject();
    }

    /**
     * array of tags [key => value]
     *
     * @return array|mixed
     */
    public function getTags()
    {
        return $this->hasArgument('tags') ? $this->getArgument('tags') : [];
    }
}
