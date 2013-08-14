<?php
namespace M6Web\Bundle\StatsdBundle\Statsd;

use Symfony\Component\EventDispatcher\Event;
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
    public function __construct(Client $statsdClient)
    {
        $this->statsdClient = $statsdClient;
    }

    /**
     * méthode appelé sur kernel.terminate
     * @param Event $event event
     *
     * @return void
     */
    public function onKernelTerminate(Event $event)
    {
        $this->statsdClient->send();
    }
}
