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
    protected $dispatcher;

    public function getBaseInstance()
    {
        $listener   = new Base;

        $this->dispatcher = new \mock\Symfony\Component\EventDispatcher\EventDispatcher;
        $this->dispatcher->addListener(BaseConsoleEvent::COMMAND, [$listener, 'onCommand']);
        $this->dispatcher->addListener(BaseConsoleEvent::TERMINATE, [$listener, 'onTerminate']);
        $this->dispatcher->addListener(BaseConsoleEvent::EXCEPTION, [$listener, 'onException']);

        $listener->setEventDispatcher($this->dispatcher);

        return $listener;
    }

    public function triggerConsoleEvent($eventName, $eventClass)
    {
        $eventClass = '\mock\\'.$eventClass;

        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->shuntParentClassCalls();

        $event = new $eventClass;

        $this->dispatcher->dispatch($eventName, $event);
    }

    public function eventDataProvider()
    {
        return [
            [
                BaseConsoleEvent::COMMAND,
                'Symfony\Component\Console\Event\ConsoleCommandEvent',
                ConsoleEvent::COMMAND,
                'M6Web\Bundle\StatsdBundle\Event\ConsoleCommandEvent'
            ],
            [
                BaseConsoleEvent::TERMINATE,
                'Symfony\Component\Console\Event\ConsoleTerminateEvent',
                ConsoleEvent::TERMINATE,
                'M6Web\Bundle\StatsdBundle\Event\ConsoleTerminateEvent'
            ],
            [
                BaseConsoleEvent::EXCEPTION,
                'Symfony\Component\Console\Event\ConsoleExceptionEvent',
                ConsoleEvent::EXCEPTION,
                'M6Web\Bundle\StatsdBundle\Event\ConsoleExceptionEvent'
            ],
        ];
    }

    /**
     * @dataProvider eventDataProvider
     */
    public function testEvent($baseEvent, $baseEventClass, $newEvent, $newEventClass)
    {
        $self     = $this;
        $listener = $this->getBaseInstance();

        $this
            ->dispatcher
            ->addListener(
                $newEvent,
                function($event) use($self, $newEventClass) {
                    $self
                        ->object($event)
                            ->isInstanceOf($newEventClass)
                    ;
                }
            )
        ;

        $this->triggerConsoleEvent($baseEvent,$baseEventClass);

        $this
            ->mock($this->dispatcher)
                ->call('dispatch')
                    ->twice()
        ;
    }
}