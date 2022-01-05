<?php

namespace M6Web\Bundle\StatsdBundle\Statsd;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event for this bundle event dispatching
 */
class StatsdEvent extends GenericEvent implements MonitorableEventInterface
{
    /**
     * getTiming
     *
     * @return mixed
     */
    public function getTiming()
    {
        return $this->getSubject();
    }

    /**
     * getValue
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->getSubject();
    }

    /**
     * array of tags [key => value]
     *
     * @return array
     */
    public function getTags()
    {
        return $this->hasArgument('tags') ? $this->getArgument('tags') : [];
    }
}
