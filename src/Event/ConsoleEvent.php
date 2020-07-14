<?php
namespace M6Web\Bundle\StatsdBundle\Event;

use Symfony\Component\Console\Event\ConsoleEvent as BaseConsoleEvent;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Base console event
 */
abstract class ConsoleEvent extends Event
{
    const COMMAND   = 'm6web.console.command';
    const TERMINATE = 'm6web.console.terminate';
    const ERROR     = 'm6web.console.error';
    const EXCEPTION = 'm6web.console.exception';

    /**
     * Original triggered console event
     *
     * @var BaseConsoleEvent
     */
    protected $originalEvent;

    /**
     * Command start time
     *
     * @var float
     */
    protected $startTime;

    /**
     * Command execution time
     *
     * @var float
     */
    protected $executionTime;

    /**
     * @param BaseConsoleEvent $originalEvent
     * @param float            $startTime
     * @param float            $executionTime
     */
    public function __construct(BaseConsoleEvent $originalEvent, $startTime = null, $executionTime = null)
    {
        $this->originalEvent = $originalEvent;
        $this->startTime     = $startTime;
        $this->executionTime = $executionTime;
    }

    /**
     * Map calls to original event
     *
     * @param string $name
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($name, $parameters)
    {
        return call_user_func_array(
            [$this->originalEvent, $name],
            $parameters
        );
    }

    /**
     * Get command start time
     *
     * @return float
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Get command execution time
     *
     * @return float Time ellapsed since command start (ms)
     */
    public function getExecutionTime()
    {
        return $this->executionTime * 1000;
    }

    /**
     * Alias of getExecutionTime
     * Allows timer simple usage
     *
     * @return float
     */
    public function getTiming()
    {
        return $this->getExecutionTime();
    }

    /**
     * Get peak memory usage
     *
     * @return int
     */
    public function getPeakMemory()
    {
        $memory = memory_get_peak_usage(true);
        $memory = ($memory > 1024 ? intval($memory / 1024) : 0);

        return $memory;
    }

    /**
     * @return BaseConsoleEvent
     */
    public function getOriginalEvent()
    {
        return $this->originalEvent;
    }

    /**
     * Get an underscored command name, if available
     *
     * @return string|null
     */
    public function getUnderscoredCommandName()
    {
        if (!is_null($command = $this->getCommand())) {
            return str_replace(':', '_', $command->getName());
        }

        return null;
    }

    /**
     * Create new event object
     *
     * @param BaseConsoleEvent $e
     * @param float            $startTime
     * @param float            $executionTime
     *
     * @return ConsoleEvent
     *
     * @throws \InvalidArgumentException
     */
    public static function createFromConsoleEvent(BaseConsoleEvent $e, $startTime = null, $executionTime = null)
    {
        if (static::support($e)) {
            return new static($e, $startTime, $executionTime);
        } else {
            throw \InvalidArgumentException('Invalid event type.');
        }
    }

    /**
     * Check if given event is supported by current class
     *
     * @param BaseConsoleEvent $e
     *
     * @return boolean
     */
    protected static function support(BaseConsoleEvent $e)
    {
        return true;
    }
}
