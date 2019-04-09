<?php

namespace Swover\Utils;

/**
 * The Request data injected into Entrance Class
 */
class Request extends \ArrayObject implements \ArrayAccess
{
    private static $string_key = 'swover_request_array_object_string_key';

    public function __construct($request, $flags = 0, $iterator_class = "ArrayIterator")
    {
        if (is_array($request) || is_object($request)) {
            $input = $request;
        } else {
            $input[self::$string_key] = $request;
        }
        parent::__construct($input, $flags, $iterator_class);
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
        if ($this->offsetExists(self::$string_key)) {
            return $this->offsetGet(self::$string_key);
        }
        return '';
    }
}