<?php
namespace M6Web\Bundle\StatsdBundle\Statsd;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

use M6Web\Component\Statsd\Client;

/**
 * classe pour le service statsd
 */
class Listener
{
    protected $statsdClient;

    /**
     * Construct the listener, injecting the statsd client service
     * @param Client $statsdClient The statsd client service
     */
    public function __construct(Client $statsdClient, EventDispatcherInterface $eventDispatcher)
    {
        $this->statsdClient = $statsdClient;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * onKernelException
     *
     * @param GetResponseForExceptionEvent $event
     * @access private
     * @return void
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $code = $event->getException()->getStatusCode();
        $this->eventDispatcher->dispatch(
            'statsd.exception',
            new StatsdEvent($code)
        );
    }

    /**
     * method called on the kernel.terminate event
     * @param PostResponseEvent $event event
     *
     * @return void
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        $this->statsdClient->send();
    }

    /**
     * onKernelTerminateEvents
     *
     * @param PostResponseEvent $event
     * @access public
     * @return void
     */
    public function onKernelTerminateEvents(PostResponseEvent $event)
    {
        $this->dispatchMemory();
        $this->dispatchRequestTime($event);
    }


    /**
     * dispatchMemory dispatch a memory event
     *
     * @access private
     * @return void
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
     * @access private
     * @return void
     */
    private function dispatchRequestTime(PostResponseEvent $event)
    {
        $request = $event->getRequest();
        $startTime = $request->server->get(
            'REQUEST_TIME_FLOAT',
            $request->server->get('REQUEST_TIME')
        );
        $time = microtime(true) - $startTime;
        $time = round($time * 1000);

        $this->eventDispatcher->dispatch(
            'statsd.time',
            new StatsdEvent($time)
        );
    }
}
