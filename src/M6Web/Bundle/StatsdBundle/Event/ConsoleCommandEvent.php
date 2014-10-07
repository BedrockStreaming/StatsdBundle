<?php

namespace M6Web\Bundle\StatsdBundle\Event;

use Symfony\Component\Console\Event\ConsoleCommandEvent as BaseEvent;
use Symfony\Component\Console\Event\ConsoleEvent as BaseConsoleEvent;

/**
 * Triggered when command start
 */
class ConsoleCommandEvent extends ConsoleEvent
{
    /**
     * {@inheritDoc}
     */
    protected static function support(BaseConsoleEvent $e)
    {
        return $e instanceof BaseEvent;
    }
}
