<?php

namespace Swover\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Swover\Utils\ArrayObject;

class ArrayTest extends TestCase
{
    public function testString()
    {
        $input = 'Hello World!';
        $instance = new ArrayObject($input);
        $instance['name'] = 'ruesin';
        $this->assertEquals($input, $instance);
        $this->assertEquals(strlen('Hello World!'), strlen($instance));
        $this->assertEquals('ruesin', $instance['name']);
    }

    public function testEmpty()
    {
        $input = [];
        $instance = new ArrayObject($input);
        $this->assertEquals(true, empty((array)$instance));
    }

    public function testArray()
    {
        $input = [
            'name' => 'ruesin'
        ];
        $instance = new ArrayObject($input);
        $instance['age'] = 100;
        $this->assertEquals('ruesin', $instance['name']);
        $this->assertEquals(100, $instance['age']);
    }

    public function testSingle()
    {
        $input = [
            'name' => 'ruesin'
        ];
        $instance = ArrayObject::getInstance($input);
        $this->assertEquals('ruesin', $instance['name']);

        $newInstance = ArrayObject::getInstance();
        $this->assertEquals(1, count($newInstance));
    }

    public function testDestroy()
    {
        ArrayObject::setInstance();

        $input = [
            'name' => 'ruesin'
        ];
        $instance = ArrayObject::getInstance($input);
        $this->assertEquals('ruesin', $instance['name']);

        ArrayObject::destroyInstance();

        $newInstance = ArrayObject::getInstance();
        $this->assertEquals(0, count($newInstance));
    }

    public function testRewrite()
    {
        ArrayObject::destroyInstance();

        $input = [
            'name' => 'ruesin'
        ];
        $instance = ArrayObject::getInstance($input);
        $this->assertEquals('ruesin', $instance['name']);

        ArrayObject::setInstance(new ArrayObject(['name'=>'sin']));
        $newInstance = ArrayObject::getInstance();
        $this->assertEquals('sin', $newInstance['name']);
    }

    public function testGetInstance()
    {
        $instance = ArrayObject::getInstance();
        $instance['name'] = 'ruesin';
        $this->assertEquals('ruesin', $instance['name']);
    }
}