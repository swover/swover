<?php

namespace Swover\Tests\Utils;

use Swover\Utils\Request;

class RequestTest extends \PHPUnit\Framework\TestCase
{
    public function testArrayGet()
    {
        $input = [
            'get' => [
                'gka' => 'gva',
                'gkb' => 'gvb'
            ],
            'header' => [],
            'server' => [],
        ];
        $instance = new Request($input);
        $this->assertEquals('gva', $instance->get('gka'));
        //$this->assertEquals($input['get'], $instance->get());
        $this->assertEquals(null, $instance->get('gkc'));
        $this->assertEquals('sin', $instance->get('name', 'sin'));
        $this->assertEquals('GET', $instance->method());
    }

    public function testArrayPost()
    {
        $input = [
            'post' => [
                'pka' => 'pva',
                'pkb' => 'pvb'
            ],
            'server' => [
                'request_method' => 'post'
            ]
        ];
        $instance = new Request($input);
        $this->assertEquals('pva', $instance->post('pka'));
        $this->assertEquals(null, $instance->post('pkc'));
        $this->assertEquals('Psin', $instance->post('name', 'Psin'));
        $this->assertEquals('POST', $instance->method());
    }

    public function testArrayInput()
    {
        $input = [
            'input' => json_encode(['ika' => 'iva', 'ikc' => 'ivc']),
            'header' => [],
            'server' => [
                'request_method' => 'post'
            ],
        ];
        $instance = new Request($input);
        $this->assertEquals($input['input'], $instance->input());
        $this->assertEquals('POST', $instance->method());
    }

    //TODO  header url method server

    public function testGet()
    {
        $request = new \Swoole\Http\Request();
        $request->get = [
            'gka' => 'gva',
            'gkb' => 'gvb'
        ];
        $request->server = [
            'request_method' => 'get',
            'query_string' => '?gka=gva&gkb=gvb'
        ];
        $instance = new Request($request);
        $this->assertEquals('gva', $instance->get('gka'));
        $this->assertEquals(null, $instance->get('gkc'));
        $this->assertEquals('sin', $instance->get('name', 'sin'));
        $this->assertEquals('GET', $instance->method());
        //$this->assertEquals('gka=gva&gkb=gvb', '');
    }

    public function testPost()
    {
        $request = new \Swoole\Http\Request();
        $request->get = [
            'pka' => 'pva',
            'pkb' => 'pvb'
        ];

        $request->server = [
            'request_method' => 'post',
        ];

        $instance = new Request($request);
        $this->assertEquals('pva', $instance->get('pka'));
        $this->assertEquals(null, $instance->get('pkc'));
        $this->assertEquals('Psin', $instance->get('name', 'Psin'));
        $this->assertEquals('POST', $instance->method());
    }

    public function testInput()
    {
        $request = new \Swoole\Http\Request();
        $request->post = [json_encode(['ika' => 'iva', 'ikc' => 'ivc']) => ''];
        $request->server = [
            'request_method' => 'post',
        ];

        $instance = new Request($request);
        $this->assertEquals(json_encode(['ika' => 'iva', 'ikc' => 'ivc']), $instance->input());
    }
}

