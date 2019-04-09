<?php
namespace Swover\Utils;

/**
 * The Request data injected into Entrance Class
 */
class Request implements \ArrayAccess
{
    private $request = null;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->request[] = $value;
        } else {
            $this->request[$offset] = $value;
        }
    }

    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->request[$offset] : null;
    }

    public function offsetExists($offset)
    {
        return isset($this->request[$offset]);
    }

    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->request[$offset]);
        }
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
        if (is_string($this->request)) {
            return $this->request;
        }
        return '';
    }

    public function __debugInfo()
    {
        return $this->request;
    }
}