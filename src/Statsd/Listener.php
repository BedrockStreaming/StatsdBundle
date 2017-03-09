<?php

namespace M6Web\Bundle\StatsdBundle\Statsd;

use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

use M6Web\Component\Statsd\Client;

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
        $this->statsdClient    = $statsdClient;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * onKernelException
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        if ($exception instanceof HttpExceptionInterface) {
            $code = $event->getException()->getStatusCode();
        } else {
            $code = 'unknown';
        }
        $this->eventDispatcher->dispatch(
            'statsd.exception',
            new StatsdEvent($code)
        );
    }

    /**
     * method called on the kernel.terminate event
     *
     * @param PostResponseEvent $event event
     *
     * @return void
     */
    public function onKernelTerminate(PostResponseEvent $event)
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
     *
     * @param PostResponseEvent $event
     */
    public function dispatchBaseEvents(PostResponseEvent $event)
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
            'statsd.memory_usage',
            new StatsdEvent($memory)
        );
    }

    /**
     * dispatchRequestTime dispatch the request time.
     * This time is a "fake" one, because some actions are performed before the initialization of the request
     * It is ~100ms smaller than the real kernel time.
     *
     * @param PostResponseEvent $event
     */
    private function dispatchRequestTime(PostResponseEvent $event)
    {
        $request   = $event->getRequest();
        $startTime = $request->server->get('REQUEST_TIME_FLOAT', $request->server->get('REQUEST_TIME'));
        $time      = microtime(true) - $startTime;
        $time      = round($time * 1000);

        $this->eventDispatcher->dispatch(
            'statsd.time',
            new StatsdEvent($time)
        );
    }
}
