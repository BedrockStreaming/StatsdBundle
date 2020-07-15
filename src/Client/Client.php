<?php

namespace M6Web\Bundle\StatsdBundle\Client;

use M6Web\Bundle\StatsdBundle\Statsd\MonitorableEventInterface;
use M6Web\Component\Statsd\Client as BaseClient;
use Symfony\Component\PropertyAccess;

/**
 * Class that extends base statsd client, to handle auto-increment from event dispatcher notifications
 *
 */
class Client extends BaseClient
{
    protected $listenedEvents = [];

    /**
     * @var PropertyAccessInterface
     */
    protected $propertyAccessor;

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
     * set the property accessor used in replaceConfigPlaceholder
     * @param PropertyAccess\PropertyAccessorInterface $propertyAccessor
     *
     * @return $this
     */
    public function setPropertyAccessor(PropertyAccess\PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;

        return $this;
    }

    /**
     * Handle an event
     *
     * @param EventInterface $event an event
     * @param string         $name  the event name
     *
     * @throws Exception
     * @return void
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
        $tags          = $this->mergeTags($event, $config);

        // replace placeholders in tags
        $tags = array_map(function($tag) use ($event, $name) {
            return $this->replaceConfigPlaceholder($event, $name, $tag);
        }, $tags);

        foreach ($config as $conf => $confValue) {

            // increment
            if ('increment' === $conf) {
                $this->increment($this->replaceConfigPlaceholder($event, $name, $confValue), 1, $tags);
            } elseif ('decrement' === $conf) {
                $this->decrement($this->replaceConfigPlaceholder($event, $name, $confValue), 1, $tags);
            } elseif ('count' === $conf) {
                $value = $this->getEventValue($event, 'getValue');
                $this->count($this->replaceConfigPlaceholder($event, $name, $confValue), $value, 1, $tags);
            } elseif ('gauge' === $conf) {
                $value = $this->getEventValue($event, 'getValue');
                $this->gauge($this->replaceConfigPlaceholder($event, $name, $confValue), $value, 1, $tags);
            } elseif ('set' === $conf) {
                $value = $this->getEventValue($event, 'getValue');
                $this->set($this->replaceConfigPlaceholder($event, $name, $confValue), $value, 1, $tags);
            } elseif ('timing' === $conf) {
                $this->addTiming($event, 'getTiming', $this->replaceConfigPlaceholder($event, $name, $confValue), $tags);
            } elseif (('custom_timing' === $conf) and is_array($confValue)) {
                $this->addTiming($event, $confValue['method'], $this->replaceConfigPlaceholder($event, $name, $confValue['node']), $tags);
            } elseif ('immediate_send' === $conf) {
                $immediateSend = $confValue;
            } elseif ('tags' === $conf) {
                // nothing
            } else {
                throw new Exception("configuration : ".$conf." not handled by the StatsdBundle or its value is in a wrong format.");
            }
        }

        if (null !== $this->toSendLimit && $this->getToSend()->count() >= $this->toSendLimit) {
            $immediateSend = true;
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
     * @throws Exception
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
     * @param object $event Event
     * @param string $timingMethod Callable method in the event
     * @param string $node Node
     * @param array  $tags Tags key => value for influxDb
     *
     * @throws Exception
     */
    private function addTiming($event, $timingMethod, $node, $tags = [])
    {
        $timing = $this->getEventValue($event, $timingMethod);
        if ($timing > 0) {
            $this->timing($node, $timing, 1, $tags);
        }
    }

    /**
     * Replaces a string with a method name
     *
     * @param EventInterface $event An event
     * @param string         $eventName The name of the event
     * @param string         $string  The node in which the replacing will happen
     *
     * @return string
     */
    private function replaceConfigPlaceholder($event, $eventName, $string)
    {
        // `event->getName()` is deprecated, we have to replace <name> directly with $eventName
        $string = str_replace('<name>', $eventName, $string);

        if ((preg_match_all('/<([^>]*)>/', $string, $matches) > 0) and ($this->propertyAccessor !== null)) {
            $tokens = $matches[1];
            foreach ($tokens as $token) {
                $value = $this->propertyAccessor->getValue($event, $token);
                $string = str_replace('<'.$token.'>', $value, $string);
            }
        }

        return $string;
    }

    /**
     * Merge config tags with tags manually sent with the event
     *
     * @param mixed $event
     * @param array $config
     *
     * @return array of tags
     */
    private function mergeTags($event, $config)
    {
        $configTags = isset($config['tags']) ? $config['tags'] : [];

        if ($event instanceof MonitorableEventInterface) {
            return array_merge($configTags, $event->getTags());
        }

        return $configTags;
    }
}
