<?php

namespace Swover\Utils;

/**
 * Events
 */
class Event extends ArrayObject
{
    public $events = [
        'master_start',
        'worker_start',
        'connect',
        'request',
        'task_start',
        'task_finish',
        'close',
        'response',
        'worker_stop'
    ];

    /**
     * The events instances
     * @var array
     */
    private $instances = [];

    /**
     * @param $name
     * @param mixed ...$parameter
     */
    public function trigger($name, ...$parameter)
    {
        if (!in_array($name, $this->events)) return;

        if (isset($this->instances[$name])) {
            foreach ($this->instances[$name] as $class => $instance) {
                call_user_func_array([$instance, 'trigger'], $parameter);
            }
        }
    }

    /**
     * Register events by array config
     *
     * @param $events
     * @return int
     */
    public function register($events)
    {
        $result = 0;
        foreach ($events as $name => $event) {
            if (!in_array($name, $this->events)) continue;

            if (is_array($event)) {
                foreach ($event as $item) {
                    $result += $this->bind($name, $item);
                }
            } else {
                $result += $this->bind($name, $event);
            }
        }
        return $result;
    }

    /**
     * Bind the class to name
     *
     * @param string $name
     * @param string|object $class
     * @return int
     */
    public function before($name, $class)
    {
        return $this->bind($name, $class, false);
    }

    /**
     * Bind the class to name
     *
     * @param string $name
     * @param string|object $class
     * @param bool $append
     * @return int
     */
    public function bind($name, $class, $append = true)
    {
        if (!is_string($class) && !is_object($class)) return 0;

        if (is_string($class)) {
            if (!class_exists($class)) {
                echo $class;
                return 0;
            }
            $class = new $class; //TODO
        }

        $interface = $this->getInterface($name);
        if (!$class instanceof $interface) return 0;

        // get_class($class)
        if ($append) {
            $this->instances[$name][] = $class;
        } else {
            array_unshift($this->instances[$name], $class);
        }

        return 1;
    }

    public function remove($name)
    {
        unset($this->instances[$name]);
    }

    private function getInterface($name)
    {
        return '\Swover\Contracts\Events\\' . str_replace(' ', '', ucwords(str_replace('_', " ", strtolower($name))));
    }
}