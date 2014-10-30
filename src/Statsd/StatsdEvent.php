<?php

namespace M6Web\Bundle\StatsdBundle\Statsd;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event for this bundle event dispatching
 */
class StatsdEvent extends GenericEvent
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
}
