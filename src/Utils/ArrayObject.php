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

    /**
     * Set Instance
     * if $instance is null, will destroy this bind
     * if static already register, will rewrite this bind
     *
     * @param null $instance
     * @return bool|null
     */
    public static function setInstance($instance = null)
    {
        if ($instance == null) {
            return static::destroyInstance();
        }
        return static::$instance[static::class] = $instance;
    }

    /**
     * Get Instance
     *
     * @param array $input
     * @param bool $rebuild
     * @return mixed
     */
    public static function getInstance($input = [], $rebuild = false)
    {
        if ($rebuild
            || !isset(static::$instance[static::class])
            || is_null(static::$instance[static::class])) {
            static::setInstance(new static($input));
        }
        return static::$instance[static::class];
    }

    /**
     * Destroy Instance
     *
     * @return bool
     */
    public static function destroyInstance()
    {
        static::$instance[static::class] = null;
        unset(static::$instance[static::class]);
        return true;
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