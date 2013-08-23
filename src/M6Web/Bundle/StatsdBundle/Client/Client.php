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
    protected $listenedEvents;


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

        // increment
        if (isset($config['increment'])) {
            $incr = $config['increment'];
            $incr = self::replaceInNodeFormMethod($event, $incr);
            $this->increment($incr);
        }

        // timing
        if (isset($config['timing'])) {
            // l'event a t'il une méthode getTimer ?
            if (!method_exists($event, 'getTiming')) {
                throw new Exception("The event class ".get_class($event)." must have a getTiming method in order to mesure timer");
            }
            if ($event->getTiming() > 0) {
                $timer = $config['timing'];
                $timer = self::replaceInNodeFormMethod($event, $timer);
                $this->timing($timer, $event->getTiming());
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
