<?php
namespace M6Web\Bundle\StatsdBundle\Client;

use M6Web\Component\Statsd\Client as BaseClient;

/**
 * Class that extends base statsd client, to handle auto-increment from event dispatcher notifications
 *
 * @author: Vincent Bouzeran <vincent.bouzeran@elao.com>
 */
class Client extends BaseClient
{
    protected $listenedEvents = array();

    /**
     * getter for listenedEvents
     * @return array
     */
    public function getListenedEvents()
    {
        return $this->listenedEvents;
    }

    /**
     * Add an event to listen
     * @param string $eventName   The event name to listen
     * @param array  $eventConfig The event handler configuration
     */
    public function addEventToListen($eventName, $eventConfig)
    {
        $this->listenedEvents[$eventName] = $eventConfig;
    }

    /**
     * Handle an event
     * @param EventInterface $event an event
     */
    public function handleEvent($event)
    {
        $name = $event->getName();
        if (!isset($this->listenedEvents[$name])) {
            return;
        }

        $config = $this->listenedEvents[$name];
        $immediateSend = false;

        foreach ($config as $conf => $confValue) {
            // increment
            if ('increment' === $conf) {
                $this->increment(self::replaceInNodeFormMethod($event, $confValue));
            } elseif ('timing' === $conf) {
                $this->addTiming($event, 'getTiming', self::replaceInNodeFormMethod($event, $confValue));
            } elseif (('custom_timing' === $conf) and is_array($confValue)) {
                $this->addTiming($event, $confValue['method'], self::replaceInNodeFormMethod($event, $confValue['node']));
            } elseif ('immediate_send' === $conf) {
                $immediateSend = $confValue;
            } else {
                throw new Exception("configuration : ".$conf." not handled by the StatsdBundle or its value is in a wrong format.");
            }
        }

        if ($immediateSend) {
            $this->send();
        }
    }


    /**
     * factorisation of the timing method
     * find the value timed
     *
     * @param object $event        Event
     * @param string $timingMethod method callable in the event
     * @param string $node         node
     *
     * @return void
     */
    private function addTiming($event, $timingMethod, $node)
    {
        if (!method_exists($event, $timingMethod)) {
            throw new Exception("The event class ".get_class($event)." must have a ".$timingMethod." method in order to mesure timer");
        }
        $timing = call_user_func(array($event,$timingMethod));
        if ($timing > 0) {
            $this->timing($node, $timing);
        }
    }

    /**
     * remplace une chaine par un nom de méthode
     * @param EventInterface $event an event
     * @param string         $node  le node ds lequel faire le remplacement
     *
     * @return string
     */
    private static function replaceInNodeFormMethod($event, $node)
    {
        $propertyAccessor = \Symfony\Component\PropertyAccess\PropertyAccess::getPropertyAccessor();

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
