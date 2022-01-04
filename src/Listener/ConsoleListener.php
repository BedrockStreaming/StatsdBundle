<?php

namespace M6Web\Bundle\StatsdBundle\Listener;

use M6Web\Bundle\StatsdBundle\Event\ConsoleEvent;
use Symfony\Component\Console\Event\ConsoleEvent as BaseConsoleEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Listen to symfony command events
 * then trigger new custom events
 */
class ConsoleListener
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher = null;

    /**
     * Time when command started
     *
     * @var float
     */
    protected $startTime = null;

    /**
     * Define event dispatch
     */
    public function setEventDispatcher(EventDispatcherInterface $ev)
    {
        $this->eventDispatcher = $ev;
    }

    public function onCommand(BaseConsoleEvent $e)
    {
        $this->startTime = microtime(true);

        $this->dispatch($e, ConsoleEvent::COMMAND);
    }

    public function onTerminate(ConsoleTerminateEvent $e)
    {
        // For non-0 exit command, fire an ERROR event
        if ($e->getExitCode() != 0) {
            $this->dispatch($e, ConsoleEvent::ERROR);
        }

        $this->dispatch($e, ConsoleEvent::TERMINATE);
    }

    public function onException(BaseConsoleEvent $e)
    {
        $this->dispatch($e, ConsoleEvent::EXCEPTION);
    }

    /**
     * Dispatch custom event
     *
     * @param string $eventName
     *
     * @return bool
     */
    protected function dispatch(BaseConsoleEvent $e, $eventName)
    {
        if (!is_null($this->eventDispatcher)) {
            $class = str_replace(
                'Symfony\Component\Console\Event',
                'M6Web\Bundle\StatsdBundle\Event',
                get_class($e)
            );

            $finaleEvent = $class::createFromConsoleEvent(
                $e,
                $this->startTime,
                !is_null($this->startTime) ? microtime(true) - $this->startTime : null
            );

            return $this->eventDispatcher->dispatch($finaleEvent, $eventName);
        } else {
            return false;
        }
    }
}
