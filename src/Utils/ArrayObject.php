<?php

namespace Swover\Utils;

/**
 * The Base Instance used like array
 */
class ArrayObject extends \ArrayObject implements \ArrayAccess
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

    public static function setInstance($instance = null)
    {
        return static::$instance[static::class] = $instance;
    }

    /**
     * @param array $input
     * @return static
     */
    public static function getInstance($input = [])
    {
        if (!isset(static::$instance[static::class]) || is_null(static::$instance[static::class])) {
            static::setInstance(new static($input));
        }
        return static::$instance[static::class];
    }

    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    public function __get($name)
    {
        return $this->offsetExists($name) ? $this->offsetGet($name) : (isset($this->$name) ? $this->$name : null);
    }

    public function __isset($name)
    {
        return isset($this->$name) || $this->offsetExists($name);
    }

    public function __unset($name)
    {
        unset($this->$name);
        $this->offsetUnset($name);
    }

    public function __toString()
    {
        return $this->__default_string;
    }
}