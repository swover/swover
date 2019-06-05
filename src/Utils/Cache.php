<?php

namespace Swover\Utils;

/**
 * The Base Instance used like array
 */
class Cache extends \ArrayObject implements \ArrayAccess
{
    private $__default_string = '';

    protected static $instance = [];

    public function __construct($input, $flags = 0, $iterator_class = "ArrayIterator")
    {
        if (!is_array($input) && !is_object($input)) {
            $this->__default_string = strval($input);
            $input = [];
        }
        parent::__construct($input, $flags, $iterator_class);
    }

    /**
     * @param $name
     * @param null $instance
     * @return mixed
     */
    public static function setInstance($name, $instance = null)
    {
        static::$instance[$name] = $instance;
        return static::$instance[$name];
    }

    public static function clearInstance($name)
    {
        self::setInstance($name);
        unset(static::$instance[$name]);
    }

    /**
     * @param string $name
     * @return static|null
     */
    public static function getInstance($name)
    {
        if (!isset(static::$instance[$name])) {
            self::setInstance($name, new self([]));
        }
        return static::$instance[$name];
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