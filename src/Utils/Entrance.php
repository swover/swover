<?php
namespace Swover\Utils;

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