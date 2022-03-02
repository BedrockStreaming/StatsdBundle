<?php

namespace M6Web\Bundle\StatsdBundle\Tests\Units\Client;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess;

/**
 * Client class
 */
class Client extends \atoum
{
    /**
     * return a mocked client
     *
     * @return Client
     */
    protected function getMockedClient()
    {
        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->orphanize('increment');
        $this->mockGenerator->orphanize('decrement');
        $this->mockGenerator->orphanize('timing');
        $client = new \mock\M6Web\Bundle\StatsdBundle\Client\Client();
        $client->clearToSend();

        return $client;
    }

    /**
     * testHandleEventWithValidConfig
     */
    public function testHandleEventWithValidConfigIncrement()
    {
        $client = $this->getMockedClient();

        $event = new \Symfony\Contracts\EventDispatcher\Event();

        $client->addEventToListen('test', [
            'increment' => 'stats.<name>',
        ]);

        $this->if($client->handleEvent($event, 'test'))
            ->then
            ->mock($client)
                ->call('increment')
                    ->withArguments('stats.test')
                    ->once()
                ->call('send')
                    ->never();
    }

    /**
     * testHandleEventWithValidConfig
     */
    public function testHandleEventWithValidConfigDecrement()
    {
        $client = $this->getMockedClient();

        $event = new \Symfony\Contracts\EventDispatcher\Event();

        $client->addEventToListen('test', [
            'decrement' => 'stats.<name>',
        ]);

        $this->if($client->handleEvent($event, 'test'))
            ->then
            ->mock($client)
                ->call('decrement')
                    ->withArguments('stats.test')
                    ->once()
                ->call('send')
                    ->never();
    }

    /**
     * testHandleEventWithImmediateSend
     */
    public function testHandleEventWithImmediateSend()
    {
        $client = $this->getMockedClient();

        $event = new \Symfony\Contracts\EventDispatcher\Event();

        $client->addEventToListen('test', [
            'increment' => 'stats.<name>',
            'immediate_send' => true,
        ]);

        $this->if($client->handleEvent($event, 'test'))
            ->then
                ->mock($client)
                    ->call('increment')
                        ->withArguments('stats.test')
                        ->once()
                    ->call('send')
                        ->once();
    }

    /**
     * Test handleEvent with to send limit
     */
    public function testHandleEventWithToSendLimit()
    {
        $client = $this->getMockedClient();
        $client->setToSendLimit(3);

        $event = new \Symfony\Contracts\EventDispatcher\Event();

        $queue = new \SplQueue();

        $client->getMockController()->increment = function ($value) use ($queue) {
            $queue->enqueue($value);
        };
        $client->getMockController()->getToSend = $queue;
        $client->getMockController()->send = function () use ($queue) {
            while ($queue->count() > 0) {
                $queue->dequeue();
            }
        };

        $client->addEventToListen('test', [
            'increment' => 'stats.<name>',
        ]);

        for ($i = 1; $i <= 50; $i++) {
            $this
                ->if($client->handleEvent($event, 'test'))
                ->mock($client)->call('send')->exactly(floor($i / 3));
        }
    }

    /**
     * test handle event with an invalid stats
     */
    public function testHandleEventWithInvalidConfigIncrement()
    {
        $client = $this->getMockedClient();

        $client->setPropertyAccessor(PropertyAccess\PropertyAccess::createPropertyAccessorBuilder()->enableMagicCall()->getPropertyAccessor());

        $client->addEventToListen('test', [
            'increment' => 'stats.<toto>',
        ]);

        $this->exception(function () use ($client) {
            $event = new \Symfony\Contracts\EventDispatcher\Event();

            $client->handleEvent($event, 'test');
        });
    }

    /**
     * test handleEvent method with event without getTimingMethod
     */
    public function testHandleEventWithInvalidEventTiming()
    {
        $client = $this->getMockedClient();

        $client->addEventToListen('test', [
            'timing' => 'stats.<name>',
        ]);

        $this->exception(function () use ($client) {
            $event = new \Symfony\Contracts\EventDispatcher\Event();

            $client->handleEvent($event, 'test');
        });

        $client = $this->getMockedClient();

        $client->addEventToListen('test', [
            'timingMemory' => 'stats.raoul',
        ]);

        $this->exception(function () use ($client) {
            $event = new \Symfony\Contracts\EventDispatcher\Event();

            $client->handleEvent($event, 'test');
        });
    }

    /**
     * test handleEvent method with timing event
     */
    public function testHandleEventWithValidEventTiming()
    {
        $client = $this->getMockedClient();

        $client->addEventToListen('test', [
            'timing' => 'stats.<name>',
        ]);

        $event = new Event();

        $client->addEventToListen('test', [
            'timing' => 'stats.<name>',
        ]);

        $this->if($client->handleEvent($event, 'test'))
            ->then
            ->mock($client)
                ->call('timing')
                    ->withArguments('stats.test', 101)
                    ->once();
    }

    /**
     * test handleEvent method with custom timing event
     */
    public function testHandleEventWithValidCustomEventTiming()
    {
        $client = $this->getMockedClient();

        $client->addEventToListen('test', [
            'timing' => 'stats.<name>',
        ]);

        $event = new Event();

        $client->addEventToListen('test', [
            'custom_timing' => ['node' => 'stats.<name>', 'method' => 'getMemory'],
        ]);

        $this->if($client->handleEvent($event, 'test'))
            ->then
            ->mock($client)
                ->call('timing')
                    ->withArguments('stats.test', 102)
                    ->once();
    }

    /**
     * Test that the handleEvent method sends a timing for a configured event
     */
    public function testHandleEventCallsConfiguredTiming()
    {
        $client = $this->getMockedClient();
        $client->addEventToListen('test.event.name', [
            'timing' => 'my.statsd.node',
        ]);
        $client->getMockController()->timing = function () {};

        $event = new Event();

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('test.event.name', [$client, 'handleEvent']);

        $this
            ->if($dispatcher->dispatch($event, 'test.event.name'))
                ->mock($client)
                    ->call('timing')
                        ->withArguments('my.statsd.node', 101, 1.0)
                        ->once();
    }

    /**
     * Test that the handleEvent method don't send a timing for a non-configured event
     */
    public function testHandleEventDontCallTimingOnUnconfiguredEvent()
    {
        $client = $this->getMockedClient();
        $client->addEventToListen('test.event.name', [
            'timing' => 'my.statsd.node',
        ]);
        $client->getMockController()->timing = function () {};

        $event = new Event();

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('test.event.name', [$client, 'handleEvent']);

        $this
            ->if($dispatcher->dispatch($event, 'test.event.other.name'))
                ->mock($client)
                    ->call('timing')
                    ->never();
    }
}
