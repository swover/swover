<?php

namespace Swover\Utils;

/**
 * Events
 */
class Event extends ArrayObject
{
    /**
     * The events instances
     * @var array
     */
    protected $instances = [];

    /**
     * The bound events
     * @var array
     */
    protected $bounds = [];

    /**
     * @param $type
     * @param mixed ...$parameter
     */
    public function trigger($type, ...$parameter)
    {
        if (!isset($this->bounds[$type])) return;

        foreach ($this->bounds[$type] as $class) {
            if (!isset($this->instances[$type][$class])) continue;
            $instance = $this->instances[$type][$class];
            if (method_exists($instance, 'trigger')) {
                call_user_func_array([$instance, 'trigger'], $parameter);
                continue;
            }

            if (is_callable($instance)) {
                call_user_func_array($instance, $parameter);
                continue;
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
        if (!is_array($events)) $events = [$events];

        $result = 0;
        foreach ($events as $event) {
            if (is_array($event)) {
                foreach ($event as $item) {
                    $result += $this->bind($item);
                }
            } else {
                $result += $this->bind($event);
            }
        }
        return $result;
    }

    /**
     * Bind the class to name
     *
     * @param string|object $class
     * @return int
     */
    public function before($class)
    {
        return $this->bind($class, false);
    }

    /**
     * Bind a class to events, This class must define `EVENT_TYPE` constant and `trigger` method
     *
     * @param string|object $class
     * @param bool $append
     * @return int
     */
    public function bind($class, $append = true)
    {
        if (!$this->checkClass($class)) return 0;

        if (is_string($class)) {
            if (!class_exists($class)) return 0;
            try {
                $reflection = new \ReflectionClass($class);
                if (! $reflection->isInstantiable()) return 0;
                $class = $reflection->newInstance();
            } catch (\Exception $e) {
                return 0;
            }
        }

        $alias = get_class($class);

        $type = $this->getEventType($alias);

        if (!$type) return 0;

        return $this->bindInstance($type, $alias, $class, $append);
    }

    /**
     * Bind an instance to the specified event-type, named $alias
     *
     * @param string $type
     * @param string $alias
     * @param object $instance
     * @param bool $append
     * @return int
     */
    public function bindInstance($type, $alias, $instance, $append = true)
    {
        $type = strtolower($type);
        if (isset($this->instances[$type][$alias])) {
            $this->removeAlias($type, $alias);
        }

        if ($append) {
            $this->bounds[$type][] = $alias;
        } else {
            array_unshift($this->bounds[$type], $alias);
        }

        $this->instances[$type][$alias] = $instance;
        return 1;
    }

    /**
     * Remove class from bounds
     * This class must define `EVENT_TYPE` constant and `trigger` method
     *
     * @param object|string $class
     * @return bool
     */
    public function remove($class)
    {
        if (!$this->checkClass($class)) return false;

        if (is_object($class)) {
            $class = get_class($class);
        }

        $type = $this->getEventType($class);

        if (!$type) return false;

        return $this->removeAlias($type, $class);
    }

    /**
     * Remove bound by alias from $type event-bounds
     *
     * @param $type
     * @param string $alias
     * @return bool
     */
    public function removeAlias($type, $alias = null)
    {
        $type = strtolower($type);
        if (is_null($alias)) {
            unset($this->instances[$type], $this->bounds[$type]);
            return true;
        }

        $bind = array_search($alias, $this->bounds[$type]);

        if ($bind !== null) {
            unset($this->bounds[$type][$bind]);
        }
        unset($this->instances[$type][$alias]);

        return true;
    }

    /**
     * Clear all event bounds
     */
    public function clear()
    {
        $this->instances = [];
        $this->bounds = [];
    }

    /**
     * Get event type from class
     * @param string $class
     * @return bool | string
     */
    public function getEventType($class)
    {
        if (!is_string($class)) {
            return false;
        }

        if (!class_exists($class)) {
            return false;
        }

        if (!defined($class . '::EVENT_TYPE')) {
            return false;
        }

        if (!method_exists($class, 'trigger')) {
            return false;
        }

        return $class::EVENT_TYPE;
    }

    /**
     * Determine is an available Class
     * @param $class
     * @return bool
     */
    private function checkClass($class)
    {
        return is_string($class) || is_object($class);
    }
}