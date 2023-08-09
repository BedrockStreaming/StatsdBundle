<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdBundle\Event;

use Symfony\Component\Console\Event\ConsoleErrorEvent as BaseEvent;
use Symfony\Component\Console\Event\ConsoleEvent as BaseConsoleEvent;

/**
 * Triggered on console exception
 */
class ConsoleErrorEvent extends ConsoleEvent
{
    protected static function support(BaseConsoleEvent $e)
    {
        return $e instanceof BaseEvent;
    }
}
