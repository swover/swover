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
     * The bound events
     * @var array
     */
    private $bounds = [];

    /**
     * @param $name
     * @param mixed ...$parameter
     */
    public function trigger($name, ...$parameter)
    {
        if (!in_array($name, $this->events)) return;

        if (isset($this->bounds[$name])) {
            foreach ($this->bounds[$name] as $class) {
                if (!isset($this->instances[$name][$class])) continue;
                $instance = $this->instances[$name][$class];
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
                return 0;
            }
            $class = new $class; //TODO
        }

        $interface = $this->getInterface($name);
        if (!$class instanceof $interface) return 0;

        $alias = get_class($class);

        if (isset($this->instances[$name][$alias])){
            $this->remove($name, $alias);
        }

        if ($append) {
            $this->bounds[$name][] = $alias;
        } else {
            array_unshift($this->bounds[$name], $alias);
        }

        $this->instances[$name][$alias] = $class;
        return 1;
    }

    public function remove($name, $class = null)
    {
        if (is_null($class)) {
            unset($this->instances[$name], $this->bounds[$name]);
            return true;
        }

        if (is_object($class)) {
            $class = get_class($class);
        }
        $bind = array_search($class, $this->bounds[$name]);
        if ($bind !== null) {
            unset($this->bounds[$name][$bind]);
        }
        unset($this->instances[$name][$class]);
        return true;
    }

    public function clear()
    {
        $this->instances = [];
        $this->bounds = [];
    }

    public function getBounds()
    {
        return $this->bounds;
    }

    public function getInstances()
    {
        return $this->instances;
    }

    private function getInterface($name)
    {
        return '\Swover\Contracts\Events\\' . str_replace(' ', '', ucwords(str_replace('_', " ", strtolower($name))));
    }
}