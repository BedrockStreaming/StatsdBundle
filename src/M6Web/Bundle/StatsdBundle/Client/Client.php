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

        foreach ($config as $conf => $dynamicNode) {
            // increment
            if ('increment' === $conf) {
                $this->increment(self::replaceInNodeFormMethod($event, $dynamicNode));
            // $conf beginning with timing
            // you can use now timingMemory, for exemple, if your event has a getMemory method
            } elseif (preg_match('/^timing([0-9A-Za-z]*)$/', $conf, $matches)) {
                if (!$matches[1]) {
                    $method = 'getTiming';
                } else {
                    $method = 'get'.ucfirst(strtolower($matches[1]));
                }
                if (!method_exists($event, $method)) {
                    throw new Exception("The event class ".get_class($event)." must have a ".$method." method in order to mesure timer");
                }
                $timing = call_user_func(array($event,$method));
                if ($timing > 0) {
                    $this->timing(self::replaceInNodeFormMethod($event, $dynamicNode), $timing);
                }
            } else {
                throw new Exception("configuration : ".$conf." not handled by the StatsdBundle");
            }
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
