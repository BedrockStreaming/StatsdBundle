<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Handle datacollector for statsd
 */
class StatsdDataCollector extends DataCollector
{
    /** @var array */
    private $statsdClients;

    /**
     * Construct the data collector
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Reset the data collector to initial state
     */
    public function reset(): void
    {
        $this->statsdClients = [];
        $this->data = [
            'clients' => [],
            'operations' => 0,
        ];
    }

    /**
     * Kernel event
     *
     * @param Event $event The received event
     */
    public function onKernelResponse($event): void
    {
        if ($event instanceof KernelEvent && HttpKernelInterface::MASTER_REQUEST == $event->getRequestType()) {
            foreach ($this->statsdClients as $clientName => $client) {
                $clientInfo = [
                    'name' => $clientName,
                    'operations' => [],
                ];
                foreach ($client->getToSend() as $operation) {
                    if ($operation) {
                        $this->data['operations']++;
                        $message = $operation['message'];

                        $clientInfo['operations'][] = [
                            'server' => $operation['server'],
                            'node' => $message->getNode(),
                            'value' => $message->getValue(),
                            'sample' => $message->getSampleRate(),
                            'unit' => $message->getUnit(),
                        ];
                    }
                }
                $this->data['clients'][] = $clientInfo;
            }
        }
    }

    /**
     * Add a statsd client to monitor
     *
     * @param string $clientAlias  The client alias
     * @param object $statsdClient A statsd client instance
     */
    public function addStatsdClient($clientAlias, $statsdClient): void
    {
        $this->statsdClients[$clientAlias] = $statsdClient;
    }

    /**
     * Collect the data
     *
     * @param Request         $request   The request object
     * @param Response        $response  The response object
     * @param \Throwable|null $exception An exception
     *
     * @return void
     */
    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
    }

    /**
     * Return the list of statsd operations
     *
     * @return array operations list
     */
    public function getClients(): array
    {
        return $this->data['clients'];
    }

    /**
     * Return the number of statsd operations
     *
     * @return int the number of operations
     */
    public function getOperations()
    {
        return $this->data['operations'];
    }

    /**
     * Return the name of the collector
     *
     * @return string data collector name
     */
    public function getName()
    {
        return 'statsd';
    }
}
