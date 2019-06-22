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

    public function bind($events)
    {
        $result = 0;
        foreach ($events as $name => $event) {
            if (!in_array($name, $this->events)) continue;

            if (is_array($event)) {
                foreach ($event as $item) {
                    $result += $this->resolve($name, $item);
                }
            } else {
                $result += $this->resolve($name, $event);
            }
        }
        return $result;
    }

    private function resolve($name, $class)
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

        //TODO
        $this->instances[$name][get_class($class)] = $class;
        return 1;
    }

    private function getInterface($name)
    {
        return '\Swover\Contracts\Events\\' . str_replace(' ', '', ucwords(str_replace('_', " ", strtolower($name))));
    }
}