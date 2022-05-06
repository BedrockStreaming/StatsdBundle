<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdBundle\Statsd;

use M6Web\Component\Statsd\Client;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * classe pour le service statsd
 */
class Listener
{
    protected $statsdClient;

    /**
     * Construct the listener, injecting the statsd client service
     *
     * @param Client                   $statsdClient    The statsd client service
     * @param EventDispatcherInterface $eventDispatcher Event dispatcher to use
     */
    public function __construct(Client $statsdClient, EventDispatcherInterface $eventDispatcher)
    {
        $this->statsdClient = $statsdClient;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * onKernelException
     */
    public function onKernelException(ExceptionEvent $event)
    {
        // @TODO: remove this backward compatibility layer after symfony 4.4 has been dropped
        if (method_exists($event, 'getThrowable')) {
            $exception = $event->getThrowable();
        } else {
            $exception = $event->getException();
        }

        if ($exception instanceof HttpExceptionInterface) {
            $code = $exception->getStatusCode();
        } else {
            $code = 'unknown';
        }

        $this->eventDispatcher->dispatch(
            new StatsdEvent($code),
            'statsd.exception'
        );
    }

    /**
     * method called on the kernel.terminate event
     *
     * @param TerminateEvent $event event
     *
     * @return void
     */
    public function onKernelTerminate(TerminateEvent $event)
    {
        $this->statsdClient->send();
    }

    /**
     * method called on the console.terminate event
     *
     * @param ConsoleTerminateEvent $event event
     *
     * @return void
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $this->statsdClient->send();
    }

    /**
     * method called if base_collectors = true in config to dispatch base events
     * (you still have to catch them)
     */
    public function dispatchBaseEvents(TerminateEvent $event)
    {
        $this->dispatchMemory();
        $this->dispatchRequestTime($event);
    }

    /**
     * dispatchMemory dispatch a memory event
     */
    private function dispatchMemory()
    {
        $memory = memory_get_peak_usage(true);
        $memory = ($memory > 1024 ? intval($memory / 1024) : 0);

        $this->eventDispatcher->dispatch(
            new StatsdEvent($memory),
            'statsd.memory_usage'
        );
    }

    /**
     * dispatchRequestTime dispatch the request time.
     * This time is a "fake" one, because some actions are performed before the initialization of the request
     * It is ~100ms smaller than the real kernel time.
     */
    private function dispatchRequestTime(TerminateEvent $event)
    {
        $request = $event->getRequest();
        $startTime = $request->server->get('REQUEST_TIME_FLOAT', $request->server->get('REQUEST_TIME'));
        $time = microtime(true) - $startTime;
        $time = round($time * 1000);

        $this->eventDispatcher->dispatch(
            new StatsdEvent($time),
            'statsd.time'
        );
    }
}
