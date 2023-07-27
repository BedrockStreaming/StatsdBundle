<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdBundle\Event;

use Symfony\Component\Console\Event\ConsoleEvent as BaseConsoleEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent as BaseEvent;

/**
 * Triggered when console terminate
 */
class ConsoleTerminateEvent extends ConsoleEvent
{
    protected static function support(BaseConsoleEvent $e)
    {
        return $e instanceof BaseEvent;
    }
}
