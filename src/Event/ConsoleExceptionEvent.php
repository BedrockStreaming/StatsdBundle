<?php

namespace M6Web\Bundle\StatsdBundle\Event;

use Symfony\Component\Console\Event\ConsoleExceptionEvent as BaseEvent;
use Symfony\Component\Console\Event\ConsoleEvent as BaseConsoleEvent;

/**
 * Triggered on console exception
 */
class ConsoleExceptionEvent extends ConsoleEvent
{
    /**
     * {@inheritDoc}
     */
    protected static function support(BaseConsoleEvent $e)
    {
        return $e instanceof BaseEvent;
    }
}
