<?php
namespace Swover\Utils;

/**
 * Base Class of Entrance
 * If the entry-class extend this class
 * The entry class will be instantiated when the service is invoked
 */
class Entrance
{
    protected $request = [];

    public function __construct(array $request)
    {
        $this->request = $request;
    }

    public function __get($name)
    {
        return isset($this->request[$name]) ? $this->request[$name] : false;
    }

    public function __isset($name)
    {
        return isset($this->$name) || isset($this->request[$name]);
    }
}