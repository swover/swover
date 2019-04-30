<?php

namespace Swover\Utils;

/**
 * The Request data injected into Entrance Class
 */
class Request extends \ArrayObject implements \ArrayAccess
{
    private $__default_string = '';

    private static $instances = [];

    public function __construct($request, $flags = 0, $iterator_class = "ArrayIterator")
    {
        if (is_array($request) || is_object($request)) {
            $input = $request;
        } else {
            $this->__default_string = strval($request);
            $input = [];
        }
        parent::__construct($input, $flags, $iterator_class);
    }

    public static function setInstance($instance, $name = 'default')
    {
        self::$instances[$name] = $instance;
    }

    public static function getInstance($name = 'default')
    {
        if (!isset(self::$instances[$name])) {
            $instance = new self([]);
            self::setInstance($instance, $name);
        }
        return self::$instances[$name];
    }

    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    public function __toString()
    {
        return $this->__default_string;
    }
}