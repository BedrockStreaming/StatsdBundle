<?php

namespace M6Web\Bundle\StatsdBundle\Client;

use M6Web\Component\Statsd\Client as BaseClient;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Class that extends base statsd client, to handle auto-increment from event dispatcher notifications
 *
 * @author: Vincent Bouzeran <vincent.bouzeran@elao.com>
 */
class Client extends BaseClient
{
    protected $listenedEvents = array();

    protected $toSendLimit;

    /**
     * getter for listenedEvents
     *
     * @return array
     */
    public function getListenedEvents()
    {
        return $this->listenedEvents;
    }

    /**
     * Add an event to listen
     *
     * @param string $eventName   The event name to listen
     * @param array  $eventConfig The event handler configuration
     */
    public function addEventToListen($eventName, $eventConfig)
    {
        $this->listenedEvents[$eventName] = $eventConfig;
    }

    /**
     * Set toSend limit
     *
     * @param int $toSendLimit
     *
     * @return $this
     */
    public function setToSendLimit($toSendLimit)
    {
        $this->toSendLimit = $toSendLimit;

        return $this;
    }

    /**
     * Handle an event
     *
     * @param EventInterface $event an event
     * @param string         $name the event name
     */
    public function handleEvent($event, $name = null)
    {
        // this is used to stay compatible with Symfony 2.3
        if (is_null($name)) {
            $name = $event->getName();
        }

        if (!isset($this->listenedEvents[$name])) {
            return;
        }

        $config        = $this->listenedEvents[$name];
        $immediateSend = false;

        foreach ($config as $conf => $confValue) {
            // increment
            if ('increment' === $conf) {
                $this->increment(self::replaceInNodeFormMethod($event, $name, $confValue));
            } elseif ('count' === $conf) {
                $value = $this->getEventValue($event, 'getValue');
                $this->count(self::replaceInNodeFormMethod($event, $name, $confValue), $value);
            } elseif ('gauge' === $conf) {
                $value = $this->getEventValue($event, 'getValue');
                $this->gauge(self::replaceInNodeFormMethod($event, $name, $confValue), $value);
            } elseif ('set' === $conf) {
                $value = $this->getEventValue($event, 'getValue');
                $this->set(self::replaceInNodeFormMethod($event, $name, $confValue), $value);
            } elseif ('timing' === $conf) {
                $this->addTiming($event, 'getTiming', self::replaceInNodeFormMethod($event, $name, $confValue));
            } elseif (('custom_timing' === $conf) and is_array($confValue)) {
                $this->addTiming($event, $confValue['method'], self::replaceInNodeFormMethod($event, $name, $confValue['node']));
            } elseif ('immediate_send' === $conf) {
                $immediateSend = $confValue;
            } else {
                throw new Exception("configuration : ".$conf." not handled by the StatsdBundle or its value is in a wrong format.");
            }
        }

        if (null !== $this->toSendLimit && $this->getToSend()->count() >= $this->toSendLimit) {
            $this->send();

            return;
        }

        if ($immediateSend) {
            $this->send();
        }
    }

    /**
     * getEventValue
     *
     * @param Event  $event
     * @param string $method
     *
     * @return mixed
     */
    private function getEventValue($event, $method)
    {
        if (!method_exists($event, $method)) {
            throw new Exception("The event class ".get_class($event)." must have a ".$method." method in order to mesure value");
        }

        return call_user_func(array($event,$method));
    }

    /**
     * Factorisation of the timing method
     * find the value timed
     *
     * @param object $event        Event
     * @param string $timingMethod Callable method in the event
     * @param string $node         Node
     *
     * @return void
     */
    private function addTiming($event, $timingMethod, $node)
    {
        $timing = $this->getEventValue($event, $timingMethod);
        if ($timing > 0) {
            $this->timing($node, $timing);
        }
    }

    /**
     * Replaces a string with a method name
     *
     * @param EventInterface $event An event
     * @param string         $eventName The name of the event
     * @param string         $node  The node in which the replacing will happen
     *
     * @return string
     */
    private static function replaceInNodeFormMethod($event, $eventName, $node)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableMagicCall()
            ->getPropertyAccessor();

        // `event->getName()` is deprecated, we have to replace <name> directly with $eventName
        $node = str_replace('<name>', $eventName, $node);

        if (preg_match_all('/<([^>]*)>/', $node, $matches) > 0) {
            $tokens = $matches[1];
            foreach ($tokens as $token) {
                $value = $propertyAccessor->getValue($event, $token);

                $node = str_replace('<'.$token.'>', $value, $node);
            }
        }

        return $node;
    }
}
