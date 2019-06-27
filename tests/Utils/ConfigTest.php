<?php

namespace Swover\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Swover\Utils\Config;

class ConfigTest extends TestCase
{
    public function testInit()
    {
        $config = [
            'a' => 1,
            'b' => 2
        ];
        $instance = Config::setInstance(new Config($config));
        $std = new \stdClass();
        $std->config = $instance;
        $std->config['b'] = 200;
        $newInstance = Config::getInstance();
        $std->config['a'] = 100;
        $this->assertEquals(100, $newInstance->get('a'));
        $this->assertEquals(200, $newInstance->get('b'));
    }
}
