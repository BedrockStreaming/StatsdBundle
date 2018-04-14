<?php

namespace M6Web\Bundle\StatsdBundle\Tests\Units\Listener;

use M6Web\Bundle\StatsdBundle\Listener\ConsoleListener as Base;

use mageekguy\atoum;
use Symfony\Component\Console\ConsoleEvents as BaseConsoleEvent;
use M6Web\Bundle\StatsdBundle\Event\ConsoleEvent;

/**
* Console listener tests
*/
class ConsoleListener extends atoum\test
{
    public function getMockedDispatcher()
    {
        return new \mock\Symfony\Component\EventDispatcher\EventDispatcher();
    }

    public function eventDataProvider()
    {
        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->shuntParentClassCalls();

        $command    = new \mock\Symfony\Component\Console\Command\Command;
        $input      = new \mock\Symfony\Component\Console\Input\InputInterface;
        $output     = new \mock\Symfony\Component\Console\Output\OutputInterface;
        $exception  = new \mock\Exception();

        return [
            [
                'onCommand',
                new \Symfony\Component\Console\Event\ConsoleCommandEvent($command, $input, $output),
                [
                    [
                        'name'  => ConsoleEvent::COMMAND,
                        'class' => 'M6Web\Bundle\StatsdBundle\Event\ConsoleCommandEvent'
                    ]
                ]
            ],
            [
                'onTerminate',
                new \Symfony\Component\Console\Event\ConsoleTerminateEvent($command, $input, $output, 0),
                [
                    [
                        'name'  => ConsoleEvent::TERMINATE,
                        'class' => 'M6Web\Bundle\StatsdBundle\Event\ConsoleTerminateEvent'
                    ]
                ]
            ],
            [
                'onTerminate',
                new \Symfony\Component\Console\Event\ConsoleTerminateEvent($command, $input, $output, -1),
                [
                    [
                        'name'  => ConsoleEvent::TERMINATE,
                        'class' => 'M6Web\Bundle\StatsdBundle\Event\ConsoleTerminateEvent'
                    ],
                    [
                        'name'  => ConsoleEvent::ERROR,
                        'class' => 'M6Web\Bundle\StatsdBundle\Event\ConsoleTerminateEvent'
                    ]
                ]
            ],
            [
                'onException',
                new \Symfony\Component\Console\Event\ConsoleErrorEvent($input, $output, $exception, $command),
                [
                    [
                        'name'  => ConsoleEvent::EXCEPTION,
                        'class' => 'M6Web\Bundle\StatsdBundle\Event\ConsoleErrorEvent'
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider eventDataProvider
     *
     * @param string           $methodName  Method name to call
     * @param BaseConsoleEvent $baseEvent   Event fired from Symfony console command
     * @param array            $firedEvents Array who contains each new events fired from the $baseEvent dispatching
     */
    public function testEvent($methodName, $baseEvent, $firedEvents)
    {
        $self = $this;
        $dispatcher = $this->getMockedDispatcher();

        // Check, during the event firing, that the event is created from base event
        foreach($firedEvents as $firedEvent) {
            $dispatcher
                ->addListener(
                    $firedEvent['name'],
                    function ($event) use ($self, $baseEvent, $firedEvent) {
                        $self
                            ->object($event)
                                ->isInstanceOf($firedEvent['class'])//
                            ->object($event->getOriginalEvent())
                                ->isIdenticalTo($baseEvent);
                    }
                );
        }

        // Here is the code to initialize and fire event, but real test is just above
        $this
            ->given(
                $base = new Base(),
                $base->setEventDispatcher($dispatcher)
            )
            ->if($base->{$methodName}($baseEvent))
            ->then
                ->mock($dispatcher)
                    ->call('dispatch')
                        ->exactly(count($firedEvents))
        ;
    }
}