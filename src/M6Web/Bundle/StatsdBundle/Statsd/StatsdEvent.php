<?php

namespace M6Web\Bundle\StatsdBundle\Statsd;

use Symfony\Component\EventDispatcher\GenericEvent;

class StatsdEvent extends GenericEvent
{
    /**
     * getTiming
     *
     * @access public
     * @return mixed
     */
    public function getTiming()
    {
        return $this->getSubject();
    }

    /**
     * getValue
     *
     * @access public
     * @return mixed
     */
    public function getValue()
    {
        return $this->getSubject();
    }
}
